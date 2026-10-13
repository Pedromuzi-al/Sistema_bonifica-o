<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Sector;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-admin');

        $stats = [
            'total' => Idea::count(),
            'approved' => Idea::where('status', Idea::STATUS_APPROVED)->count(),
            'implemented' => Idea::where('status', Idea::STATUS_IMPLEMENTED)->count(),
            'completed' => Idea::where('status', Idea::STATUS_COMPLETED)->count(),
        ];

        $ideas = Idea::with('author', 'originSector', 'beneficiarySectors')->latest()->limit(10)->get();
        $sectors = Sector::withCount('ideasAsOrigin')->get();

        return view('admin.index', compact('stats', 'ideas', 'sectors'));
    }
}
