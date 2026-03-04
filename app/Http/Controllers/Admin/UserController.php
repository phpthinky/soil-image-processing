<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users    = User::latest()->get();
        $editUser = null;

        if (request('edit_id')) {
            $editUser = User::findOrFail(request('edit_id'));
        }

        return view('admin.users', compact('users', 'editUser'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50|unique:users',
            'email'     => 'required|email|max:100|unique:users',
            'password'  => 'required|string|min:6',
            'user_type' => 'required|in:farmer,professional,admin',
        ]);

        User::create([
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'user_type' => $request->user_type,
        ]);

        return redirect()->route('admin.users')->with('success', 'User added successfully!');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'username'  => 'required|string|max:50|unique:users,username,' . $user->id,
            'email'     => 'required|email|max:100|unique:users,email,' . $user->id,
            'user_type' => 'required|in:farmer,professional,admin',
            'password'  => 'nullable|string|min:6',
        ]);

        $data = [
            'username'  => $request->username,
            'email'     => $request->email,
            'user_type' => $request->user_type,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted successfully!');
    }
}
