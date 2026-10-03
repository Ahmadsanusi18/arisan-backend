<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Tampilkan profil user yang sedang login.
     */
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update nama dan email user yang sedang login.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->fresh(),
        ]);
    }
}