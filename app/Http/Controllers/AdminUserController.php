<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserRequest;
use App\Models\AuditLog;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use App\Services\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,blocked,deactivated,unverified,unassigned,pending']]);
        $users = User::withTrashed()->with('roles.permissions', 'permissions', 'sector', 'teams')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%')->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->when($request->input('status') === 'active', fn ($query) => $query->whereNull('deleted_at')->whereNull('blocked_at'))
            ->when($request->input('status') === 'blocked', fn ($query) => $query->whereNotNull('blocked_at')->whereNull('deleted_at'))
            ->when($request->input('status') === 'deactivated', fn ($query) => $query->whereNotNull('deleted_at'))
            ->when($request->input('status') === 'unverified', fn ($query) => $query->whereNull('email_verified_at')->whereNull('deleted_at'))
            ->when($request->input('status') === 'pending', fn ($query) => $query->where('invitation_pending', true)->whereNull('deleted_at'))
            ->when($request->input('status') === 'unassigned', fn ($query) => $query->doesntHave('roles')->whereNull('deleted_at'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('manage-admin');

        return $this->form(new User);
    }

    public function edit(User $user): View
    {
        $this->authorize('manage-admin');

        return $this->form($user);
    }

    private function form(User $user): View
    {
        $sectors = Sector::where('is_active', true)->orWhere('id', $user->sector_id)->orderBy('name')->get();
        $teams = Team::where('is_active', true)->orWhereHas('users', fn ($query) => $query->where('users.id', $user->id ?? 0))->orderBy('name')->get();
        $roles = Role::where('guard_name', 'web')->get();
        $permissions = Permission::where('guard_name', 'web')->get();

        return view('admin.users.form', compact('user', 'sectors', 'teams', 'roles', 'permissions'));
    }

    public function store(AdminUserRequest $request, AdministrationService $administration): RedirectResponse
    {
        $user = $administration->createUser($request->user(), $request->validated());
        if (config('innovation.invitation_delivery') === 'manual') {
            return redirect()->route('admin.users.edit', $user)->with('success', 'Pessoa cadastrada. Gere o link de primeiro acesso para o titular definir a própria senha.');
        }
        $this->sendInvitation($user);

        return redirect()->route('admin.users.edit', $user)->with('success', 'Pessoa cadastrada. Um convite foi enviado para definir a senha e confirmar o e-mail.');
    }

    public function update(AdminUserRequest $request, User $user, AdministrationService $administration): RedirectResponse
    {
        $oldEmail = $user->email;
        $updated = $administration->updateUser($request->user(), $user, $request->validated());
        if ($updated->id === $request->user()->id) {
            $request->session()->put('access_version', (int) $updated->access_version);
        }
        if ($oldEmail !== $updated->email) {
            if ($updated->isAvailable() && config('innovation.invitation_delivery') === 'email') {
                $updated->invitation_pending ? $this->sendInvitation($updated) : $updated->sendEmailVerificationNotification();
            }
        }

        return redirect()->route('admin.users.edit', $updated)->with('success', 'Dados e acesso atualizados.');
    }

    public function block(Request $request, User $user, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $administration->block($request->user(), $user, $data['reason']);

        return back()->with('success', 'Conta bloqueada e sessões revogadas. O histórico foi preservado.');
    }

    public function reactivate(Request $request, User $user, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $administration->reactivate($request->user(), $user, $data['reason']);

        return back()->with('success', 'Conta reativada. A pessoa deverá entrar novamente.');
    }

    public function resendVerification(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-admin');
        abort_unless(config('innovation.invitation_delivery') === 'email', 422, 'Use a geração de link individual nesta versão.');
        abort_unless($user->isAvailable(), 422, 'Reative a conta antes de reenviar a confirmação.');
        if (! $user->hasVerifiedEmail()) {
            $user->invitation_pending ? $this->sendInvitation($user) : $user->sendEmailVerificationNotification();
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => User::class, 'model_id' => $user->id, 'action' => 'verification_requested']);
        }

        return back()->with('success', 'Se o e-mail estava pendente, uma nova confirmação foi enviada.');
    }

    public function resetPassword(Request $request, User $user, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        abort_unless(config('innovation.invitation_delivery') === 'manual', 404);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'Informe a nova senha da pessoa.',
            'password.confirmed' => 'A confirmação deve ser igual à nova senha.',
        ]);
        $administration->resetPassword($request->user(), $user, $data['password']);

        if ($user->id === $request->user()->id) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Senha redefinida. Entre novamente com a nova senha.');
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Senha redefinida. As sessões anteriores foram encerradas. A pessoa já pode entrar com a nova senha.');
    }

    public function accessLink(Request $request, User $user, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        abort_unless(config('innovation.invitation_delivery') === 'manual', 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $url = DB::transaction(function () use ($request, $user, $administration, $data) {
            $administration->lockAccounts($request->user());
            Gate::forUser(User::findOrFail($request->user()->id))->authorize('manage-admin');
            $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->isAvailable(), 422, 'Reative a conta antes de gerar um link de acesso.');
            abort_unless($current->invitation_pending, 422, 'Use a redefinição de senha para recuperar o acesso desta pessoa.');
            $token = Password::broker()->createToken($current);
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => User::class, 'model_id' => $current->id, 'action' => 'manual_access_link_created', 'details' => ['purpose' => $current->invitation_pending ? 'first_access' : 'recovery', 'reason' => $data['reason'], 'expires_in_minutes' => config('auth.passwords.users.expire')]]);

            return route('password.reset', ['token' => $token, 'email' => $current->email]);
        }, 3);

        return redirect()->route('admin.users.edit', $user)->with('access_link', $url)->with('access_link_user_id', $user->id)->with('success', 'Link individual gerado. Entregue somente ao titular após conferir a identidade. O link anterior foi invalidado.');
    }

    private function sendInvitation(User $user): void
    {
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['email' => 'Os dados foram salvos, mas o convite não pôde ser enviado. Abra a gestão desta pessoa e reenvie o convite após conferir a configuração de e-mail.']);
        }
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => 'Os dados foram salvos. Não foi enviado outro convite: '.__($status)]);
        }
    }
}
