<?php

namespace App\Console\Commands;

use App\Models\JadwalPembayaran;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class KirimListTelatBayar extends Command
{
    protected $signature = 'arisan:kirim-list-telat';

    protected $description = 'Kirim daftar peserta yang telat bayar ke WA admin (dijalankan jam 00:01 & 09:00)';

    public function handle(WhatsappService $wa)
    {
        $telat = JadwalPembayaran::with('kloterPeserta.peserta', 'kloterPeserta.kloter')
            ->where('status_bayar', 'belum')
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->get();

        if ($telat->isEmpty()) {
            return;
        }

        $perKloter = $telat->groupBy(fn ($j) => $j->kloterPeserta->kloter->nama);

        $pesan = "📋 *Daftar Peserta Telat Bayar*\n\n";
        foreach ($perKloter as $namaKloter => $items) {
            $pesan .= "*{$namaKloter}*\n";
            foreach ($items as $j) {
                $peserta = $j->kloterPeserta->peserta;
                $pesan .= "- {$peserta->nama} ({$peserta->nomor_wa}) — jatuh tempo {$j->tanggal_jatuh_tempo->format('d M Y')}\n";
            }
            $pesan .= "\n";
        }

        $admins = User::whereNotNull('nomor_wa')->get();

        foreach ($admins as $admin) {
            $wa->kirim($admin->nomor_wa, $pesan);
        }
    }
}
