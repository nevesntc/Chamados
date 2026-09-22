<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Assignee;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        if (Ticket::exists()) {
            $this->command?->info('Já existem chamados. Dados demonstrativos não foram adicionados.');

            return;
        }
        $ids = Assignee::orderBy('id')->limit(3)->pluck('id');
        $samples = [
            ['Computador reiniciando durante o trabalho', 'O computador da recepção reinicia ao abrir as planilhas. Precisamos verificar para retomar o atendimento.', 'high', 'open', 0],
            ['Acesso à pasta compartilhada do financeiro', 'Solicito acesso de leitura à pasta de relatórios para concluir a conferência do mês.', 'medium', 'in_progress', 1],
            ['Impressora do segundo andar sem conexão', 'A impressora não aparece na rede. O equipamento está ligado e exibe uma mensagem de conexão indisponível.', 'high', 'in_progress', 2],
            ['Substituição de cadeira no administrativo', 'A regulagem de altura da cadeira não funciona. Solicito avaliação e substituição, se necessário.', 'low', 'open', 0],
            ['Instalação de monitor na sala de reuniões', 'Precisamos conectar o novo monitor ao computador da sala e testar a apresentação por HDMI.', 'medium', 'open', 1],
            ['Atualização do sistema de videoconferência', 'O aplicativo solicita uma atualização para permitir a entrada nas próximas reuniões.', 'medium', 'resolved', 2],
            ['Teclado com teclas sem resposta', 'Algumas teclas deixaram de responder. A troca do teclado resolveu o problema.', 'low', 'closed', 0],
            ['Rede Wi-Fi instável na recepção', 'A conexão cai durante o atendimento. Verificar o ponto de acesso e a cobertura do ambiente.', 'high', 'open', 2],
        ];
        foreach ($samples as $index => [$title, $description, $priority, $status, $person]) {
            $ticket = new Ticket(compact('title', 'description', 'priority', 'status') + ['assignee_id' => $ids[$person]]);
            $ticket->created_at = now()->subHours($index * 3 + 1);
            $ticket->updated_at = $ticket->created_at;
            $ticket->save();
        }
    }
}
