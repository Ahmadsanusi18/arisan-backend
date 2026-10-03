<?php

namespace App\Console\Commands;

use App\Models\Kloter;
use App\Models\LogNotifikasiAdminArisan;
use App\Models\User;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KirimNotifikasiPemenangArisan extends Command
{
    protected $signature = 'arisan:kirim-notifikasi-pemenang';

    protected $description = 'Kirim notifikasi kloter penuh dan pemenang harian ke admin';

    public function handle(WhatsappService $wa): int
    {
        $this->kirimNotifikasiKloterPenuh($wa);
        $this->kirimNotifikasiPemenangHariIni($wa);

        return self::SUCCESS;
    }

    /**
     * Kirim daftar peserta ketika kloter sudah penuh.
     * Notifikasi ini hanya dikirim satu kali per kloter.
     */
    protected function kirimNotifikasiKloterPenuh(
        WhatsappService $wa
    ): void {
        $kloters = Kloter::with([
            'pesertaFix.peserta',
        ])
            ->where('status', 'aktif')
            ->get();

        foreach ($kloters as $kloter) {
            $peserta = $kloter->pesertaFix
                ->sortBy('nomor_urut')
                ->values();

            if (
                $peserta->count() < $kloter->jumlah_peserta_maks
            ) {
                continue;
            }

            $sudahKirim = LogNotifikasiAdminArisan::where(
                'kloter_id',
                $kloter->id
            )
                ->where('jenis', 'kloter_penuh')
                ->where('status_kirim', 'sukses')
                ->exists();

            if ($sudahKirim) {
                continue;
            }

            $baris = [];

            foreach ($peserta as $slot) {
                $tanggalMenang = $this->tanggalMenang(
                    $kloter,
                    $slot->nomor_urut
                );

                $baris[] =
                    $slot->nomor_urut
                    . '. '
                    . $slot->peserta->nama
                    . ' — '
                    . $tanggalMenang->translatedFormat('d F Y');
            }

            $pesan =
                "*Kloter {$kloter->nama} sudah penuh!*\n\n"
                . "Jumlah peserta: *{$peserta->count()}*\n\n"
                . implode("\n", $baris);

            $suksesSemua = $this->kirimKeSemuaAdmin(
                $wa,
                $pesan
            );

            LogNotifikasiAdminArisan::create([
                'kloter_id' => $kloter->id,
                'user_id' => null,
                'jenis' => 'kloter_penuh',
                'tanggal_target' => null,
                'waktu_kirim' => now(),
                'status_kirim' => $suksesSemua
                    ? 'sukses'
                    : 'gagal',
            ]);

            $this->info(
                "Notifikasi kloter penuh: {$kloter->nama} "
                . ($suksesSemua ? 'berhasil.' : 'gagal.')
            );
        }
    }

    /**
     * Kirim notifikasi pemenang pada hari ini.
     *
     * Jika hanya satu pemenang:
     *
     * Pemenang Arisan kot
     *
     * Sanusi
     * Tanggal 4 Oktober 2026
     *
     * Jika lebih dari satu:
     *
     * Pemenang Arisan
     * Tanggal 4 Oktober 2026
     *
     * kot — Sanusi
     * pp — Budi
     */
    protected function kirimNotifikasiPemenangHariIni(
        WhatsappService $wa
    ): void {
        $hariIni = now()->startOfDay();

        $kloters = Kloter::with([
            'pesertaFix.peserta',
        ])
            ->where('status', 'aktif')
            ->get();

        $pemenang = [];

        foreach ($kloters as $kloter) {
            foreach (
                $kloter->pesertaFix
                    ->sortBy('nomor_urut')
                    as $slot
            ) {
                $tanggalMenang = $this->tanggalMenang(
                    $kloter,
                    $slot->nomor_urut
                );

                if (!$tanggalMenang->isSameDay($hariIni)) {
                    continue;
                }

                $pemenang[] = [
                    'kloter' => $kloter,
                    'slot' => $slot,
                ];
            }
        }

        if (empty($pemenang)) {
            return;
        }

        /*
         * Hanya pemenang yang belum berhasil
         * diberitahukan ke admin tertentu yang diproses.
         */
        $admins = User::whereNotNull('nomor_wa')->get();

        if ($admins->isEmpty()) {
            $this->warn(
                'Tidak ada admin dengan nomor WhatsApp.'
            );

            return;
        }

        foreach ($admins as $admin) {
            $pemenangBelumDikirim = [];

            foreach ($pemenang as $item) {
                $sudahKirim = LogNotifikasiAdminArisan::where(
                    'kloter_id',
                    $item['kloter']->id
                )
                    ->where('user_id', $admin->id)
                    ->where('jenis', 'pemenang_harian')
                    ->whereDate(
                        'tanggal_target',
                        $hariIni->toDateString()
                    )
                    ->where('status_kirim', 'sukses')
                    ->exists();

                if (!$sudahKirim) {
                    $pemenangBelumDikirim[] = $item;
                }
            }

            if (empty($pemenangBelumDikirim)) {
                continue;
            }

            $pesan = $this->buatPesanPemenang(
                $pemenangBelumDikirim,
                $hariIni
            );

            $berhasil = $wa->kirim(
                $admin->nomor_wa,
                $pesan
            );

            foreach ($pemenangBelumDikirim as $item) {
                LogNotifikasiAdminArisan::create([
                    'kloter_id' => $item['kloter']->id,
                    'user_id' => $admin->id,
                    'jenis' => 'pemenang_harian',
                    'tanggal_target' => $hariIni->toDateString(),
                    'waktu_kirim' => now(),
                    'status_kirim' => $berhasil
                        ? 'sukses'
                        : 'gagal',
                ]);
            }

            $this->info(
                "Notifikasi pemenang ke admin {$admin->id} "
                . ($berhasil ? 'berhasil.' : 'gagal.')
            );
        }
    }

    /**
     * Membuat format pesan pemenang sesuai jumlah pemenang.
     */
    protected function buatPesanPemenang(
        array $pemenang,
        Carbon $tanggal
    ): string {
        $tanggalFormat = $tanggal->translatedFormat('d F Y');

        if (count($pemenang) === 1) {
            $item = $pemenang[0];

            return
                "Pemenang Arisan {$item['kloter']->nama}\n\n"
                . $item['slot']->peserta->nama
                . "\nTanggal {$tanggalFormat}";
        }

        $baris = [];

        foreach ($pemenang as $item) {
            $baris[] =
                "{$item['kloter']->nama} — "
                . $item['slot']->peserta->nama;
        }

        return
            "Pemenang Arisan\n"
            . "Tanggal {$tanggalFormat}\n\n"
            . implode("\n", $baris);
    }

    /**
     * Rumus tanggal pemenang:
     *
     * nomor 1 + durasi 10 = hari ke-10
     * nomor 2 + durasi 10 = hari ke-20
     */
    protected function tanggalMenang(
        Kloter $kloter,
        int $nomorUrut
    ): Carbon {
        return Carbon::parse($kloter->tanggal_mulai)
            ->addDays(
                ($nomorUrut * $kloter->durasi_pembayaran_hari) - 1
            );
    }

    /**
     * Digunakan untuk notifikasi kloter penuh.
     */
    protected function kirimKeSemuaAdmin(
        WhatsappService $wa,
        string $pesan
    ): bool {
        $admins = User::whereNotNull('nomor_wa')->get();

        if ($admins->isEmpty()) {
            $this->warn(
                'Tidak ada admin dengan nomor WhatsApp.'
            );

            return false;
        }

        $semuaSukses = true;

        foreach ($admins as $admin) {
            if (!$wa->kirim($admin->nomor_wa, $pesan)) {
                $semuaSukses = false;
            }
        }

        return $semuaSukses;
    }
}