<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'full_name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:6|confirmed',
        ]);

        if (!empty($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password does not match.']);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->full_name = $validated['full_name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }
}
