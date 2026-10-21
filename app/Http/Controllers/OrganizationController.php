<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectorRequest;
use App\Http\Requests\TeamRequest;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use App\Services\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $teams = Team::with('sector')->withCount('users')->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))->orderBy('name')->paginate(15, ['*'], 'teams_page')->withQueryString();

        return view('admin.organization.index', compact('teams'));
    }

    public function sectors(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,inactive']]);
        $sectors = Sector::withCount(['users' => fn ($q) => $q->withTrashed(), 'ideasAsOrigin'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.organization.sectors', compact('sectors'));
    }

    public function sectorShow(Request $request, Sector $sector): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'membership' => ['nullable', 'in:linked,unlinked,all']]);
        $membership = $request->input('membership') ?: 'linked';
        $users = User::withTrashed()->with('sector')
            ->when($membership === 'linked', fn ($q) => $q->where('sector_id', $sector->id))
            ->when($membership === 'unlinked', fn ($q) => $q->where(fn ($q) => $q->whereNull('sector_id')->orWhere('sector_id', '!=', $sector->id)))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%')->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')->paginate(20)->withQueryString();
        $sector->loadCount(['users' => fn ($q) => $q->withTrashed(), 'ideasAsOrigin']);

        return view('admin.organization.sector-show', compact('sector', 'users', 'membership'));
    }

    public function sectorMember(Request $request, Sector $sector, User $user, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['action' => ['required', 'in:link,unlink']]);
        $administration->sectorMembership($request->user(), $sector, $user, $data['action']);

        return back()->with('success', 'Vínculo com o setor atualizado. Os registros anteriores das ideias foram preservados.');
    }

    public function sectorForm(?Sector $sector = null): View
    {
        $this->authorize('manage-admin');

        return view('admin.organization.sector', ['sector' => $sector ?? new Sector(['is_active' => true])]);
    }

    public function saveSector(SectorRequest $request, AdministrationService $administration, ?Sector $sector = null): RedirectResponse
    {
        $saved = $administration->saveSector($request->user(), $request->validated(), $sector);

        return redirect()->route('admin.sectors.show', $saved)->with('success', 'Setor salvo. Desativar um setor preserva seus registros anteriores.');
    }

    public function teamForm(?Team $team = null): View
    {
        $this->authorize('manage-admin');
        $team ??= new Team(['is_active' => true]);
        $sectors = Sector::where('is_active', true)->orWhere('id', $team->sector_id)->orderBy('name')->get();
        $users = User::withTrashed()->orderBy('name')->get();

        return view('admin.organization.team', compact('team', 'sectors', 'users'));
    }

    public function saveTeam(TeamRequest $request, AdministrationService $administration, ?Team $team = null): RedirectResponse
    {
        $administration->saveTeam($request->user(), $request->validated(), $team);

        return redirect()->route('admin.organization')->with('success', 'Equipe e participantes atualizados.');
    }
}
