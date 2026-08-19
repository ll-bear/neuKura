<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        \Log::debug("login");
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        \Log::debug('store called', $credentials);

        if (Auth::attempt($credentials)) {
            \Log::debug("login success");
            $request->session()->regenerate();
            return redirect()->intended('/bookmarks');
        }

        \Log::debug('attempt failed');

        return back()->withErrors([
            'email' => '認証情報が一致しません。',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
