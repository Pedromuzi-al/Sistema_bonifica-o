<?php

namespace App\Http\Requests;

use App\Models\VotingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CastVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = filter_var($this->input('voting_cycle_id'), FILTER_VALIDATE_INT);
        $cycle = $id ? VotingCycle::find($id) : null;

        return $this->user()->can($cycle?->isSelection() ? 'voteInSelection' : 'vote', $this->route('idea'));
    }

    protected function failedAuthorization(): void
    {
        if ($this->expectsJson()) {
            parent::failedAuthorization();
        }
        throw new HttpResponseException(redirect()->back()->with('permission_error', 'Você não tem permissão para realizar esta ação.'));
    }

    public function rules(): array
    {
        return ['voting_cycle_id' => ['required', 'integer', 'exists:voting_cycles,id'], 'scores' => ['required', 'array', 'min:1'], 'scores.*' => ['nullable', 'integer', 'between:1,5']];
    }
}
