<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman/form login.
     */
    public function showLoginForm()
    {
        // Pastikan Anda punya view di: resources/views/login.blade.php
        return view('auth.login');
    }

    /**
     * Memproses data login yang dikirim dari form.
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Siapkan data kredensial
        //    (Ini adalah kredensial standar Laravel)
        $credentials = [
            'username' => $request->username, // <-- Sudah benar
            'password' => $request->password, // <-- Sudah benar
        ];

        // 3. Coba lakukan login
        //    Auth::attempt() sekarang akan bekerja sempurna
        if (Auth::attempt($credentials)) {
            
            // Jika berhasil, regenerasi session dan arahkan ke dashboard
            $request->session()->regenerate();
            
            // Kita akan buat rute '/dashboard' ini setelah ini
            return redirect()->intended('/dashboard'); 
        }

        // 4. Jika gagal, kembalikan ke form login dengan pesan error
        return back()->withErrors([
            // 'username' ini merujuk ke $errors->first('username') di view Anda
            'username' => 'Login gagal. Periksa kembali username dan password Anda.'
        ]);
    }

    /**
     * Memproses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
