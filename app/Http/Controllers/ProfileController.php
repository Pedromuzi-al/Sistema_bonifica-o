<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'roles' => Role::all(),
            'permissions' => Permission::all(),
            'canManageRoles' => $canManageRoles,
            'isAdminViewingAnotherUser' => $canManageRoles && $targetUser->id !== $request->user()->id,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, ?User $user = null): RedirectResponse
    {
        $targetUser = $user ?? $request->user();

        if ($user && $user->id !== $request->user()->id && ! $request->user()->can('manage-admin')) {
            abort(403, 'Você não tem permissão para alterar este perfil.');
        }

        $targetUser->fill($request->validated());

        if ($targetUser->isDirty('email')) {
            $targetUser->email_verified_at = null;
        }

        $targetUser->save();

        $redirect = $targetUser->id === $request->user()->id
            ? Redirect::route('profile.edit')
            : Redirect::route('profile.edit', ['user' => $targetUser->id]);

        return $redirect->with('status', 'profile-updated');
    }

    public function updatePermissions(Request $request, ?User $user = null): RedirectResponse
    {
        $request->validate([
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $targetUser = $user ?? $request->user();

        if (! Gate::check('manage-admin') && $targetUser->id !== $request->user()->id) {
            return Redirect::route('profile.edit', ['user' => $targetUser->id])->with('error', 'Você não tem permissão para alterar permissões.');
        }

        if (! Gate::check('manage-admin') && $targetUser->id === $request->user()->id) {
            return Redirect::route('profile.edit')->with('error', 'Você não tem permissão para alterar permissões.');
        }

        DB::transaction(function () use ($request, $targetUser) {
            $targetUser->syncRoles($request->input('role') ? [$request->input('role')] : []);
            $targetUser->syncPermissions($request->input('permissions', []));
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => User::class, 'model_id' => $targetUser->id, 'action' => 'permissions_updated', 'details' => $request->only('role', 'permissions')]);
        });

        $redirect = $targetUser->id === $request->user()->id
            ? Redirect::route('profile.edit')
            : Redirect::route('profile.edit', ['user' => $targetUser->id]);

        return $redirect->with('status', 'permissions-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        DB::transaction(function () use ($user) {
            AuditLog::create(['causer_id' => $user->id, 'model_type' => User::class, 'model_id' => $user->id, 'action' => 'account_deactivated', 'details' => ['reason' => 'Solicitação do titular; histórico preservado.']]);
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
