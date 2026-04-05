<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Show the profile edit page.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'phone_num' => 'nullable|string|max:20',
        ]);

        $user->phone_num = $request->phone_num;
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui!');
    }
}
