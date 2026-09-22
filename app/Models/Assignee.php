<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignee extends Model
{
    protected $fillable = ['name', 'workspace_id', 'user_id'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
