<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Assignee;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function register(string $name, string $email): User
    {
        $this->post('/cadastro', [
            'name' => $name, 'email' => $email,
            'password' => 'Segura1234', 'password_confirmation' => 'Segura1234',
        ])->assertRedirect('/workspace');

        return User::where('email', $email)->firstOrFail();
    }

    /** Devolve dono e membro já reunidos no mesmo espaço, com o membro autenticado. */
    private function teamOfTwo(): array
    {
        $owner = $this->register('Maria', 'maria@example.test');
        $this->post('/workspace/equipe/convites');
        $code = session('invite_code');
        $this->post('/sair');
        $member = $this->register('João', 'joao@example.test');
        $this->post('/workspace/equipe/entrar', ['code' => $code]);

        return [$owner->fresh(), $member->fresh()];
    }

    private function loginAs(User $user): void
    {
        $this->post('/sair');
        $this->post('/entrar', ['email' => $user->email, 'password' => 'Segura1234'])->assertRedirect('/workspace');
    }

    public function test_owner_renames_the_team_and_member_cannot(): void
    {
        [$owner, $member] = $this->teamOfTwo();

        $this->patch('/workspace/equipe', ['name' => 'Suporte Interno'])->assertForbidden();

        $this->loginAs($owner);
        $this->patch('/workspace/equipe', ['name' => ''])->assertSessionHasErrors('name');
        $this->patch('/workspace/equipe', ['name' => 'Suporte Interno'])->assertRedirect('/workspace/equipe');
        $this->assertDatabaseHas('workspaces', ['id' => $owner->current_workspace_id, 'name' => 'Suporte Interno']);
        $this->get('/workspace/equipe')->assertInertia(fn ($page) => $page->where('workspaceName', 'Suporte Interno'));
        $this->assertNotSame($member->current_workspace_id, null);
    }

    public function test_owner_removes_member_who_keeps_history_but_stops_receiving_tickets(): void
    {
        [$owner, $member] = $this->teamOfTwo();
        $workspaceId = $owner->current_workspace_id;
        $memberAssignee = Assignee::where('workspace_id', $workspaceId)->where('user_id', $member->id)->firstOrFail();

        $this->post('/workspace/chamados', [
            'title' => 'Impressora parada', 'description' => 'Chamado atendido pelo João.',
            'priority' => 'high', 'assignment_mode' => 'manual', 'assignee_id' => $memberAssignee->id,
        ])->assertSessionHasNoErrors();
        $ticket = Ticket::latest('id')->firstOrFail();

        $this->loginAs($owner);
        $this->delete('/workspace/equipe/membros/'.$member->id)->assertRedirect('/workspace/equipe');

        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $workspaceId, 'user_id' => $member->id]);
        $this->assertNotNull($memberAssignee->fresh()->deactivated_at);
        $this->assertSame($memberAssignee->id, $ticket->fresh()->assignee_id, 'O chamado já atendido continua nomeando quem o atendeu.');
        $this->assertNotSame($workspaceId, $member->fresh()->current_workspace_id);

        // Sem vínculo, a pessoa some das opções e não entra mais na distribuição.
        $this->get('/workspace/equipe')->assertInertia(fn ($page) => $page->has('people', 1));
        $this->post('/workspace/chamados', [
            'title' => 'Tentativa manual', 'description' => 'Não deve aceitar quem saiu.',
            'priority' => 'low', 'assignment_mode' => 'manual', 'assignee_id' => $memberAssignee->id,
        ])->assertSessionHasErrors('assignee_id');

        $ownerAssignee = Assignee::where('workspace_id', $workspaceId)->where('user_id', $owner->id)->firstOrFail();
        $this->post('/workspace/chamados', [
            'title' => 'Novo automatico', 'description' => 'Deve sobrar para quem ficou.',
            'priority' => 'low', 'assignment_mode' => 'automatic',
        ])->assertSessionHasNoErrors();
        $this->assertSame($ownerAssignee->id, Ticket::latest('id')->firstOrFail()->assignee_id);
    }

    public function test_member_leaves_on_their_own_and_owner_cannot(): void
    {
        [$owner, $member] = $this->teamOfTwo();
        $workspaceId = $owner->current_workspace_id;

        $this->post('/workspace/equipe/sair')->assertRedirect('/workspace');
        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $workspaceId, 'user_id' => $member->id]);
        $this->assertNotSame($workspaceId, $member->fresh()->current_workspace_id);
        $this->get('/workspace/chamados/1')->assertNotFound();

        $this->loginAs($owner);
        $this->post('/workspace/equipe/sair')->assertSessionHasErrors('member');
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $workspaceId, 'user_id' => $owner->id, 'role' => 'owner']);
    }

    public function test_rejoining_reactivates_the_same_responsible(): void
    {
        [$owner, $member] = $this->teamOfTwo();
        $workspaceId = $owner->current_workspace_id;
        $memberAssignee = Assignee::where('workspace_id', $workspaceId)->where('user_id', $member->id)->firstOrFail();

        $this->post('/workspace/equipe/sair')->assertRedirect('/workspace');
        $this->assertNotNull($memberAssignee->fresh()->deactivated_at);

        $this->loginAs($owner);
        $this->post('/workspace/equipe/convites');
        $code = session('invite_code');

        $this->loginAs($member);
        $this->post('/workspace/equipe/entrar', ['code' => $code])->assertRedirect('/workspace/equipe');

        $this->assertNull($memberAssignee->fresh()->deactivated_at);
        $this->assertSame(2, Assignee::where('workspace_id', $workspaceId)->count(), 'Retornar não duplica o responsável.');
        $this->assertSame($workspaceId, $member->fresh()->current_workspace_id);
    }

    public function test_removal_requires_ownership_and_an_actual_member(): void
    {
        [$owner, $member] = $this->teamOfTwo();

        $this->delete('/workspace/equipe/membros/'.$owner->id)->assertForbidden();

        $this->loginAs($owner);
        $this->delete('/workspace/equipe/membros/'.$member->id)->assertRedirect('/workspace/equipe');
        // Repetir a remoção não encontra mais o vínculo.
        $this->delete('/workspace/equipe/membros/'.$member->id)->assertNotFound();
    }
}
