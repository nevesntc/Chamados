<?php

declare(strict_types=1);

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Assignee;
use App\Models\Ticket;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function person(string $name = 'Ana'): Assignee
    {
        return Assignee::create(['name' => $name]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['title' => 'Impressora sem conexão', 'description' => 'A impressora não aparece na rede do escritório.', 'priority' => 'medium', 'assignment_mode' => 'automatic'], $overrides);
    }

    private function ticket(Assignee $person, array $overrides = []): Ticket
    {
        return Ticket::create(array_replace(['title' => 'Chamado existente', 'description' => 'Descrição do chamado.', 'priority' => 'medium', 'status' => 'open', 'assignee_id' => $person->id], $overrides));
    }

    public function test_root_redirects_and_empty_list_renders(): void
    {
        $this->get('/')->assertRedirect('/chamados');
        $this->get('/chamados')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Tickets/Index')->has('tickets.data', 0)->where('summary.total', 0));
        $this->get('/chamados/create')->assertOk();
    }

    public function test_seed_is_repeatable_and_provides_three_people(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('assignees', 3);
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('tickets', 8);
    }

    public function test_automatic_assignment_includes_zero_workload_and_ignores_completed(): void
    {
        $ana = $this->person();
        $bruno = $this->person('Bruno');
        $carla = $this->person('Carla');
        $this->ticket($ana);
        $this->ticket($bruno, ['status' => 'in_progress']);
        $this->ticket($carla, ['status' => 'resolved']);
        $this->ticket($carla, ['status' => 'closed']);
        $this->post('/chamados', $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($carla->id, Ticket::latest('id')->first()->assignee_id);
        $this->assertSame(TicketStatus::Open, Ticket::latest('id')->first()->status);
    }

    public function test_tie_break_uses_lowest_id_and_subsequent_assignment_balances_load(): void
    {
        $first = $this->person();
        $second = $this->person('Bruno');
        $this->post('/chamados', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($first->id, Ticket::latest('id')->first()->assignee_id);
        $this->post('/chamados', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($second->id, Ticket::latest('id')->first()->assignee_id);
    }

    public function test_manual_assignment_is_respected_and_internal_fields_are_ignored(): void
    {
        $person = $this->person();
        $this->person('Bruno');
        $this->ticket($person);
        $this->post('/chamados', $this->payload(['assignment_mode' => 'manual', 'assignee_id' => $person->id, 'status' => 'closed', 'created_at' => '2000-01-01', 'id' => 999]))->assertSessionHasNoErrors();
        $ticket = Ticket::latest('id')->first();
        $this->assertSame($person->id, $ticket->assignee_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNotSame(999, $ticket->id);
        $this->assertSame(now()->year, $ticket->created_at->year);
    }

    public function test_edit_preserves_owner_and_opening_date_and_supports_reopening(): void
    {
        $person = $this->person();
        $ticket = $this->ticket($person, ['status' => 'resolved']);
        $opening = $ticket->created_at->toISOString();
        $this->travel(2)->hours();
        $this->put('/chamados/'.$ticket->id, $this->payload(['title' => 'Título atualizado', 'status' => 'open', 'assignment_mode' => 'manual', 'assignee_id' => $person->id, 'created_at' => '2000-01-01']))->assertRedirect('/chamados/'.$ticket->id);
        $ticket->refresh();
        $this->assertSame($opening, $ticket->created_at->toISOString());
        $this->assertSame($person->id, $ticket->assignee_id);
        $this->assertSame(1, $person->tickets()->active()->count());
        $this->get('/chamados/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->component('Tickets/Show')->where('ticket.title', 'Título atualizado'));
        $this->get('/chamados/'.$ticket->id.'/edit')->assertInertia(fn (Assert $p) => $p->component('Tickets/Edit')->where('ticket.assignee_id', $person->id));
    }

    public function test_redistribution_excludes_the_edited_ticket(): void
    {
        $first = $this->person();
        $second = $this->person('Bruno');
        $ticket = $this->ticket($first);
        $this->put('/chamados/'.$ticket->id, $this->payload(['status' => 'open']))->assertSessionHasNoErrors();
        $this->assertSame($first->id, $ticket->fresh()->assignee_id);
        $this->ticket($first);
        $this->put('/chamados/'.$ticket->id, $this->payload(['status' => 'in_progress']))->assertSessionHasNoErrors();
        $this->assertSame($second->id, $ticket->fresh()->assignee_id);
    }

    public function test_no_people_results_in_useful_error_and_no_partial_write(): void
    {
        $this->from('/chamados/create')->post('/chamados', $this->payload())->assertSessionHasErrors('assignment_mode');
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseHas('ticket_write_locks', ['id' => 1, 'version' => 0]);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->person();
        $this->post('/chamados', $this->payload(['title' => ' ', 'description' => '', 'priority' => 'urgent', 'assignment_mode' => 'other']))->assertSessionHasErrors(['title', 'description', 'priority', 'assignment_mode']);
        $this->post('/chamados', $this->payload(['title' => str_repeat('x', 151), 'description' => str_repeat('x', 5001)]))->assertSessionHasErrors(['title', 'description']);
        $this->post('/chamados', $this->payload(['assignment_mode' => 'manual', 'assignee_id' => 999]))->assertSessionHasErrors('assignee_id');
        $this->post('/chamados', $this->payload(['assignment_mode' => 'manual']))->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_invalid_status_does_not_change_the_ticket(): void
    {
        $person = $this->person();
        $ticket = $this->ticket($person);
        $this->put('/chamados/'.$ticket->id, $this->payload(['status' => 'unknown']))->assertSessionHasErrors('status');
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_combined_filters_and_global_summary(): void
    {
        $person = $this->person();
        $other = $this->person('Bruno');
        $match = $this->ticket($person, ['title' => 'Impressora RH', 'priority' => 'high']);
        $this->ticket($other, ['title' => 'Impressora RH', 'priority' => 'high']);
        $this->ticket($person, ['title' => 'Impressora RH', 'priority' => 'high', 'status' => 'resolved']);
        $this->ticket($person, ['title' => 'Computador', 'priority' => 'low']);
        $this->get('/chamados?search=Impressora&status=open&priority=high&assignee_id='.$person->id)
            ->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)->where('tickets.data.0.id', $match->id)->where('summary.total', 4)->where('summary.completed', 1));
    }

    public function test_pagination_is_stable_and_preserves_filters(): void
    {
        $person = $this->person();
        $this->freezeTime();
        $ids = [];
        for ($i = 0; $i < 22; $i++) {
            $ids[] = $this->ticket($person, ['title' => 'Teste '.$i])->id;
        }
        $this->get('/chamados?status=open')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 20)->where('tickets.data.0.id', $ids[21])->where('tickets.total', 22)->where('tickets.next_page_url', fn ($url) => str_contains($url, 'status=open')));
        $this->get('/chamados?status=open&page=2')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 2)->where('tickets.data.0.id', $ids[1]));
    }

    public function test_missing_ticket_returns_not_found(): void
    {
        $this->get('/chamados/999')->assertNotFound();
        $this->get('/chamados/999/edit')->assertNotFound();
    }

    public function test_search_treats_wildcards_as_literal_and_accepts_zero(): void
    {
        $person = $this->person();
        $this->ticket($person, ['title' => 'Disco 100% cheio']);
        $this->ticket($person, ['title' => 'Arquivo_relatorio']);
        $this->ticket($person, ['title' => 'Monitor 0']);
        $this->ticket($person, ['title' => 'Sem coincidência']);
        $this->get('/chamados?search=%25')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)->where('tickets.data.0.title', 'Disco 100% cheio'));
        $this->get('/chamados?search=_')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)->where('tickets.data.0.title', 'Arquivo_relatorio'));
        $this->get('/chamados?search=0')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 2));
    }
}
