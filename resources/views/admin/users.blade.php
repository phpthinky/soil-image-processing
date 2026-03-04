@extends('layouts.app')
@section('title', 'User Management')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h2>User Management</h2>
        <p class="lead">Manage system users and their permissions.</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row mt-4">
    {{-- Add / Edit User Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>{{ $editUser ? 'Edit User' : 'Add New User' }}</h5>
            </div>
            <div class="card-body">
                @if($editUser)
                <form method="POST" action="{{ route('admin.users.update', $editUser) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="user_id" value="{{ $editUser->id }}">
                @else
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                @endif
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="{{ $editUser ? $editUser->username : old('username') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ $editUser ? $editUser->email : old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Type</label>
                        <select class="form-control" name="user_type" required>
                            @foreach(['farmer','professional','admin'] as $type)
                            <option value="{{ $type }}" {{ ($editUser && $editUser->user_type === $type) ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password {{ $editUser ? '(leave blank to keep current)' : '*' }}</label>
                        <input type="password" class="form-control" name="password" {{ !$editUser ? 'required' : '' }} minlength="6">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn {{ $editUser ? 'btn-primary' : 'btn-success' }}">
                            {{ $editUser ? 'Update User' : 'Add User' }}
                        </button>
                        @if($editUser)
                        <a href="{{ route('admin.users') }}" class="btn btn-secondary">Cancel</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Users List --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>All Users</h5></div>
            <div class="card-body">
                @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Email</th><th>User Type</th><th>Registered</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    {{ $user->username }}
                                    @if($user->id === auth()->id())
                                        <span class="badge bg-primary">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->user_type === 'admin' ? 'danger' : ($user->user_type === 'professional' ? 'warning' : 'success') }}">
                                        {{ ucfirst($user->user_type) }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.users', ['edit_id' => $user->id]) }}" class="btn btn-outline-primary">Edit</a>
                                        @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                                        </form>
                                        @else
                                        <span class="btn btn-outline-secondary disabled">Current user</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p>No users found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
