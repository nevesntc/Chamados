<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = ['title', 'description', 'priority', 'status', 'assignee_id'];

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
