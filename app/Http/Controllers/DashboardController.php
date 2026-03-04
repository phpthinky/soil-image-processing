<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Models\User;
use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function user()
    {
        $user          = Auth::user();
        $sampleCount   = $user->soilSamples()->count();
        $recentSamples = $user->soilSamples()->latest()->limit(5)->get();

        return view('dashboard.user', compact('sampleCount', 'recentSamples'));
    }

    public function admin()
    {
        $usersCount     = User::count();
        $samplesCount   = SoilSample::count();
        $cropsCount     = Crop::count();
        $recentSamples  = SoilSample::with('user')->latest()->limit(5)->get();

        return view('dashboard.admin', compact('usersCount', 'samplesCount', 'cropsCount', 'recentSamples'));
    }
}
