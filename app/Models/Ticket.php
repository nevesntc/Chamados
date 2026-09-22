<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Ticket extends Model
{
    protected $fillable = ['title', 'description', 'priority', 'status', 'assignee_id', 'workspace_id'];

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where('workspace_id', Auth::user()?->current_workspace_id)
            ->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    protected function casts(): array
    {
        return ['priority' => TicketPriority::class, 'status' => TicketStatus::class];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Assignee::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::activeValues());
    }
}
