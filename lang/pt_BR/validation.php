<?php

declare(strict_types=1);

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'exists' => 'O :attribute selecionado não está disponível.',
    'enum' => 'Selecione um valor válido para :attribute.',
    'in' => 'Selecione um valor válido para :attribute.',
    'max' => ['string' => 'O campo :attribute deve ter no máximo :max caracteres.'],
    'min' => ['numeric' => 'O campo :attribute deve ser no mínimo :min.'],
    'attributes' => [
        'title' => 'título', 'description' => 'descrição', 'priority' => 'prioridade',
        'status' => 'status', 'assignment_mode' => 'forma de atribuição', 'assignee_id' => 'responsável',
        'search' => 'busca', 'page' => 'página',
    ],
];
