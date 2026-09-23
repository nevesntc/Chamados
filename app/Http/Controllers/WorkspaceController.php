<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Assignee;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspaces\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $workspaceId = $request->user()->current_workspace_id;
        $tickets = Ticket::query()->where('workspace_id', $workspaceId);

        return Inertia::render('Workspace/Dashboard', [
            'summary' => [
                'total' => (clone $tickets)->count(),
                'active' => (clone $tickets)->active()->count(),
                'completed' => (clone $tickets)->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])->count(),
                'team' => Assignee::where('workspace_id', $workspaceId)->count(),
            ],
            'recent' => $tickets->with('assignee:id,name')->latest()->limit(5)->get(['id', 'title', 'status', 'assignee_id', 'created_at']),
        ]);
    }

    public function team(Request $request, WorkspaceMembership $membership): Response
    {
        $workspace = $request->user()->currentWorkspace;

        return Inertia::render('Workspace/Team', [
            'workspaceName' => $workspace->name,
            'people' => Assignee::where('workspace_id', $workspace->id)->assignable()
                ->withCount(['tickets as active_count' => fn ($q) => $q->active()])
                ->orderBy('id')->get(['id', 'name', 'user_id']),
            'workspaces' => $request->user()->workspaces()->orderBy('name')->get(['workspaces.id', 'name']),
            'isOwner' => $membership->isOwner($request->user(), $workspace),
            'currentUserId' => $request->user()->id,
            'ownerId' => (int) $workspace->members()->wherePivot('role', 'owner')->value('users.id'),
        ]);
    }

    public function rename(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        abort_unless($membership->isOwner($request->user(), $workspace), 403);
        $workspace->update($request->validate(['name' => ['required', 'string', 'max:120']]));

        return to_route('workspace.team')->with('success', 'Nome da equipe atualizado.');
    }

    public function removeMember(Request $request, User $member, WorkspaceMembership $membership): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        abort_unless($membership->isOwner($request->user(), $workspace), 403);
        abort_unless($workspace->members()->whereKey($member->id)->exists(), 404);
        $membership->remove($member, $workspace);

        return to_route('workspace.team')->with('success', $member->name.' saiu da equipe. Os chamados já atendidos continuam no histórico.');
    }

    public function leave(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $membership->remove($request->user(), $request->user()->currentWorkspace);

        return to_route('workspace.dashboard')->with('success', 'Você saiu da equipe.');
    }

    public function invite(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        abort_unless($membership->isOwner($request->user(), $workspace), 403);
        $code = Str::random(48);
        DB::transaction(function () use ($workspace, $code) {
            DB::table('workspace_invites')->where('workspace_id', $workspace->id)->delete();
            DB::table('workspace_invites')->insert([
                'workspace_id' => $workspace->id,
                'token_hash' => hash('sha256', $code),
                'expires_at' => now()->addDays(7),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return to_route('workspace.team')->with('invite_code', $code)
            ->with('success', 'Convite criado. Copie o código agora; ele será mostrado apenas uma vez.');
    }

    public function join(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:48']]);
        DB::transaction(function () use ($request, $membership, $data) {
            $invite = DB::table('workspace_invites')->where('token_hash', hash('sha256', $data['code']))
                ->where('expires_at', '>', now())->lockForUpdate()->first();
            if (! $invite) {
                throw ValidationException::withMessages(['code' => 'Código inválido ou expirado.']);
            }
            $workspace = Workspace::findOrFail($invite->workspace_id);
            if (! $workspace->members()->whereKey($request->user()->id)->exists()) {
                $workspace->members()->attach($request->user()->id, ['role' => 'member']);
            }
            $membership->activate($request->user(), $workspace);
        });

        return to_route('workspace.team')->with('success', 'Você entrou na equipe.');
    }

    public function switch(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $data = $request->validate(['workspace_id' => ['required', 'integer']]);
        $workspace = $request->user()->workspaces()->findOrFail($data['workspace_id']);
        $membership->activate($request->user(), $workspace);

        return to_route('workspace.dashboard');
    }

    public function profile(Request $request): Response
    {
        return Inertia::render('Workspace/Profile', ['account' => $request->user()->only('name', 'email')]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update($data);
            Assignee::where('user_id', $request->user()->id)->update(['name' => $data['name']]);
        });

        return to_route('workspace.profile')->with('success', 'Perfil atualizado.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return to_route('workspace.profile')->with('success', 'Senha alterada.');
    }
}
