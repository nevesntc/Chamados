<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignee extends Model
{
    protected $fillable = ['name', 'workspace_id', 'user_id', 'deactivated_at'];

    protected function casts(): array
    {
        return ['deactivated_at' => 'datetime'];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** Somente quem continua na equipe pode receber novos chamados. */
    public function scopeAssignable(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }
}
