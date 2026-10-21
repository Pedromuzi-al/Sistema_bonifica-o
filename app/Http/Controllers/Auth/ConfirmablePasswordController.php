<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(Request $request): View
    {
        $data = $request->validate(['return_to' => ['nullable', 'in:profile,idea,admin_user'], 'user_id' => ['required_if:return_to,admin_user', 'nullable', 'integer'], 'idea_id' => ['required_if:return_to,idea', 'nullable', 'integer']]);
        if (($data['return_to'] ?? null) === 'profile') {
            $request->session()->put('url.intended', route('profile.edit'));
        } elseif (($data['return_to'] ?? null) === 'admin_user') {
            $this->authorize('manage-admin');
            $target = User::findOrFail($data['user_id']);
            $request->session()->put('url.intended', route('admin.users.edit', $target));
        } elseif (($data['return_to'] ?? null) === 'idea') {
            $idea = Idea::findOrFail($data['idea_id']);
            $this->authorize('view', $idea);
            $request->session()->put('url.intended', route('ideas.show', $idea));
        }

        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
