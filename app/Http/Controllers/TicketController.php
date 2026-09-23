<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Assignee;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(ListTicketsRequest $request): Response
    {
        $filters = $request->safe()->except('page');
        $workspaceId = $request->user()->current_workspace_id;
        $counts = Ticket::query()->where('workspace_id', $workspaceId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('Tickets/Index', [
            ...$this->formOptions(), 'filters' => $filters,
            'tickets' => Ticket::query()->where('workspace_id', $workspaceId)->with('assignee:id,name')
                ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->whereRaw(
                    "title LIKE ? ESCAPE '!'",
                    ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%']
                ))
                ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
                ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
                ->when($filters['assignee_id'] ?? null, fn (Builder $q, $id) => $q->where('assignee_id', $id))
                ->latest()->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'total' => $counts->sum(),
                'open' => (int) ($counts[TicketStatus::Open->value] ?? 0),
                'in_progress' => (int) ($counts[TicketStatus::InProgress->value] ?? 0),
                'completed' => (int) ($counts[TicketStatus::Resolved->value] ?? 0) + (int) ($counts[TicketStatus::Closed->value] ?? 0),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tickets/Create', $this->formOptions());
    }

    public function store(StoreTicketRequest $request, CreateTicket $action): RedirectResponse
    {
        $ticket = $action->execute($request->validated(), $request->user()->current_workspace_id);

        return to_route('tickets.show', $ticket)->with('success', 'Chamado criado. Sua solicitação já tem um responsável.');
    }

    public function show(Ticket $ticket): Response
    {
        return Inertia::render('Tickets/Show', ['ticket' => $ticket->load('assignee:id,name'),
            'statuses' => TicketStatus::options(), 'priorities' => TicketPriority::options()]);
    }

    public function edit(Ticket $ticket): Response
    {
        return Inertia::render('Tickets/Edit', [...$this->formOptions(), 'ticket' => $ticket->load('assignee:id,name')]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, UpdateTicket $action): RedirectResponse
    {
        $action->execute($ticket, $request->validated(), $request->user()->current_workspace_id);

        return to_route('tickets.show', $ticket)->with('success', 'Chamado atualizado com sucesso.');
    }

    private function formOptions(): array
    {
        return [
            'assignees' => Assignee::query()->where('workspace_id', auth()->user()->current_workspace_id)->assignable()
                ->withCount(['tickets as active_count' => fn (Builder $q) => $q->active()])
                ->orderBy('id')->get(['id', 'name']),
            'statuses' => TicketStatus::options(), 'priorities' => TicketPriority::options(),
        ];
    }
}
