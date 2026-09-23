<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Enums\TicketStatus;
use App\Models\Assignee;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspaces\WorkspaceMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo12345!';

    private const PEOPLE = [
        ['name' => 'Ana Demonstração', 'email' => 'ana.demo@example.test'],
        ['name' => 'Bruno Demonstração', 'email' => 'bruno.demo@example.test'],
        ['name' => 'Carla Demonstração', 'email' => 'carla.demo@example.test'],
    ];

    public function run(WorkspaceMembership $membership, CreateTicket $create, UpdateTicket $update): void
    {
        // Fixed demo credentials belong only in a disposable local SQLite database.
        if (! app()->environment('local', 'testing') || DB::getDriverName() !== 'sqlite') {
            throw new RuntimeException('DemoSeeder só pode ser executado com SQLite em ambiente local ou de teste.');
        }

        $emails = array_column(self::PEOPLE, 'email');
        $existing = User::query()->whereIn('email', $emails)->get();
        if ($existing->isNotEmpty()) {
            $workspaceId = $existing->first()->current_workspace_id;
            $complete = $existing->count() === count(self::PEOPLE)
                && $workspaceId !== null
                && $existing->every(fn (User $user) => $user->current_workspace_id === $workspaceId)
                && Workspace::whereKey($workspaceId)->where('name', 'Equipe Demonstração Local')->exists()
                && DB::table('workspace_members')->where('workspace_id', $workspaceId)->whereIn('user_id', $existing->modelKeys())->count() === count(self::PEOPLE)
                && Assignee::where('workspace_id', $workspaceId)->whereIn('user_id', $existing->modelKeys())->count() === count(self::PEOPLE);

            if ($complete) {
                $this->command?->warn('Demonstração local já existe; nenhum dado foi alterado.');

                return;
            }

            throw new RuntimeException('Há contas de demonstração incompletas ou conflitantes. Use um banco local descartável novo.');
        }

        DB::transaction(function () use ($membership, $create, $update): void {
            $people = [];
            foreach (self::PEOPLE as $person) {
                $people[] = User::create([...$person, 'password' => self::PASSWORD]);
            }

            $workspace = $membership->createPersonal($people[0]);
            $workspace->update(['name' => 'Equipe Demonstração Local']);
            foreach (array_slice($people, 1) as $user) {
                $workspace->members()->attach($user->id, ['role' => 'member']);
                $membership->activate($user, $workspace);
            }

            $ownerAssignee = Assignee::where('workspace_id', $workspace->id)->where('user_id', $people[0]->id)->firstOrFail();
            $tickets = [
                ['title' => 'Computador não inicia', 'priority' => 'high', 'status' => TicketStatus::Open, 'mode' => 'automatic'],
                ['title' => 'Impressora sem conexão', 'priority' => 'medium', 'status' => TicketStatus::Open, 'mode' => 'automatic'],
                ['title' => 'Acesso ao sistema recuperado', 'priority' => 'medium', 'status' => TicketStatus::Resolved, 'mode' => 'automatic'],
                ['title' => 'Solicitação de cadeira concluída', 'priority' => 'low', 'status' => TicketStatus::Closed, 'mode' => 'automatic'],
                ['title' => 'Configurar estação de trabalho', 'priority' => 'high', 'status' => TicketStatus::InProgress, 'mode' => 'manual'],
            ];

            foreach ($tickets as $example) {
                $data = [
                    'title' => $example['title'],
                    'description' => 'Chamado fictício para avaliar a distribuição e os estados de atendimento.',
                    'priority' => $example['priority'],
                    'assignment_mode' => $example['mode'],
                ];
                if ($example['mode'] === 'manual') {
                    $data['assignee_id'] = $ownerAssignee->id;
                }

                $ticket = $create->execute($data, $workspace->id);
                if ($example['status'] !== TicketStatus::Open) {
                    $update->execute($ticket, [
                        ...$data,
                        'status' => $example['status']->value,
                        'assignment_mode' => 'manual',
                        'assignee_id' => $ticket->assignee_id,
                    ], $workspace->id);
                }
            }
        });

        $this->command?->info('Demonstração local criada: três contas e cinco chamados no mesmo workspace.');
    }
}
