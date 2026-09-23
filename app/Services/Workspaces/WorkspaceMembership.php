<?php

declare(strict_types=1);

namespace App\Services\Workspaces;

use App\Models\Assignee;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        // Quem retorna à equipe volta a receber chamados sem duplicar o responsável.
        Assignee::updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $user->id],
            ['name' => $user->name, 'deactivated_at' => null],
        );
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    }

    public function isOwner(User $user, Workspace $workspace): bool
    {
        return $workspace->members()->whereKey($user->id)->wherePivot('role', 'owner')->exists();
    }

    /**
     * Desliga alguém da equipe preservando os chamados que já atendeu.
     */
    public function remove(User $member, Workspace $workspace): void
    {
        if ($this->isOwner($member, $workspace)) {
            throw ValidationException::withMessages([
                'member' => 'O dono não pode ser removido nem sair do espaço que criou.',
            ]);
        }

        DB::transaction(function () use ($member, $workspace) {
            $workspace->members()->detach($member->id);
            Assignee::where('workspace_id', $workspace->id)->where('user_id', $member->id)
                ->update(['deactivated_at' => now()]);

            if ($member->current_workspace_id === $workspace->id) {
                $member->forceFill(['current_workspace_id' => $member->workspaces()->value('workspaces.id')])->save();
            }
        });
    }
}
