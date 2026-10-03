<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index()
    {
        $pengaturan = Pengaturan::first();

        if (!$pengaturan) {
            $pengaturan = Pengaturan::create([
                'jam_kirim_pengingat_peserta' => '09:00:00',
                'template_pesan_pengingat' =>
                    'Halo {nama_peserta}, pengingat pembayaran arisan *{nama_kloter}* jatuh tempo {keterangan_jatuh_tempo} ({tanggal_jatuh_tempo}). Nominal pembayaran: *{nominal_pembayaran}*. Mohon segera dibayar ya 🙏',
            ]);
        }

        return response()->json($pengaturan);
    }

    public function update(Request $request, Pengaturan $pengaturan)
    {
        $data = $request->validate([
            'jam_kirim_pengingat_peserta' => 'required|date_format:H:i',
            'template_pesan_pengingat' => 'required|string|max:5000',
        ]);

        $pengaturan->update($data);

        return response()->json($pengaturan);
    }
}