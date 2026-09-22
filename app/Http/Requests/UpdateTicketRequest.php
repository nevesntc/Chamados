<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends StoreTicketRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'status' => ['required', Rule::enum(TicketStatus::class)]];
    }
}
