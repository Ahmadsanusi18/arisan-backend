<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPembayaran;
use App\Models\Kloter;
use App\Models\KloterPeserta;
use App\Models\Peserta;
use App\Models\RequestNomor;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestNomorController extends Controller
{
    /**
     * PUBLIK — peserta submit request 1 atau lebih nomor sekaligus lewat halaman scan QR.
     * Ini BUKAN langsung fix, cuma masuk antrian menunggu approval admin.
     */
    public function store(Request $request, string $kode_qr)
    {
        $kloter = Kloter::where('kode_qr', $kode_qr)->firstOrFail();

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:20',
            'nomor_urut' => 'required|array|min:1',
            'nomor_urut.*' => 'integer|min:1|max:' . $kloter->jumlah_peserta_maks,
        ]);

        $sudahFix = KloterPeserta::where('kloter_id', $kloter->id)
            ->whereIn('nomor_urut', $data['nomor_urut'])
            ->pluck('nomor_urut')
            ->toArray();

        if (count($sudahFix) > 0) {
            return response()->json([
                'message' => 'Nomor ' . implode(', ', $sudahFix) . ' sudah fix/terisi, tidak bisa direquest lagi.',
            ], 422);
        }

        $dibuat = [];

        foreach ($data['nomor_urut'] as $nomor) {
            $dibuat[] = RequestNomor::create([
                'kloter_id' => $kloter->id,
                'nomor_urut' => $nomor,
                'peserta_nama' => $data['nama'],
                'peserta_nomor_wa' => $data['nomor_wa'],
                'waktu_request' => now(),
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'message' => 'Request berhasil dikirim, menunggu approval admin.',
            'data' => $dibuat,
        ], 201);
    }

    /**
     * ADMIN — lihat semua request, dikelompokkan per kloter & nomor urut,
     * diurutkan siapa yang lebih dulu request.
     */
    public function index(Request $request)
    {
        $query = RequestNomor::query()->with('kloter');

        if ($request->filled('kloter_id')) {
            $query->where('kloter_id', $request->kloter_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query
            ->orderBy('nomor_urut')
            ->orderBy('waktu_request')
            ->get()
            ->groupBy(['kloter_id', 'nomor_urut']);

        return response()->json($data);
    }

    /**
     * ADMIN — approve atau reject 1 request.
     */
    public function update(Request $request, RequestNomor $requestNomor)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        if ($requestNomor->status !== 'pending') {
            return response()->json([
                'message' => 'Request ini sudah diproses sebelumnya.',
            ], 422);
        }

        if ($data['status'] === 'rejected') {
            $requestNomor->update([
                'status' => 'rejected',
            ]);

            return response()->json($requestNomor);
        }

        return DB::transaction(function () use ($requestNomor) {
            $kloter = Kloter::where('id', $requestNomor->kloter_id)
                ->lockForUpdate()
                ->first();

            $sudahFix = KloterPeserta::where('kloter_id', $kloter->id)
                ->where('nomor_urut', $requestNomor->nomor_urut)
                ->exists();

            if ($sudahFix) {
                $requestNomor->update([
                    'status' => 'rejected',
                ]);

                return response()->json([
                    'message' => 'Nomor ini baru saja fix duluan lewat proses lain. Request ini otomatis ditolak.',
                ], 409);
            }

            $nomorNormal = app(WhatsappService::class)
                ->normalisasiNomor($requestNomor->peserta_nomor_wa);

            // Jangan mencari peserta berdasarkan nomor WA.
            // Nomor yang sama boleh mempunyai nama yang berbeda.
            $peserta = Peserta::create([
                'nama' => $requestNomor->peserta_nama,
                'nomor_wa' => $nomorNormal,
            ]);

            $slot = KloterPeserta::create([
                'kloter_id' => $kloter->id,
                'peserta_id' => $peserta->id,
                'nomor_urut' => $requestNomor->nomor_urut,
                'sumber' => 'qr_approval',
            ]);

            $this->generateJadwalPembayaran($slot, $kloter);

            $requestNomor->update([
                'status' => 'approved',
            ]);

            RequestNomor::where('kloter_id', $kloter->id)
                ->where('nomor_urut', $requestNomor->nomor_urut)
                ->where('id', '!=', $requestNomor->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                ]);

            return response()->json(
                $slot->load('peserta', 'jadwalPembayaran')
            );
        });
    }

    /**
     * ADMIN — batalkan/hapus request yang masih pending.
     */
    public function destroy(RequestNomor $requestNomor)
    {
        if ($requestNomor->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya request berstatus pending yang bisa dihapus.',
            ], 422);
        }

        $requestNomor->delete();

        return response()->json([
            'message' => 'Request dibatalkan.',
        ]);
    }

    protected function generateJadwalPembayaran(
        KloterPeserta $slot,
        Kloter $kloter
    ): void {
        $tanggal = Carbon::parse($kloter->tanggal_mulai);

        for (
            $siklus = 0;
            $siklus < $kloter->jumlah_peserta_maks;
            $siklus++
        ) {
            /*
             * Contoh:
             * mulai 1 Oktober
             * durasi 5 hari
             *
             * siklus 0 => 5 Oktober
             * siklus 1 => 10 Oktober
             * siklus 2 => 15 Oktober
             *
             * Periode pembayaran:
             * 1–5 Oktober
             * 6–10 Oktober
             * 11–15 Oktober
             */
            $jatuhTempo = $tanggal->copy()->addDays(
                (($siklus + 1) * $kloter->durasi_pembayaran_hari) - 1
            );

            JadwalPembayaran::create([
                'kloter_peserta_id' => $slot->id,
                'tanggal_jatuh_tempo' => $jatuhTempo->toDateString(),
                'status_bayar' => 'belum',
            ]);
        }
    }
}