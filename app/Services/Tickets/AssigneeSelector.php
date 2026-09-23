<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use App\Models\Assignee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AssigneeSelector
{
    /** Selection must run inside the same write transaction as persistence. */
    public function select(int $workspaceId, ?int $excludingTicketId = null): int
    {
        $assignee = Assignee::query()->where('workspace_id', $workspaceId)->assignable()
            ->withCount(['tickets as active_count' => fn (Builder $query) => $query->active()
                ->when($excludingTicketId, fn (Builder $query) => $query->where('id', '!=', $excludingTicketId))])
            ->orderBy('active_count')->orderBy('id')->first();
        if (! $assignee) {
            throw ValidationException::withMessages([
                'assignment_mode' => 'Nenhum responsável disponível. Configure os responsáveis antes de abrir um chamado.',
            ]);
        }

        return $assignee->id;
    }
}
