<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Assignee;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_refuses_production(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->app->call([new DemoSeeder, 'run']);
            $this->fail('O seed de demonstração não pode rodar em produção.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('só pode ser executado', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_optional_demo_seed_provides_three_login_accounts_and_balances_next_ticket(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $people = User::orderBy('id')->get();
        $this->assertCount(3, $people);
        $this->assertTrue($people->every(fn (User $user) => Hash::check('Demo12345!', $user->password)));
        $workspaceId = $people[0]->current_workspace_id;
        $this->assertTrue($people->every(fn (User $user) => $user->current_workspace_id === $workspaceId));
        $this->assertDatabaseCount('workspaces', 1);
        $this->assertDatabaseCount('assignees', 3);
        $this->assertDatabaseCount('tickets', 5);
        $assignees = Assignee::where('workspace_id', $workspaceId)->orderBy('id')->get();
        $this->assertEqualsCanonicalizing($people->modelKeys(), $assignees->pluck('user_id')->all());
        $this->assertSame([2, 1, 0], $assignees->map(fn (Assignee $assignee) => $assignee->tickets()->active()->count())->all());
        $this->assertEqualsCanonicalizing(['open', 'in_progress', 'resolved', 'closed'], Ticket::distinct()->pluck('status')->map(fn ($status) => $status->value)->all());

        $this->post('/entrar', ['email' => 'ana.demo@example.test', 'password' => 'Demo12345!'])->assertRedirect('/workspace');
        $this->get('/workspace/equipe')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Workspace/Team')->has('people', 3));
        $this->post('/workspace/chamados', [
            'title' => 'Novo chamado automático', 'description' => 'Conferir menor carga no cenário de demonstração.',
            'priority' => 'low', 'assignment_mode' => 'automatic',
        ])->assertSessionHasNoErrors();
        $this->assertSame($assignees[2]->id, Ticket::latest('id')->firstOrFail()->assignee_id);
    }
}
