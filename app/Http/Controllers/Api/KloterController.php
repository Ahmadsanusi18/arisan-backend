<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kloter;
use App\Models\KloterPeserta;
use App\Models\RequestNomor;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KloterController extends Controller
{
    public function index()
    {
        $kloters = Kloter::withCount([
            'pesertaFix',
            'requestNomors as pending_request_count' => function ($q) {
                $q->where('status', 'pending');
            },
        ])
            ->orderByDesc('id')
            ->get();

        foreach ($kloters as $kloter) {
            $this->syncStatus($kloter);

            $hariIni = Carbon::today();

            $kloter->load('pesertaFix');

            $kloter->ada_pemenang = $kloter->pesertaFix->contains(
                function ($slot) use ($kloter, $hariIni) {
                    $tanggalMenang = Carbon::parse($kloter->tanggal_mulai)
                        ->addDays(
                            ($slot->nomor_urut * $kloter->durasi_pembayaran_hari) - 1
                        );

                    return $tanggalMenang->lte($hariIni);
                }
            );

            $kloter->pemenang_hari_ini = $kloter->pesertaFix->contains(
                function ($slot) use ($kloter, $hariIni) {
                    $tanggalMenang = Carbon::parse($kloter->tanggal_mulai)
                        ->addDays(
                            ($slot->nomor_urut * $kloter->durasi_pembayaran_hari) - 1
                        );

                    return $tanggalMenang->isSameDay($hariIni);
                }
            );
        }

        return response()->json($kloters);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'total_nilai_arisan' => 'required|integer|min:0',
            'jumlah_peserta_maks' => 'required|integer|min:2|max:100',
            'durasi_pembayaran_hari' => 'required|integer|min:1|max:3650',
            'tanggal_mulai' => 'required|date',
            'biaya_admin' => 'required|integer|min:0',
        ]);

        $data['kode_qr'] = (string) Str::uuid();
        $data['status'] = 'aktif';

        $kloter = Kloter::create($data);

        return response()->json($kloter, 201);
    }

    public function show(Kloter $kloter)
    {
        $this->syncStatus($kloter);

        $kloter->load([
            'pesertaFix.peserta',
            'pesertaFix.jadwalPembayaran',
            'requestNomors' => fn ($q) =>
                $q->where('status', 'pending')
                    ->orderBy('waktu_request'),
        ]);

        return response()->json($kloter);
    }

    public function update(Request $request, Kloter $kloter)
    {
        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'total_nilai_arisan' => 'sometimes|required|integer|min:0',
            'jumlah_peserta_maks' => 'sometimes|required|integer|min:2|max:100',
            'durasi_pembayaran_hari' => 'sometimes|required|integer|min:1|max:3650',
            'tanggal_mulai' => 'sometimes|required|date',
            'biaya_admin' => 'sometimes|required|integer|min:0',
        ]);

        if (isset($data['jumlah_peserta_maks'])) {
            $sudahFix = $kloter->pesertaFix()->count();

            if ($data['jumlah_peserta_maks'] < $sudahFix) {
                return response()->json([
                    'message' => "Tidak bisa diubah, sudah ada {$sudahFix} slot yang fix di kloter ini.",
                ], 422);
            }
        }

        $kloter->update($data);

        $this->syncStatus($kloter->fresh());

        return response()->json($kloter->fresh());
    }

    public function destroy(Kloter $kloter)
    {
        $kloter->delete();

        return response()->json([
            'message' => 'Kloter dihapus',
        ]);
    }

    public function showByQr(string $kode_qr)
    {
        $kloter = Kloter::where('kode_qr', $kode_qr)->firstOrFail();

        $this->syncStatus($kloter);

        $fix = KloterPeserta::where('kloter_id', $kloter->id)
            ->pluck('nomor_urut')
            ->toArray();

        $pending = RequestNomor::where('kloter_id', $kloter->id)
            ->where('status', 'pending')
            ->get()
            ->groupBy('nomor_urut');

        $slots = [];

        for ($i = 1; $i <= $kloter->jumlah_peserta_maks; $i++) {
            if (in_array($i, $fix)) {
                $status = 'fix';
            } elseif (isset($pending[$i])) {
                $status = 'direquest';
            } else {
                $status = 'tersedia';
            }

            $slots[] = [
                'nomor_urut' => $i,
                'status' => $status,
                'jumlah_request' => isset($pending[$i])
                    ? $pending[$i]->count()
                    : 0,
            ];
        }

        return response()->json([
            'kloter' => [
                'nama' => $kloter->nama,
                'total_nilai_arisan' => $kloter->total_nilai_arisan,
                'jumlah_peserta_maks' => $kloter->jumlah_peserta_maks,
                'durasi_pembayaran_hari' => $kloter->durasi_pembayaran_hari,
                'tanggal_mulai' => $kloter->tanggal_mulai,
                'status' => $kloter->status,
            ],
            'slots' => $slots,
        ]);
    }

    public function qrImage(Kloter $kloter)
    {
        $frontendUrl = config(
            'app.frontend_url',
            'http://localhost:5173'
        );

        $joinUrl = rtrim($frontendUrl, '/') .
            "/join/{$kloter->kode_qr}";

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($joinUrl)
            ->size(400)
            ->margin(10)
            ->build();

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType());
    }

    /**
     * Menentukan status kloter berdasarkan tanggal
     * dan kondisi seluruh jadwal pembayaran.
     *
     * Aturan:
     * - selesai   : sudah mencapai tanggal akhir dan semua pembayaran lunas
     * - terlambat : ada pembayaran yang sudah lewat jatuh tempo
     *               dan masih belum dibayar
     * - aktif     : selain kondisi di atas
     */
    private function syncStatus(Kloter $kloter): void
    {
        $hariIni = Carbon::today();

        $jumlahPeserta = $kloter->pesertaFix()->count();

        /*
         * Kloter belum penuh, jadi belum bisa dianggap selesai.
         */
        if ($jumlahPeserta < $kloter->jumlah_peserta_maks) {
            $adaTerlambat = $kloter->pesertaFix()
                ->whereHas('jadwalPembayaran', function ($query) use ($hariIni) {
                    $query
                        ->where('status_bayar', 'belum')
                        ->whereDate('tanggal_jatuh_tempo', '<', $hariIni);
                })
                ->exists();

            $statusBaru = $adaTerlambat
                ? 'terlambat'
                : 'aktif';

            if ($kloter->status !== $statusBaru) {
                $kloter->update([
                    'status' => $statusBaru,
                ]);
            }

            return;
        }

        /*
         * Semua slot peserta sudah terisi.
         * Hitung tanggal akhir berdasarkan periode terakhir.
         *
         * Contoh:
         * mulai 1 Oktober
         * durasi 5 hari
         * peserta 2
         *
         * tanggal akhir = 10 Oktober
         */
        $tanggalAkhir = Carbon::parse($kloter->tanggal_mulai)
            ->addDays(
                ($kloter->jumlah_peserta_maks *
                    $kloter->durasi_pembayaran_hari) - 1
            );

        /*
         * Sebelum tanggal akhir, kloter belum boleh selesai.
         * Tetapi tetap bisa menjadi terlambat.
         */
        if ($hariIni->lt($tanggalAkhir)) {
            $adaTerlambat = $kloter->pesertaFix()
                ->whereHas('jadwalPembayaran', function ($query) use ($hariIni) {
                    $query
                        ->where('status_bayar', 'belum')
                        ->whereDate('tanggal_jatuh_tempo', '<', $hariIni);
                })
                ->exists();

            $statusBaru = $adaTerlambat
                ? 'terlambat'
                : 'aktif';

            if ($kloter->status !== $statusBaru) {
                $kloter->update([
                    'status' => $statusBaru,
                ]);
            }

            return;
        }

        /*
         * Sudah mencapai tanggal akhir.
         * Kloter selesai hanya jika semua jadwal pembayaran lunas.
         */
        $adaBelumBayar = $kloter->pesertaFix()
            ->whereHas('jadwalPembayaran', function ($query) {
                $query->where('status_bayar', 'belum');
            })
            ->exists();

        $statusBaru = $adaBelumBayar
            ? 'terlambat'
            : 'selesai';

        if ($kloter->status !== $statusBaru) {
            $kloter->update([
                'status' => $statusBaru,
            ]);
        }
    }
}