<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.url', 'http://localhost:3001');
    }

    public function kirim(string $nomor, string $pesan): bool
    {
        $nomor = $this->normalisasiNomor($nomor);

        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/send", [
                'nomor' => $nomor,
                'pesan' => $pesan,
            ]);

            return $response->successful() && ($response->json('success') === true);
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WA: ' . $e->getMessage(), ['nomor' => $nomor]);
            return false;
        }
    }

    public function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        } elseif (!str_starts_with($nomor, '62')) {
            $nomor = '62' . $nomor;
        }

        return $nomor;
    }
}
