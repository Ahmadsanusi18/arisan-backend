<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'Kata sandi saat ini tidak sesuai.',
                ],
            ]);
        }

        if (Hash::check($data['new_password'], $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => [
                    'Kata sandi baru harus berbeda dari kata sandi saat ini.',
                ],
            ]);
        }

        $user->update([
            'password' => $data['new_password'],
        ]);

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}