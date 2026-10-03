<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPembayaran;
use App\Models\LogPengingat;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JadwalPembayaranController extends Controller
{
    public function index(Request $request)
    {
        $query = JadwalPembayaran::with(
            'kloterPeserta.peserta',
            'kloterPeserta.kloter'
        );

        if ($request->filled('kloter_id')) {
            $query->whereHas(
                'kloterPeserta',
                fn ($q) => $q->where('kloter_id', $request->kloter_id)
            );
        }

        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        return response()->json(
            $query->orderBy('tanggal_jatuh_tempo')->get()
        );
    }

    public function update(
        Request $request,
        JadwalPembayaran $jadwalPembayaran
    ) {
        $data = $request->validate([
            'status_bayar' => 'required|in:sudah,belum',
        ]);

        $jadwalPembayaran->update($data);

        return response()->json($jadwalPembayaran);
    }

    public function kirimUlang(
        Request $request,
        JadwalPembayaran $jadwalPembayaran,
        WhatsappService $wa
    ) {
        $data = $request->validate([
            'jenis' => 'required|in:H-1,H',
        ]);

        $jadwalPembayaran->load([
            'kloterPeserta.peserta',
            'kloterPeserta.kloter',
        ]);

        if ($jadwalPembayaran->status_bayar !== 'belum') {
            return response()->json([
                'message' => 'Pengingat tidak bisa dikirim karena pembayaran sudah lunas.',
            ], 422);
        }

        $kloterPeserta = $jadwalPembayaran->kloterPeserta;
        $kloter = $kloterPeserta?->kloter;
        $peserta = $kloterPeserta?->peserta;

        if (!$kloter || !$peserta) {
            return response()->json([
                'message' => 'Data kloter atau peserta tidak ditemukan.',
            ], 404);
        }

        $nominalPembayaran = $kloterPeserta->nominal_pembayaran;

        $tanggalJatuhTempo = Carbon::parse(
            $jadwalPembayaran->tanggal_jatuh_tempo
        )->translatedFormat('d M Y');

        $labelWaktu = $data['jenis'] === 'H-1'
            ? 'besok'
            : 'hari ini';

        $pesan = "Halo {$peserta->nama}, pengingat pembayaran arisan *{$kloter->nama}* jatuh tempo {$labelWaktu} ({$tanggalJatuhTempo}). Nominal pembayaran: *Rp" .
            number_format($nominalPembayaran, 0, ',', '.') .
            "*. Mohon segera dibayar ya 🙏";

        $sukses = $wa->kirim(
            $peserta->nomor_wa,
            $pesan
        );

        LogPengingat::create([
            'jadwal_pembayaran_id' => $jadwalPembayaran->id,
            'kloter_id' => $kloter->id,
            'jenis' => $data['jenis'],
            'waktu_kirim' => now(),
            'status_kirim' => $sukses
                ? 'sukses'
                : 'gagal',
        ]);

        if (!$sukses) {
            return response()->json([
                'message' => 'WhatsApp gagal dikirim.',
            ], 500);
        }

        return response()->json([
            'message' => 'Pengingat berhasil dikirim ulang.',
            'data' => [
                'jadwal_pembayaran_id' => $jadwalPembayaran->id,
                'jenis' => $data['jenis'],
                'nominal_pembayaran' => $nominalPembayaran,
                'nomor_wa' => $peserta->nomor_wa,
            ],
        ]);
    }
}