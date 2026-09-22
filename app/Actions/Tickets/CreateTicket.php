<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\Tickets\AssigneeSelector;
use App\Services\Tickets\TicketWriteTransaction;

class CreateTicket
{
    public function __construct(private AssigneeSelector $selector, private TicketWriteTransaction $transaction) {}

    public function execute(array $data): Ticket
    {
        return $this->transaction->run(fn () => Ticket::create([
            'title' => $data['title'], 'description' => $data['description'],
            'priority' => $data['priority'], 'status' => TicketStatus::Open,
            'assignee_id' => $data['assignment_mode'] === 'automatic'
                ? $this->selector->select() : $data['assignee_id'],
        ]));
    }
}
