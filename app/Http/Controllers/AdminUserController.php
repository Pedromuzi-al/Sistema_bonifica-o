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
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,blocked,deactivated,unverified,unassigned']]);
        $users = User::withTrashed()->with('roles', 'sector', 'teams')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%')->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->when($request->input('status') === 'active', fn ($query) => $query->whereNull('deleted_at')->whereNull('blocked_at'))
            ->when($request->input('status') === 'blocked', fn ($query) => $query->whereNotNull('blocked_at')->whereNull('deleted_at'))
            ->when($request->input('status') === 'deactivated', fn ($query) => $query->whereNotNull('deleted_at'))
            ->when($request->input('status') === 'unverified', fn ($query) => $query->whereNull('email_verified_at')->whereNull('deleted_at'))
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
            if ($updated->isAvailable()) {
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
        abort_unless($user->isAvailable(), 422, 'Reative a conta antes de reenviar a confirmação.');
        if (! $user->hasVerifiedEmail()) {
            $user->invitation_pending ? $this->sendInvitation($user) : $user->sendEmailVerificationNotification();
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => User::class, 'model_id' => $user->id, 'action' => 'verification_requested']);
        }

        return back()->with('success', 'Se o e-mail estava pendente, uma nova confirmação foi enviada.');
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
