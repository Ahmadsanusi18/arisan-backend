<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => ['Email atau kata sandi salah.'],
        ]);
    }

    $request->session()->regenerate();

    return response()->noContent();
});

Route::post('/logout', function (Request $request) {
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->noContent();
})->middleware('auth:sanctum');

Route::get('/api/user', fn (Request $request) => $request->user())
    ->middleware('auth:sanctum');

Route::get('/', function () {
    return view('welcome');
});