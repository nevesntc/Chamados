<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Services\Tickets\AssigneeSelector;
use App\Services\Tickets\TicketWriteTransaction;

class UpdateTicket
{
    public function __construct(private AssigneeSelector $selector, private TicketWriteTransaction $transaction) {}

    public function execute(Ticket $ticket, array $data): Ticket
    {
        return $this->transaction->run(function () use ($ticket, $data) {
            $ticket->update([
                'title' => $data['title'], 'description' => $data['description'],
                'priority' => $data['priority'], 'status' => $data['status'],
                'assignee_id' => $data['assignment_mode'] === 'automatic'
                    ? $this->selector->select($ticket->id) : $data['assignee_id'],
            ]);

            return $ticket;
        });
    }
}
