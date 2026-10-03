<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KloterPeserta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $query = KloterPeserta::with([
            'peserta',
            'kloter',
        ]);

        /*
         * Pencarian:
         * - nama peserta
         * - nomor WhatsApp
         * - nama kloter
         */
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('peserta', function ($peserta) use ($search) {
                    $peserta
                        ->where('nama', 'like', "%{$search}%")
                        ->orWhere('nomor_wa', 'like', "%{$search}%");
                })
                ->orWhereHas('kloter', function ($kloter) use ($search) {
                    $kloter->where(
                        'nama',
                        'like',
                        "%{$search}%"
                    );
                });
            });
        }

        $anggota = $query
            ->get()
            ->map(function ($slot) {
                $kloter = $slot->kloter;

                $tanggalMenang = null;

                if (
                    $kloter &&
                    $kloter->tanggal_mulai &&
                    $kloter->durasi_pembayaran_hari &&
                    $slot->nomor_urut
                ) {
                    $tanggalMenang = Carbon::parse(
                        $kloter->tanggal_mulai
                    )->addDays(
                        ($slot->nomor_urut *
                            $kloter->durasi_pembayaran_hari) - 1
                    );
                }

                return [
                    'id' => $slot->id,
                    'peserta_id' => $slot->peserta_id,
                    'kloter_id' => $slot->kloter_id,

                    'nama' => $slot->peserta?->nama,
                    'nomor_wa' => $slot->peserta?->nomor_wa,

                    'kloter' => $kloter?->nama,
                    'status' => $kloter?->status,

                    'nomor_urut' => $slot->nomor_urut,

                    'tanggal_mulai' => $kloter?->tanggal_mulai
                        ? Carbon::parse(
                            $kloter->tanggal_mulai
                        )->toDateString()
                        : null,

                    'tanggal_menang' => $tanggalMenang
                        ? $tanggalMenang->toDateString()
                        : null,
                ];
            })
            ->sort(function ($a, $b) {
                /*
                 * Urutan status:
                 * aktif -> terlambat -> selesai
                 */
                $prioritas = [
                    'aktif' => 1,
                    'terlambat' => 2,
                    'selesai' => 3,
                ];

                $statusA = $prioritas[$a['status'] ?? ''] ?? 99;
                $statusB = $prioritas[$b['status'] ?? ''] ?? 99;

                if ($statusA !== $statusB) {
                    return $statusA <=> $statusB;
                }

                /*
                 * Kloter terbaru lebih atas.
                 */
                return strcmp(
                    $b['tanggal_mulai'] ?? '',
                    $a['tanggal_mulai'] ?? ''
                );
            })
            ->values();

        return response()->json($anggota);
    }
}