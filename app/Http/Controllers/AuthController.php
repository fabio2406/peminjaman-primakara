<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function showRegisterForm()
    {
        if (Auth::check()) {
            // Jika sudah login, redirect sesuai dengan role
            if (Auth::user()->role === 'admin') {
                return redirect('/admin/dashboard');
            } elseif (Auth::user()->role === 'peminjam') {
                return redirect('/peminjam/dashboard');
            } elseif (Auth::user()->role === 'penyetuju') {
                return redirect('/penyetuju/dashboard');
            }
        }
    
        return view('register');
        
    }

    public function register(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:15',
        ]);

        // Buat user baru
        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'inactive', // Status otomatis inactive
            'role' => 'peminjam',   // Role otomatis peminjam
        ]);

        // Redirect ke halaman login dengan pesan sukses
        return redirect('/login')->with('success', 'Registration successful! You can now log in.');
    }
    public function showLoginForm()
    {
        if (Auth::check()) {
            // Jika sudah login, redirect sesuai dengan role
            if (Auth::user()->role === 'admin') {
                return redirect('/admin/dashboard');
            } elseif (Auth::user()->role === 'peminjam') {
                return redirect('/peminjam/dashboard');
            } elseif (Auth::user()->role === 'penyetuju') {
                return redirect('/penyetuju/dashboard');
            }
        }
    
        return view('login');
        
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Cek apakah user statusnya aktif atau tidak
            if ($user->status !== 'active') {
                Auth::logout();
                return redirect()->back()->withErrors(['Your account is inactive, please contact the administrator.']);
            }

            // Redirect berdasarkan role user
            if ($user->role === 'admin') {
                return redirect('/admin/dashboard');
            } elseif ($user->role === 'peminjam') {
                return redirect('/peminjam/dashboard');
            } elseif ($user->role === 'penyetuju') {
                return redirect('/penyetuju/dashboard');
            }
        }

        return redirect()->back()->withErrors(['Login failed. Please check your credentials and try again.']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
