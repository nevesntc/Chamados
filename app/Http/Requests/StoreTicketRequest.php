<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Local demonstration; authentication is outside this version's scope.
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'assignment_mode' => ['required', Rule::in(['automatic', 'manual'])],
            'assignee_id' => ['exclude_unless:assignment_mode,manual', 'required', 'integer', 'exists:assignees,id'],
        ];
    }
}
