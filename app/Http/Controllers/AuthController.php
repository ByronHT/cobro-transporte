<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // =========================
    // LOGIN ADMINISTRADOR
    // =========================
    public function showLoginForm()
    {
        return view('auth.login'); // Vista: resources/views/auth/login.blade.php
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'admin') {
                return redirect()->intended('/admin'); // Ruta protegida de admin
            }

            // Si no es admin, lo sacamos
            Auth::logout();
            return back()->withErrors(['error' => 'No tienes permisos para entrar como administrador.']);
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas']);
    }

    // =========================
    // LOGIN CLIENTE
    // =========================
    public function showClienteLoginForm()
    {
        return view('auth.logincliente'); // Vista: resources/views/auth/logincliente.blade.php
    }

    public function loginCliente(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'passenger') {
                return redirect()->route('cliente.dashboard'); // Ruta protegida cliente
            }

            // Si no es cliente, lo sacamos
            Auth::logout();
            return back()->withErrors(['error' => 'No tienes permisos para entrar como cliente.']);
        }

        return back()->withErrors(['error' => 'Credenciales inválidas']);
    }

    // =========================
    // LOGOUT
    // =========================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login-admin'); // Redirige al login de admin
    }
}
