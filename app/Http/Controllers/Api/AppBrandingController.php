<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppBrandingController extends Controller
{
    public function index()
    {
        $branding = AppBranding::first();

        if (!$branding) {
            $branding = AppBranding::create([
                'app_name' => 'Arisan',
                'logo_path' => null,
                'primary_color' => '#0d41e1',
                'sidebar_color' => '#004A7C',
                'accent_color' => '#ffc300',
            ]);
        }

        return response()->json($branding);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'primary_color' => ['required', 'string', 'max:20'],
            'sidebar_color' => ['required', 'string', 'max:20'],
            'accent_color' => ['required', 'string', 'max:20'],
        ]);

        $branding = AppBranding::first();

        if (!$branding) {
            $branding = AppBranding::create($data);
        } else {
            $branding->update($data);
        }

        return response()->json([
            'message' => 'Branding berhasil diperbarui.',
            'branding' => $branding->fresh(),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $data = $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $branding = AppBranding::first();

        if (!$branding) {
            $branding = AppBranding::create([
                'app_name' => 'Arisan',
                'logo_path' => null,
                'primary_color' => '#0d41e1',
                'sidebar_color' => '#004A7C',
                'accent_color' => '#ffc300',
            ]);
        }

        if ($branding->logo_path) {
            $oldPath = str_replace('/storage/', '', $branding->logo_path);

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('logo')->store('branding', 'public');

        $branding->update([
            'logo_path' => url(Storage::url($path)),
        ]);

        return response()->json([
            'message' => 'Logo berhasil diunggah.',
            'branding' => $branding->fresh(),
        ]);
    }

    public function deleteLogo()
    {
        $branding = AppBranding::first();

        if (!$branding) {
            return response()->json([
                'message' => 'Pengaturan branding belum tersedia.',
            ], 404);
        }

        if ($branding->logo_path) {
            $oldPath = parse_url($branding->logo_path, PHP_URL_PATH);

            $oldPath = str_replace('/storage/', '', $oldPath);

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $branding->update([
            'logo_path' => null,
        ]);

        return response()->json([
            'message' => 'Logo berhasil dihapus.',
            'branding' => $branding->fresh(),
        ]);
    }
}