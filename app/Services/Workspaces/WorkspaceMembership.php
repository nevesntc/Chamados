<?php

declare(strict_types=1);

namespace App\Services\Workspaces;

use App\Models\Assignee;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class WorkspaceMembership
{
    public function createPersonal(User $user): Workspace
    {
        return DB::transaction(function () use ($user) {
            $workspace = Workspace::create(['name' => 'Equipe de '.$user->name]);
            $workspace->members()->attach($user->id, ['role' => 'owner']);
            $this->activate($user, $workspace);

            return $workspace;
        });
    }

    public function activate(User $user, Workspace $workspace): void
    {
        if (! $workspace->members()->whereKey($user->id)->exists()) {
            abort(403);
        }

        Assignee::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $user->id],
            ['name' => $user->name],
        );
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    }
}
