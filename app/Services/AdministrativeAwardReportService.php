<?php

namespace App\Services;

use App\Models\VotingCycle;
use Illuminate\Support\Facades\DB;

class AdministrativeAwardReportService
{
    public function __construct(private CycleReportService $reports) {}

    public function build(VotingCycle $cycle): array
    {
        $report = $this->reports->build($cycle, true);
        $awards = $report['entries']->flatMap(fn ($entry) => $entry['awards']);
        // Allocations are the amounts frozen when recognition was proposed.
        // Never recalculate historical shares using today's contributors.
        $allocations = DB::table('award_allocations')->leftJoin('users', 'users.id', '=', 'award_allocations.user_id')
            ->whereIn('bonus_award_id', $awards->pluck('id'))
            ->orderBy('award_allocations.id')->get(['bonus_award_id', 'users.name', 'amount'])->groupBy('bonus_award_id');
        $legacyNames = DB::table('users')->whereIn('id', $awards->pluck('user_id'))->pluck('name', 'id');
        $rows = collect();
        foreach ($report['entries'] as $entry) {
            if ($entry['awards']->isEmpty()) {
                $rows->push(['entry' => $entry, 'recipient' => '—', 'amount' => null, 'state' => 'Sem bonificação']);
            }
            foreach ($entry['awards'] as $award) {
                $recipients = $allocations->get($award->id, collect([(object) ['name' => $legacyNames->get($award->user_id), 'amount' => $award->award_amount]]));
                $state = $award->rejected_at ? 'Não aprovada' : ($award->published_at ? 'Divulgada' : ($award->approved_at ? 'Aprovada' : 'Proposta'));
                foreach ($recipients as $recipient) {
                    $rows->push(['entry' => $entry, 'recipient' => $recipient->name ?? 'Pessoa indisponível', 'amount' => $recipient->amount, 'state' => $state]);
                }
            }
        }
        $maximumVotes = $report['entries']->max('votes');

        return [...$report, 'rows' => $rows, 'most_voted' => $maximumVotes > 0 ? $report['entries']->where('votes', $maximumVotes)->values() : collect()];
    }
}
