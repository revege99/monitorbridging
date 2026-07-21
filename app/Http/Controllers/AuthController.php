<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required']]);
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (!Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Username/email atau password tidak sesuai.'])->onlyInput('login');
        }
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
