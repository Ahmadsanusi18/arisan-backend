<?php

namespace App\Console\Commands;

use App\Models\JadwalPembayaran;
use App\Models\LogPengingat;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KirimReminderArisan extends Command
{
    protected $signature = 'arisan:kirim-reminder';

    protected $description = 'Kirim pengingat H-1 dan hari-H sesuai waktu yang diatur admin';

    public function handle(WhatsappService $wa): int
    {
        $pengaturan = Pengaturan::first();

        if (!$pengaturan || !$pengaturan->jam_kirim_pengingat_peserta) {
            $this->info('Jam pengingat belum diatur.');
            return self::SUCCESS;
        }

        $sekarang = now();

        $jamKirim = substr(
            trim($pengaturan->jam_kirim_pengingat_peserta),
            0,
            5
        );

        $jamSekarang = $sekarang->format('H:i');

        if ($jamSekarang !== $jamKirim) {
            return self::SUCCESS;
        }

        $this->info("Waktu reminder cocok: {$jamSekarang}");

        $hariIni = $sekarang->toDateString();
        $besok = $sekarang->copy()->addDay()->toDateString();

        $this->prosesJenis(
            jenis: 'H-1',
            tanggal: $besok,
            wa: $wa,
            tanggalLog: $hariIni,
            template: $pengaturan->template_pesan_pengingat
        );

        $this->prosesJenis(
            jenis: 'H',
            tanggal: $hariIni,
            wa: $wa,
            tanggalLog: $hariIni,
            template: $pengaturan->template_pesan_pengingat
        );

        return self::SUCCESS;
    }

    protected function prosesJenis(
        string $jenis,
        string $tanggal,
        WhatsappService $wa,
        string $tanggalLog,
        ?string $template
    ): void {
        $jadwals = JadwalPembayaran::with([
            'kloterPeserta.peserta',
            'kloterPeserta.kloter',
        ])
            ->whereDate('tanggal_jatuh_tempo', $tanggal)
            ->where('status_bayar', 'belum')
            ->get();

        if ($jadwals->isEmpty()) {
            $this->info("Tidak ada jadwal {$jenis} untuk {$tanggal}.");
            return;
        }

        $grup = $jadwals->groupBy(function ($jadwal) {
            return $jadwal->kloterPeserta->kloter_id
                . '-' .
                $jadwal->kloterPeserta->peserta->id;
        });

        $kloterTerkirim = [];

        foreach ($grup as $items) {
            $contoh = $items->first();

            $kloterPeserta = $contoh->kloterPeserta;
            $kloter = $kloterPeserta->kloter;
            $peserta = $kloterPeserta->peserta;

            if (!$kloter || !$peserta) {
                continue;
            }

            /*
             * Anti-spam:
             * Kalau jadwal + jenis reminder yang sama sudah
             * berhasil dikirim pada hari ini, jangan kirim lagi
             * secara otomatis.
             */
            $jadwalYangBelumDikirim = $items->filter(function ($jadwal) use ($jenis, $tanggalLog) {
                return !LogPengingat::where('jadwal_pembayaran_id', $jadwal->id)
                    ->where('jenis', $jenis)
                    ->whereDate('created_at', $tanggalLog)
                    ->where('status_kirim', 'sukses')
                    ->exists();
            });

            if ($jadwalYangBelumDikirim->isEmpty()) {
                continue;
            }

            $labelWaktu = $jenis === 'H-1'
                ? 'besok'
                : 'hari ini';

            $tanggalJatuhTempo = Carbon::parse(
                $contoh->tanggal_jatuh_tempo
            )->translatedFormat('d M Y');

            /*
             * Nominal pembayaran peserta.
             *
             * Contoh:
             * Total arisan Rp200.000
             * 2 peserta
             * = Rp100.000 per peserta
             */
            $nominalPembayaran = $kloterPeserta->nominal_pembayaran;

            $nominalFormatted = 'Rp' . number_format(
                $nominalPembayaran,
                0,
                ',',
                '.'
            );

            /*
             * Template default jika pengaturan belum memiliki
             * template pesan.
             */
            $templatePesan = $template ?: 'Halo {nama_peserta}, pengingat pembayaran arisan *{nama_kloter}* jatuh tempo {keterangan_jatuh_tempo} ({tanggal_jatuh_tempo}). Nominal pembayaran: *{nominal_pembayaran}*. Mohon segera dibayar ya 🙏';

            /*
             * Ganti semua variabel template dengan data sebenarnya.
             */
            $pesan = str_replace(
                [
                    '{nama_peserta}',
                    '{nama_kloter}',
                    '{nominal_pembayaran}',
                    '{tanggal_jatuh_tempo}',
                    '{keterangan_jatuh_tempo}',
                    '{jenis}',
                ],
                [
                    $peserta->nama,
                    $kloter->nama,
                    $nominalFormatted,
                    $tanggalJatuhTempo,
                    $labelWaktu,
                    $jenis,
                ],
                $templatePesan
            );

            $this->info(
                "Mengirim {$jenis} ke {$peserta->nomor_wa} - {$kloter->nama} - {$nominalFormatted}"
            );

            $sukses = $wa->kirim(
                $peserta->nomor_wa,
                $pesan
            );

            foreach ($jadwalYangBelumDikirim as $jadwal) {
                LogPengingat::create([
                    'jadwal_pembayaran_id' => $jadwal->id,
                    'kloter_id' => $kloter->id,
                    'jenis' => $jenis,
                    'waktu_kirim' => now(),
                    'status_kirim' => $sukses
                        ? 'sukses'
                        : 'gagal',
                ]);
            }

            if ($sukses) {
                $kloterTerkirim[$kloter->id] = $kloter->nama;

                $this->info(
                    "Berhasil mengirim ke {$peserta->nama}."
                );
            } else {
                $this->error(
                    "Gagal mengirim ke {$peserta->nama}."
                );

                Log::warning('Reminder arisan gagal dikirim', [
                    'jenis' => $jenis,
                    'jadwal_ids' => $jadwalYangBelumDikirim
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'peserta_id' => $peserta->id,
                    'nomor_wa' => $peserta->nomor_wa,
                ]);
            }

            sleep(rand(3, 5));
        }

        foreach ($kloterTerkirim as $namaKloter) {
            $this->notifAdmin(
                "Pengingat *{$jenis}* untuk kloter *{$namaKloter}* sudah selesai dikirim ke peserta.",
                $wa
            );
        }
    }

    protected function notifAdmin(
        string $pesan,
        WhatsappService $wa
    ): void {
        $admins = User::whereNotNull('nomor_wa')->get();

        foreach ($admins as $admin) {
            $wa->kirim(
                $admin->nomor_wa,
                $pesan
            );
        }
    }
}