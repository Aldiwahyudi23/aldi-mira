<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KodeLoginController extends Controller
{
     public function showLoginForm()
    {
        return inertia('Auth/KodeLogin');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Cari user berdasarkan password yang cocok (tanpa email)
        $user = User::all()->first(function ($u) use ($request) {
            return Hash::check($request->password, $u->password);
        });

        if (! $user) {
            return back()->withErrors(['password' => 'Kode tidak valid 😢']);
        }

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
