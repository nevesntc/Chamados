<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Assignee;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthWorkspaceTest extends TestCase
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

    public function test_web_responses_set_security_headers_and_private_pages_are_not_cached(): void
    {
        $this->get('/entrar')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");

        $this->register('Maria Silva', 'maria@example.test');
        $this->get('/workspace')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_guest_is_redirected_and_registration_creates_only_real_member(): void
    {
        $this->get('/workspace')->assertRedirect('/entrar');
        $this->get('/workspace/chamados/create')->assertRedirect('/entrar');
        $this->post('/workspace/chamados', [])->assertRedirect('/entrar');
        $this->get('/cadastro')->assertOk();
        $user = $this->register('Maria Silva', 'maria@example.test');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('workspaces', 1);
        $this->assertDatabaseCount('assignees', 1);
        $this->assertDatabaseHas('assignees', ['name' => 'Maria Silva', 'user_id' => $user->id, 'workspace_id' => $user->current_workspace_id]);
        $this->get('/workspace')->assertOk();
        $this->get('/workspace/equipe')->assertOk();
        $this->get('/workspace/perfil')->assertOk();
        $this->get('/workspace/chamados')->assertOk();
        $this->post('/sair')->assertRedirect('/entrar');
        $this->assertGuest();
        $this->get('/workspace')->assertRedirect('/entrar');
    }

    public function test_login_validation_and_session(): void
    {
        $user = $this->register('Maria Silva', 'maria@example.test');
        $this->post('/sair');
        $this->post('/entrar', ['email' => 'maria@example.test', 'password' => 'errada'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/entrar', ['email' => 'MARIA@example.test', 'password' => 'Segura1234'])->assertRedirect('/workspace');
        $this->assertAuthenticatedAs($user);
        $this->get('/cadastro')->assertRedirect('/workspace');
    }

    public function test_registration_rejects_weak_password_and_duplicate_email(): void
    {
        $this->post('/cadastro', ['name' => 'A', 'email' => 'a@example.test', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->register('Maria', 'maria@example.test');
        $this->post('/sair');
        $this->post('/cadastro', ['name' => 'Outra', 'email' => 'MARIA@example.test', 'password' => 'Segura1234', 'password_confirmation' => 'Segura1234'])->assertSessionHasErrors('email');
    }

    public function test_workspaces_isolate_tickets_and_manual_assignment(): void
    {
        $first = $this->register('Maria', 'maria@example.test');
        $person = Assignee::where('user_id', $first->id)->firstOrFail();
        $ticket = Ticket::create(['workspace_id' => $first->current_workspace_id, 'assignee_id' => $person->id, 'title' => 'Privado', 'description' => 'Somente Maria.', 'priority' => 'high', 'status' => 'open']);
        $this->post('/sair');
        $second = $this->register('João', 'joao@example.test');
        $this->get('/workspace/chamados')->assertInertia(fn ($page) => $page->where('summary.total', 0));
        $this->get('/workspace/chamados/'.$ticket->id)->assertNotFound();
        $this->put('/workspace/chamados/'.$ticket->id, [])->assertNotFound();
        $this->post('/workspace/chamados', ['title' => 'Tentativa', 'description' => 'Atribuir a Maria', 'priority' => 'medium', 'assignment_mode' => 'manual', 'assignee_id' => $person->id])->assertSessionHasErrors('assignee_id');
        $this->assertSame($second->current_workspace_id, auth()->user()->current_workspace_id);
    }

    public function test_invite_allows_join_and_non_owner_cannot_generate_it(): void
    {
        $first = $this->register('Maria', 'maria@example.test');
        $this->post('/workspace/equipe/convites')->assertRedirect('/workspace/equipe');
        $code = session('invite_code');
        $this->assertSame(48, strlen($code));
        $this->assertNotEquals($code, DB::table('workspace_invites')->value('token_hash'));
        $this->post('/workspace/equipe/entrar', ['code' => $code])->assertRedirect('/workspace/equipe');
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $first->current_workspace_id, 'user_id' => $first->id, 'role' => 'owner']);
        $this->post('/sair');
        $second = $this->register('João', 'joao@example.test');
        $this->post('/workspace/equipe/entrar', ['code' => str_repeat('x', 48)])->assertSessionHasErrors('code');
        $this->post('/workspace/equipe/entrar', ['code' => $code])->assertRedirect('/workspace/equipe');
        $this->assertSame($first->current_workspace_id, $second->fresh()->current_workspace_id);
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $first->current_workspace_id, 'user_id' => $second->id, 'role' => 'member']);
        $this->assertDatabaseHas('assignees', ['workspace_id' => $first->current_workspace_id, 'user_id' => $second->id]);
        $this->post('/workspace/equipe/convites')->assertForbidden();
        $this->post('/workspace/trocar', ['workspace_id' => $first->current_workspace_id + 100])->assertNotFound();
    }

    public function test_profile_updates_name_and_password(): void
    {
        $user = $this->register('Maria', 'maria@example.test');
        $this->patch('/workspace/perfil', ['name' => 'Maria Silva'])->assertRedirect('/workspace/perfil');
        $this->assertSame('Maria Silva', $user->fresh()->name);
        $this->assertDatabaseHas('assignees', ['user_id' => $user->id, 'name' => 'Maria Silva']);
        $this->put('/workspace/perfil/senha', ['current_password' => 'errada', 'password' => 'Outra12345', 'password_confirmation' => 'Outra12345'])->assertSessionHasErrors('current_password');
        $this->put('/workspace/perfil/senha', ['current_password' => 'Segura1234', 'password' => 'Outra12345', 'password_confirmation' => 'Outra12345'])->assertRedirect('/workspace/perfil');
        $this->post('/sair');
        $this->post('/entrar', ['email' => 'maria@example.test', 'password' => 'Segura1234'])->assertSessionHasErrors('email');
        $this->post('/entrar', ['email' => 'maria@example.test', 'password' => 'Outra12345'])->assertRedirect('/workspace');
    }
}
