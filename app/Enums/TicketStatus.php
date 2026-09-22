<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto', self::InProgress => 'Em andamento',
            self::Resolved => 'Resolvido', self::Closed => 'Fechado',
        };
    }

    public static function activeValues(): array
    {
        return [self::Open->value, self::InProgress->value];
    }

    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
