<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // (opcional) validación mínima del lado servidor
        // $request->validate(['email' => 'required|email', 'password' => 'required']);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            // Redirige a lo que el usuario quería, o al dashboard
            return redirect()->intended(route('dashboard.index'));
        }

        return back()->with('error', 'Credenciales Incorrectas');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerate();

        // 👈 Después del logout, volvé al login (no al dashboard protegido)
        return redirect()->route('login');
    }
}
