<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/
use App\Http\Controllers\Api\AnggotaController;
use App\Http\Controllers\Api\KloterController;
use App\Http\Controllers\Api\RequestNomorController;
use App\Http\Controllers\Api\KloterPesertaController;
use App\Http\Controllers\Api\JadwalPembayaranController;
use App\Http\Controllers\Api\PengaturanController;
use App\Http\Controllers\Api\SidebarMenuController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\AppBrandingController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\SystemSettingController;

/*
|--------------------------------------------------------------------------
| PUBLIC API
|--------------------------------------------------------------------------
|
| Route di bagian ini tidak menggunakan Sanctum stateful.
| Tidak membutuhkan session/login admin.
|
*/

Route::get(
    '/publik/kloter/{kode_qr}',
    [KloterController::class, 'showByQr']
);

Route::post(
    '/publik/kloter/{kode_qr}/request-nomor',
    [RequestNomorController::class, 'store']
);

/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
|
| Semua endpoint admin menggunakan:
|
| admin-api
| auth:sanctum
|
| Jadi session Sanctum hanya dipakai oleh aplikasi admin.
|
*/

Route::middleware(['admin-api', 'auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Kloter
    |--------------------------------------------------------------------------
    */

    Route::apiResource('kloter', KloterController::class);

    Route::get(
        '/kloter/{kloter}/qr-image',
        [KloterController::class, 'qrImage']
    );

    /*
    |--------------------------------------------------------------------------
    | Request Nomor
    |--------------------------------------------------------------------------
    */

    Route::apiResource('request-nomor', RequestNomorController::class)
        ->only(['index', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Kloter Peserta
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'kloter-peserta',
        KloterPesertaController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Jadwal Pembayaran
    |--------------------------------------------------------------------------
    */

    Route::apiResource('jadwal-pembayaran', JadwalPembayaranController::class)
        ->only(['index', 'update']);

    Route::post(
        '/jadwal-pembayaran/{jadwalPembayaran}/kirim-ulang',
        [JadwalPembayaranController::class, 'kirimUlang']
    );
    
    /*
    |-------------------------------------------------------------------------
    | Anggota
    |-------------------------------------------------------------------------
    */
    Route::get(
        '/anggota',
        [AnggotaController::class, 'index']
    );
        /*
    |--------------------------------------------------------------------------
    | Pengaturan
    |--------------------------------------------------------------------------
    */

    Route::apiResource('pengaturan', PengaturanController::class)
        ->only(['index', 'update']);

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/branding',
        [AppBrandingController::class, 'index']
    );

    Route::put(
        '/branding',
        [AppBrandingController::class, 'update']
    );

    Route::post(
        '/branding/logo',
        [AppBrandingController::class, 'uploadLogo']
    );

    Route::delete(
        '/branding/logo',
        [AppBrandingController::class, 'deleteLogo']
    );

    /*
    |--------------------------------------------------------------------------
    | Profil
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profil',
        [ProfileController::class, 'show']
    );

    Route::put(
        '/profil',
        [ProfileController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | Keamanan
    |--------------------------------------------------------------------------
    */

    Route::put(
        '/keamanan/password',
        [SecurityController::class, 'updatePassword']
    );

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/notification-settings',
        [NotificationSettingController::class, 'index']
    );

    Route::put(
        '/notification-settings',
        [NotificationSettingController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/system-settings',
        [SystemSettingController::class, 'index']
    );

    Route::put(
        '/system-settings/timezone',
        [SystemSettingController::class, 'updateTimezone']
    );

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/sidebar-menus',
        [SidebarMenuController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | Superadmin
    |--------------------------------------------------------------------------
    */

    Route::middleware('superadmin')->group(function () {

        Route::get(
            '/superadmin-test',
            function (Request $request) {
                return response()->json([
                    'message' => 'Akses superadmin berhasil.',
                    'user' => $request->user(),
                ]);
            }
        );

        Route::post(
            '/sidebar-menus',
            [SidebarMenuController::class, 'store']
        );

        Route::get(
            '/sidebar-menus/{sidebarMenu}',
            [SidebarMenuController::class, 'show']
        );

        Route::put(
            '/sidebar-menus/{sidebarMenu}',
            [SidebarMenuController::class, 'update']
        );

        Route::patch(
            '/sidebar-menus/{sidebarMenu}',
            [SidebarMenuController::class, 'update']
        );

        Route::delete(
            '/sidebar-menus/{sidebarMenu}',
            [SidebarMenuController::class, 'destroy']
        );
    });
});