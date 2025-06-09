<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        
        $password = $user->password_plaintext ?? ''; // Contoh saja
        
        return view('profile.profile', compact('user', 'password'));
    }
}
