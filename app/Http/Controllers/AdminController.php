<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $result = $this->authorizeOrRedirect('manage-admin', null, 'Você não tem permissão para acessar o painel administrativo.');

        if ($result !== true) {
            return $result;
        }

        $stats = [
            'total' => Idea::count(),
            'approved' => Idea::where('status', Idea::STATUS_APPROVED)->count(),
            'implemented' => Idea::where('status', Idea::STATUS_IMPLEMENTED)->count(),
            'completed' => Idea::where('status', Idea::STATUS_COMPLETED)->count(),
        ];

        $ideas = Idea::with('author', 'originSector', 'beneficiarySectors')->latest()->limit(10)->get();
        $sectors = Sector::withCount('ideasAsOrigin')->get();
        $users = User::with('roles', 'permissions')->latest()->get();

        return view('admin.index', compact('stats', 'ideas', 'sectors', 'users'));
    }
}
