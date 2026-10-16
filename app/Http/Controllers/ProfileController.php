<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccessPermissionsRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, ?User $user = null): View
    {
        $targetUser = $user ?? $request->user();
        $canManageRoles = $request->user()->can('manage-admin');

        if ($user && $user->id !== $request->user()->id && ! $canManageRoles) {
            abort(403, 'Você não tem permissão para acessar esse perfil.');
        }

        return view('profile.edit', [
            'user' => $targetUser,
            'roles' => $canManageRoles ? Role::all() : collect(),
            'permissions' => $canManageRoles ? Permission::all() : collect(),
            'canManageRoles' => $canManageRoles,
            'isAdminViewingAnotherUser' => $canManageRoles && $targetUser->id !== $request->user()->id,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, AdministrationService $administration, ?User $user = null): RedirectResponse
    {
        $targetUser = $user ?? $request->user();

        if ($user && $user->id !== $request->user()->id && ! $request->user()->can('manage-admin')) {
            abort(403, 'Você não tem permissão para alterar este perfil.');
        }

        $oldEmail = $targetUser->email;
        $targetUser = $administration->updateUser($request->user(), $targetUser, $request->validated(), true);
        if ($targetUser->id === $request->user()->id) {
            $request->session()->put('access_version', (int) $targetUser->access_version);
        }
        if ($oldEmail !== $targetUser->email) {
            $targetUser->sendEmailVerificationNotification();
        }

        $redirect = $targetUser->id === $request->user()->id
            ? Redirect::route('profile.edit')
            : Redirect::route('profile.edit', ['user' => $targetUser->id]);

        return $redirect->with('status', 'profile-updated');
    }

    public function updatePermissions(AccessPermissionsRequest $request, AdministrationService $administration, ?User $user = null): RedirectResponse
    {
        $targetUser = $user ?? $request->user();

        if (! Gate::check('manage-admin') && $targetUser->id !== $request->user()->id) {
            return Redirect::route('profile.edit', ['user' => $targetUser->id])->with('error', 'Você não tem permissão para alterar permissões.');
        }

        if (! Gate::check('manage-admin') && $targetUser->id === $request->user()->id) {
            return Redirect::route('profile.edit')->with('error', 'Você não tem permissão para alterar permissões.');
        }

        $administration->permissions($request->user(), $targetUser, $request->validated());

        $redirect = $targetUser->id === $request->user()->id
            ? Redirect::route('profile.edit')
            : Redirect::route('profile.edit', ['user' => $targetUser->id]);

        return $redirect->with('status', 'permissions-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, AdministrationService $administration): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $administration->deactivate($user);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
