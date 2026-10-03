<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SidebarMenu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SidebarMenuController extends Controller
{
    /**
     * Daftar menu yang bisa dibaca sidebar.
     *
     * Admin biasa:
     * - hanya menu aktif
     * - access = all
     *
     * Superadmin:
     * - bisa melihat semua menu jika ?manage=1
     * - tanpa ?manage=1 tetap hanya menu yang aktif
     */
    public function index(Request $request)
    {
        $query = SidebarMenu::query();

        $isSuperadmin = $request->user()?->role === 'superadmin';
        $isManageMode = $request->boolean('manage');

        if (!($isSuperadmin && $isManageMode)) {
            $query
                ->where('is_active', true)
                ->where(function ($q) use ($isSuperadmin) {
                    $q->where('access', 'all');

                    if ($isSuperadmin) {
                        $q->orWhere('access', 'superadmin');
                    }
                });
        }

        $menus = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($menus);
    }

    /**
     * Membuat menu baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:sidebar_menus,slug',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'template' => [
                'required',
                'string',
                'max:50',
            ],

            'access' => [
                'required',
                Rule::in(['all', 'superadmin']),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $menu = SidebarMenu::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'] ?? null,
            'template' => $data['template'],
            'access' => $data['access'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($menu, 201);
    }

    /**
     * Detail satu menu.
     */
    public function show(SidebarMenu $sidebarMenu)
    {
        return response()->json($sidebarMenu);
    }

    /**
     * Update menu.
     */
    public function update(Request $request, SidebarMenu $sidebarMenu)
    {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('sidebar_menus', 'slug')
                    ->ignore($sidebarMenu->id),
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'template' => [
                'sometimes',
                'required',
                'string',
                'max:50',
            ],

            'access' => [
                'sometimes',
                'required',
                Rule::in(['all', 'superadmin']),
            ],

            'sort_order' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ]);

        $sidebarMenu->update($data);

        return response()->json(
            $sidebarMenu->fresh()
        );
    }

    /**
     * Hapus menu.
     */
    public function destroy(SidebarMenu $sidebarMenu)
    {
        $sidebarMenu->delete();

        return response()->json([
            'message' => 'Menu sidebar berhasil dihapus.',
        ]);
    }
}