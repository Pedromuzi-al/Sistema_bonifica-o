<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PrivateDocumentService
{
    public function canReview(User $user): bool
    {
        return $user->can('manage-admin') && $user->hasSystemPermission('view_audit_logs');
    }

    public function review(User $actor, Idea $idea, int $document, array $data): bool
    {
        Gate::forUser($actor)->authorize('manage-admin');
        abort_unless($this->canReview($actor), 403);
        DB::transaction(function () use ($actor, $idea, $document, $data) {
            $record = DB::table('idea_attachments')->where('idea_id', $idea->id)->where('id', $document)->lockForUpdate()->first();
            abort_unless($record && ! $record->deleted_at, 404);
            if ($record->review_status === 'deletion_pending') {
                throw ValidationException::withMessages(['document' => 'Documento já encaminhado para exclusão.']);
            }
            $hold = (bool) ($data['legal_hold'] ?? false);
            if (($hold || $record->legal_hold) && $data['action'] === 'delete') {
                throw ValidationException::withMessages(['document' => 'Retire a preservação obrigatória em uma revisão justificada antes de excluir.']);
            }
            $values = ['review_status' => match ($data['action']) {
                'approve' => 'approved', 'restrict' => 'restricted', 'delete' => 'deletion_pending',
            }, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_notes' => $data['notes'], 'retention_until' => $data['retention_until'] ?? null, 'legal_hold' => $hold, 'updated_at' => now()];
            DB::table('idea_attachments')->where('id', $document)->update($values);
            AuditLog::create(['causer_id' => $actor->id, 'model_type' => Idea::class, 'model_id' => $idea->id, 'action' => 'private_document_reviewed', 'details' => ['document_id' => $document, 'before' => ['review_status' => $record->review_status, 'legal_hold' => (bool) $record->legal_hold, 'retention_until' => $record->retention_until], 'after' => $values]]);
        }, 3);
        if ($data['action'] === 'delete') {
            try {
                return $this->purge($document);
            } catch (\Throwable $exception) {
                report($exception);

                return false;
            }
        }

        return true;
    }

    public function purge(int $document): bool
    {
        // The deletion marker is committed before touching storage. A storage/database
        // failure can safely be retried without making the document downloadable again.
        DB::transaction(function () use ($document) {
            $record = DB::table('idea_attachments')->where('id', $document)->lockForUpdate()->first();
            if (! $record || $record->deleted_at || $record->legal_hold) {
                return;
            }
            $expired = $record->reviewed_at && $record->retention_until && now()->greaterThanOrEqualTo($record->retention_until);
            if ($record->review_status !== 'deletion_pending' && ! $expired) {
                return;
            }
            DB::table('idea_attachments')->where('id', $document)->update(['review_status' => 'deletion_pending', 'updated_at' => now()]);
        }, 3);

        return DB::transaction(function () use ($document) {
            $record = DB::table('idea_attachments')->where('id', $document)->lockForUpdate()->first();
            if (! $record || $record->deleted_at || $record->legal_hold || $record->review_status !== 'deletion_pending') {
                return false;
            }
            $disk = Storage::disk('local');
            if ($disk->exists($record->path) && ! $disk->delete($record->path)) {
                throw new \RuntimeException('Não foi possível remover o documento privado '.$document.'.');
            }
            DB::table('idea_attachments')->where('id', $document)->update(['path' => '', 'review_status' => 'deleted', 'deleted_at' => now(), 'updated_at' => now()]);
            AuditLog::create(['causer_id' => $record->reviewed_by, 'model_type' => Idea::class, 'model_id' => $record->idea_id, 'action' => 'private_document_deleted', 'details' => ['document_id' => $document, 'retention_until' => $record->retention_until]]);

            return true;
        }, 3);
    }
}
