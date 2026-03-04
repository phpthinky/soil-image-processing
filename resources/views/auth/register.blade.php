@extends('layouts.app')
@section('title', 'Register')
@section('styles')
<style>
body { background: linear-gradient(135deg, #4caf50, #2e7d32); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.register-card { animation: fadeInDown 1s ease; border-radius: 15px; overflow: hidden; box-shadow: 0px 8px 20px rgba(0,0,0,0.2); }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
.card-header { background: #388e3c; color: #fff; text-align: center; }
.form-control { padding-left: 40px; }
.input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; }
.btn-success { border-radius: 30px; transition: transform 0.2s; }
.btn-success:hover { transform: scale(1.05); }
</style>
@endsection
@section('content')
<center>
<div class="col-md-6 col-lg-5">
    <div class="card register-card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Soil Analyzer Registration</h4>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3 position-relative">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" value="{{ old('username') }}" placeholder="Enter username" required>
                </div>
                <div class="mb-3 position-relative">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Enter email" required>
                </div>
                <div class="mb-3 position-relative">
                    <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                    <select class="form-control" name="user_type" required>
                        <option value="farmer" {{ old('user_type') === 'farmer' ? 'selected' : '' }}>Farmer</option>
                        <option value="professional" {{ old('user_type') === 'professional' ? 'selected' : '' }}>Agricultural Professional</option>
                    </select>
                </div>
                <div class="mb-3 position-relative">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>
                <div class="mb-3 position-relative">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm password" required>
                </div>
                <button type="submit" class="btn btn-success w-100"><i class="fas fa-user-check me-2"></i>Register</button>
            </form>
            <div class="text-center mt-3">
                <p class="mb-0">Already have an account? <a href="{{ route('login') }}" class="text-success fw-bold">Login here</a></p>
            </div>
        </div>
    </div>
</div>
</center>
@endsection
