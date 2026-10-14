<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\Idea;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function query(Request $request): Builder
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'sector' => ['nullable', 'integer', 'exists:sectors,id'], 'status' => ['nullable', Rule::in(array_keys(Idea::LABELS))], 'category' => ['nullable', 'string', 'max:255']]);

        return Idea::query()->with('result', 'implementationPlan')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('sector'), fn ($q) => $q->where('origin_sector_id', $request->integer('sector')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('publication->category', $request->input('category')));
    }

    public function index(Request $request): View
    {
        $this->authorize('manage-admin');
        $query = $this->query($request);
        $total = (clone $query)->count();
        $implemented = (clone $query)->whereHas('result', fn ($q) => $q->whereNotNull('validated_at'))->count();
        $distribution = (clone $query)->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $durations = (clone $query)->whereHas('implementationPlan', fn ($q) => $q->whereNotNull('completed_at'))->get()->map(fn ($idea) => $idea->created_at->diffInDays($idea->implementationPlan->completed_at));
        $ids = (clone $query)->pluck('id');
        $awards = BonusAward::whereIn('idea_id', $ids)->whereNotNull('approved_at')->get();
        $sectors = Sector::orderBy('name')->get();
        $ideas = $query->latest()->paginate(20)->withQueryString();

        return view('reports.index', compact('total', 'implemented', 'distribution', 'durations', 'awards', 'sectors', 'ideas'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('manage-admin');
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Código', 'Título revisado', 'Status', 'Data de envio', 'Indicador', 'Antes', 'Depois', 'Unidade', 'Diferença', 'Percentual', 'Data da validação'], ';', '"', '');
            foreach ($query->lazyById() as $idea) {
                $measurements = $idea->result?->validated_at ? $idea->result->measurements : [];
                foreach ($measurements ?: [[]] as $measurement) {
                    $row = [$idea->idea_code, $idea->publication['title'] ?? 'Publicação pendente', Idea::LABELS[$idea->status] ?? $idea->status, $idea->created_at->format('d/m/Y'), $measurement['indicator'] ?? '', $measurement['before'] ?? '', $measurement['after'] ?? '', $measurement['unit'] ?? '', $measurement['difference'] ?? '', $measurement['percentage'] ?? '', $idea->result?->validated_at?->format('d/m/Y') ?? ''];
                    fputcsv($stream, array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : $value, $row), ';', '"', '');
                }
            }
            fclose($stream);
        }, 'relatorio-ideias.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function audit(Request $request): View
    {
        abort_unless($request->user()->hasSystemPermission('view_audit_logs'), 403);
        $logs = AuditLog::latest('id')->paginate(30);

        return view('reports.audit', compact('logs'));
    }
}
