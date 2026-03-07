<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.profile.index');
    }

    public function update(Request $request)
    {
        $user = User::find(auth()->id());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->save();

        // Redirect back to where user came from (settings or profile page)
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/settings')) {
            return redirect()->route('admin.settings.index')
                ->with('success', 'Profile updated successfully!');
        }

        return redirect()->route('admin.profile.index')
            ->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $user = User::find(auth()->id());

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        // Model has 'hashed' cast on password, so just assign directly
        $user->password = $validated['password'];
        $user->save();

        // Redirect back to where user came from
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/settings')) {
            return redirect()->route('admin.settings.index')
                ->with('success', 'Password changed successfully!');
        }

        return redirect()->route('admin.profile.index')
            ->with('success', 'Password changed successfully!');
    }
}
