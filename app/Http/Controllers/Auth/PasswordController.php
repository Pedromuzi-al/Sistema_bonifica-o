<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, AdministrationService $administration): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['bail', 'required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = $administration->changeOwnPassword($request->user(), $validated['current_password'], $validated['password']);
        Auth::guard('web')->setUser($user);
        $request->session()->regenerate();
        $request->session()->put('access_version', (int) $user->access_version);
        $request->session()->forget('auth.password_confirmed_at');

        return back()->with('status', 'password-updated');
    }
}
