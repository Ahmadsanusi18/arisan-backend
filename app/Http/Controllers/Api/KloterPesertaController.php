<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPembayaran;
use App\Models\Kloter;
use App\Models\KloterPeserta;
use App\Models\Peserta;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KloterPesertaController extends Controller
{
    public function index(Request $request)
    {
        $query = KloterPeserta::with([
            'peserta',
            'jadwalPembayaran',
            'kloter',
        ]);

        if ($request->filled('kloter_id')) {
            $query->where('kloter_id', $request->kloter_id);
        }

        return response()->json(
            $query->orderBy('nomor_urut')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kloter_id' => 'required|exists:kloters,id',
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:20',
            'nomor_urut' => 'required|integer|min:1',
        ]);

        $kloterCek = Kloter::findOrFail($data['kloter_id']);

        if ($data['nomor_urut'] > $kloterCek->jumlah_peserta_maks) {
            return response()->json([
                'message' => 'Nomor urut melebihi jumlah peserta maksimal kloter ini.',
            ], 422);
        }

        return DB::transaction(function () use ($data) {
            $kloter = Kloter::where('id', $data['kloter_id'])
                ->lockForUpdate()
                ->first();

            $sudahFix = KloterPeserta::where('kloter_id', $kloter->id)
                ->where('nomor_urut', $data['nomor_urut'])
                ->exists();

            if ($sudahFix) {
                return response()->json([
                    'message' => 'Nomor ini sudah fix/terisi.',
                ], 422);
            }

            $nomorNormal = app(WhatsappService::class)
                ->normalisasiNomor($data['nomor_wa']);

            $peserta = Peserta::create([
                'nama' => $data['nama'],
                'nomor_wa' => $nomorNormal,
            ]);

            $slot = KloterPeserta::create([
                'kloter_id' => $kloter->id,
                'peserta_id' => $peserta->id,
                'nomor_urut' => $data['nomor_urut'],
                'sumber' => 'manual',
            ]);

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
                 * Rumus:
                 * tanggal mulai + ((siklus + 1) * durasi) - 1 hari
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

            return response()->json(
                $slot->load('peserta', 'jadwalPembayaran'),
                201
            );
        });
    }

    public function show(KloterPeserta $kloterPeserta)
    {
        return response()->json(
            $kloterPeserta->load(
                'peserta',
                'jadwalPembayaran'
            )
        );
    }

    public function update(Request $request, KloterPeserta $kloterPeserta)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:20',
        ]);

        $kloterPeserta->load('peserta');

        if (!$kloterPeserta->peserta) {
            return response()->json([
                'message' => 'Data peserta tidak ditemukan.',
            ], 404);
        }

        $nomorNormal = app(WhatsappService::class)
            ->normalisasiNomor($data['nomor_wa']);

        $kloterPeserta->peserta->update([
            'nama' => $data['nama'],
            'nomor_wa' => $nomorNormal,
        ]);

        return response()->json(
            $kloterPeserta->fresh()->load(
                'peserta',
                'jadwalPembayaran'
            )
        );
    }

    public function destroy(KloterPeserta $kloterPeserta)
    {
        return DB::transaction(function () use ($kloterPeserta) {
            $pesertaId = $kloterPeserta->peserta_id;

            JadwalPembayaran::where(
                'kloter_peserta_id',
                $kloterPeserta->id
            )->delete();

            $kloterPeserta->delete();

            if ($pesertaId) {
                Peserta::where('id', $pesertaId)->delete();
            }

            return response()->json([
                'message' => 'Slot peserta beserta jadwal pembayarannya berhasil dihapus.',
            ]);
        });
    }
}