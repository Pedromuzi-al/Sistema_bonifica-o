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
        $sectors = Sector::withCount('users', 'ideasAsOrigin')->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))->orderBy('name')->paginate(15, ['*'], 'sectors_page')->withQueryString();
        $teams = Team::with('sector')->withCount('users')->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))->orderBy('name')->paginate(15, ['*'], 'teams_page')->withQueryString();

        return view('admin.organization.index', compact('sectors', 'teams'));
    }

    public function sectorForm(?Sector $sector = null): View
    {
        $this->authorize('manage-admin');

        return view('admin.organization.sector', ['sector' => $sector ?? new Sector(['is_active' => true])]);
    }

    public function saveSector(SectorRequest $request, AdministrationService $administration, ?Sector $sector = null): RedirectResponse
    {
        $administration->saveSector($request->user(), $request->validated(), $sector);

        return redirect()->route('admin.organization')->with('success', 'Setor salvo. Desativar um setor preserva seus registros anteriores.');
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
