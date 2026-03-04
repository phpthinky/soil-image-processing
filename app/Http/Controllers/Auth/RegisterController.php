<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50|unique:users',
            'email'     => 'required|email|max:100|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:farmer,professional',
        ]);

        $user = User::create([
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'user_type' => $request->user_type,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
