<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'app_name' => config('app.name'),
            'app_environment' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'app_timezone' => config('app.timezone'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ]);
    }

    public function updateTimezone(Request $request)
    {
        $data = $request->validate([
            'timezone' => [
                'required',
                'string',
                'timezone',
            ],
        ]);

        /*
         * Untuk tahap ini timezone hanya divalidasi dan dikembalikan.
         * APP_TIMEZONE tetap menjadi sumber konfigurasi utama aplikasi.
         */
        return response()->json([
            'message' => 'Timezone berhasil divalidasi.',
            'timezone' => $data['timezone'],
        ]);
    }
}