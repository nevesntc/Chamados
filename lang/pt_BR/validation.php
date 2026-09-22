<?php

declare(strict_types=1);

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'exists' => 'O :attribute selecionado não está disponível.',
    'email' => 'Informe um e-mail válido.',
    'unique' => 'Este :attribute já está cadastrado.',
    'confirmed' => 'A confirmação de :attribute não corresponde.',
    'current_password' => 'A senha atual está incorreta.',
    'password' => [
        'letters' => 'A :attribute precisa conter pelo menos uma letra.',
        'numbers' => 'A :attribute precisa conter pelo menos um número.',
    ],
    'enum' => 'Selecione um valor válido para :attribute.',
    'in' => 'Selecione um valor válido para :attribute.',
    'max' => ['string' => 'O campo :attribute deve ter no máximo :max caracteres.'],
    'min' => ['numeric' => 'O campo :attribute deve ser no mínimo :min.', 'string' => 'O campo :attribute deve ter pelo menos :min caracteres.'],
    'size' => ['string' => 'O campo :attribute deve ter :size caracteres.'],
    'attributes' => [
        'title' => 'título', 'description' => 'descrição', 'priority' => 'prioridade',
        'status' => 'status', 'assignment_mode' => 'forma de atribuição', 'assignee_id' => 'responsável',
        'search' => 'busca', 'page' => 'página',
        'name' => 'nome', 'email' => 'e-mail', 'password' => 'senha',
        'password_confirmation' => 'confirmação de senha', 'current_password' => 'senha atual',
        'code' => 'código de convite', 'workspace_id' => 'workspace',
    ],
];
