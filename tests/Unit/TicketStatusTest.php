<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    public function test_only_unfinished_statuses_count_towards_workload(): void
    {
        $this->assertSame(['open', 'in_progress'], TicketStatus::activeValues());
        $this->assertNotContains(TicketStatus::Resolved->value, TicketStatus::activeValues());
        $this->assertNotContains(TicketStatus::Closed->value, TicketStatus::activeValues());
    }
}
