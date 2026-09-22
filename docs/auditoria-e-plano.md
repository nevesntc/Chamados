# Auditoria e plano de implementação — Chamados internos

Data: 21/09/2026. Registro histórico do planejamento, anterior à implementação.

**Atualização:** a primeira versão foi implementada. Consulte o README, arquitetura.md, requisitos.md e validacao.md para o estado atual. A estratégia de concorrência evoluiu após verificar a compatibilidade do Laravel com PHP 8.3.

## 1. Parecer

O melhor posicionamento para esta vaga é entregar o fluxo completo, fácil de executar e bem testado, com decisões que você consiga explicar. A distribuição automática é a regra central do produto; merece mais atenção que funcionalidades adicionais.

Recomendo um monólito Laravel + Inertia + Vue, com SQLite para execução local, componentes pequenos e casos de uso explícitos. Aplicaremos princípios de código limpo de forma pragmática. Esta proposta usa convenções do Laravel e Eloquent; não pretende ser uma implementação estrita de Clean Architecture independente de framework.

O PDF é a fonte de requisitos, não uma autorização para publicar arquivos, criar repositório remoto ou enviar mensagens. Esta análise não executou essas ações. O documento menciona restrição de divulgação: manter o PDF e suas reproduções fora da entrega pública.

## 2. Evidências e limites da auditoria

- Todas as quatro páginas físicas do PDF foram extraídas e conferidas visualmente. A capa não tem numeração; as páginas seguintes exibem 1, 2 e 3 de 4. Não há evidência de uma quinta página no arquivo recebido.
- A pasta do projeto estava vazia, inclusive sem repositório Git. Portanto, não há código, testes, desempenho ou segurança da aplicação a aprovar nesta etapa.
- O documento informa 10 dias corridos após confirmação do recebimento e esforço aproximado de 8 horas. A data dessa confirmação não foi fornecida; não é possível determinar o vencimento.
- PHP CLI 8.3.30, Node, npm, Git e executável Docker foram encontrados. Isso não confirma que o daemon Docker esteja funcionando.
- PHP tem PDO SQLite e sqlite3. `mbstring`, `openssl` e `curl` não apareceram na lista de extensões carregadas; revisar os requisitos do framework e do Composer antes do setup.
- Composer não foi encontrado no PATH consultado. Pode existir fora dele; sua disponibilidade ainda precisa ser resolvida.

## 3. Matriz de requisitos e aceite

Todos os itens abaixo estão pendentes de implementação/verificação. A numeração corresponde ao documento; os critérios concretizam a entrega proposta.

| Origem | Exigência | Evidência de aceite planejada |
| --- | --- | --- |
| 1.0 | Repositório GitHub e acesso da equipe | Repositório acessível aos avaliadores; publicação e envio tratados na etapa de entrega |
| 1.1, 6.1, 6.2 | Instalação e execução locais documentadas | Seguir README em checkout limpo, instalar, criar banco, popular e abrir a aplicação |
| 1.2 | Justificar tecnologia e arquitetura | README explica opções, limites e alternativas descartadas |
| 2.1 | Criar, editar, listar e visualizar | Cada fluxo funciona pelo navegador e tem teste de integração pertinente |
| 2.2 | Campos mínimos | Título, descrição, prioridade, status, responsável e abertura persistidos e exibidos |
| 3.1–3.4 | Pelo menos três responsáveis selecionáveis | Seeder cria três responsáveis; formulário de criação e edição permite selecioná-los |
| 4.1 | Atribuição automática pela menor carga | Teste com cargas diferentes escolhe quem tem menos chamados não concluídos |
| 4.2 | Atribuição manual | Escolha válida é preservada mesmo quando outro responsável tem carga menor |
| 4.3 | Definição de chamado em aberto | Regra documentada, centralizada e usada na contagem e nos testes |
| 5.1–5.2 | Listagem útil ao acompanhamento | Lista mostra prioridade, status, responsável e data; filtros e paginação têm comportamento consistente |
| Dicas | Fundamentos, componentes, testes, CSS moderno e referências | Testes significativos, formulário reutilizado, estilos consistentes e bibliotecas creditadas |

Exclusão de chamados, autenticação, cadastro de responsáveis, anexos, comentários, notificações, SLA e implantação pública não são requisitos explícitos. A stack é livre; Laravel, Inertia e Vue são diferenciais citados, não obrigações.

## 4. Stack proposta e justificativas

| Escolha | Motivo | Custo ou limite |
| --- | --- | --- |
| Laravel 13 + PHP compatível | Alinhamento com a equipe; validação, migrations, ORM e testes integrados | Requer preparar extensões e Composer |
| Inertia + Vue 3 + TypeScript | Navegação e formulários integrados ao backend; contratos de propriedades mais claros | Tipos de frontend não substituem validação no servidor |
| Tailwind CSS | Interface consistente com pouco CSS próprio | Evitar componentes gigantes com marcação duplicada |
| SQLite | Avaliador não precisa instalar servidor de banco | Escritas são serializadas; concorrência precisa de estratégia e teste próprios |
| PHPUnit, Pint, ESLint e checagem de tipos | Testes de comportamento e estilo verificável | Configuração enxuta; ferramentas precisam ser realmente executadas |
| GitHub Actions | Evidência reproduzível de testes e build | Configurar após o fluxo local funcionar |

A documentação oficial informa que Laravel 13 exige PHP 8.3 ou superior. Fixar versões compatíveis na implementação e versionar `composer.lock` e o lockfile do frontend. A disponibilidade de PHP 8.3 no computador não garante que todas as dependências já possam ser instaladas.

Inertia mantém o roteamento no servidor e integra páginas Vue. Para esta aplicação, isso permite um único projeto e evita manter uma API separada apenas para servir suas próprias telas.

Docker é opcional: primeiro validar o caminho nativo documentado. Não adicionar Redis, filas, microsserviços, CQRS ou event sourcing sem uma necessidade concreta. Autenticação fica fora da primeira versão local; se houver exposição pública ou uso real, definir identidade e autorização antes dessa etapa.

## 5. Regras de negócio propostas

Estas são decisões de projeto para pontos abertos no documento, não exigências atribuídas à empresa.

### Status e prioridade

- Status: `open`, `in_progress`, `resolved`, `closed`, exibidos em português.
- Carga ativa: `open` e `in_progress`. Resolvidos e fechados não entram na contagem porque o trabalho de atendimento já terminou.
- Prioridade: `low`, `medium`, `high`. Ela organiza a fila, mas não pondera a distribuição: o requisito usa quantidade de chamados.
- Criação inicia em aberto. Na edição, permitir mudar entre os quatro status, inclusive reabrir; documentar essa liberdade para evitar um fluxo burocrático não solicitado.
- Resolver ou fechar mantém o responsável. Reabrir devolve o chamado à carga dele; não redistribui silenciosamente.
- Abertura é gerada pelo servidor e imutável. Armazenar datas em UTC e apresentar em `America/Sao_Paulo`.

### Distribuição automática

1. O formulário oferece modo automático ou manual. Na edição, começa em manual com o responsável atual; salvar texto não deve redistribuir.
2. No modo manual, validar a existência do responsável. Uma seleção inválida gera erro de campo, sem conversão silenciosa para automático.
3. No automático, contar chamados ativos por responsável, incluindo aqueles com contagem zero.
4. Em edição com redistribuição explícita, excluir o próprio chamado da contagem antes de escolher. Isso compara a carga que cada pessoa já possui sem essa tarefa.
5. Escolher a menor contagem. Em empate, usar o menor ID: simples, determinístico e fácil de testar. Esse desempate não garante rodízio histórico, o que fica documentado.
6. Escolher e persistir na mesma transação, com tratamento de concorrência adequado ao banco.
7. Sem responsáveis disponíveis, mostrar erro compreensível e não salvar chamado incompleto.

Exemplo: Ana tem 2 ativos, Bruno tem 1 e Carla tem 0. O próximo chamado automático vai para Carla, ainda que ela tenha 10 resolvidos.

### Concorrência

Uma transação genérica não é prova de distribuição correta: duas requisições podem ler a mesma carga antes de gravar. Em SQLite, avaliar a configuração de transação `IMMEDIATE`, adquirindo a reserva de escrita antes da leitura da carga. Manter a operação curta, definir espera limitada para banco ocupado e tratar falha sem gravação parcial. Não usar `lockForUpdate()` como se SQLite oferecesse bloqueio de linhas equivalente ao MySQL.

A estratégia precisa cobrir criação, troca de responsável e mudanças de status que alterem carga. Confirmar o comportamento na versão instalada e testar duas conexões/processos contra o mesmo arquivo SQLite. Testes sequenciais ou bancos em memória separados não demonstram concorrência. Enquanto esse teste não passar, o README deve registrar concorrência como limitação não validada.

## 6. Organização e modelo de dados

```text
app/
  Actions/Tickets/CreateTicket.php
  Actions/Tickets/UpdateTicket.php
  Services/Tickets/AssigneeSelector.php
  Enums/TicketStatus.php
  Enums/TicketPriority.php
  Http/Controllers/TicketController.php
  Http/Requests/StoreTicketRequest.php
  Http/Requests/UpdateTicketRequest.php
  Models/Ticket.php
  Models/Assignee.php
resources/js/
  Pages/Tickets/{Index,Create,Edit,Show}.vue
  Components/Tickets/{TicketForm,TicketFilters,StatusBadge}.vue
tests/
  Feature/Tickets/
  Unit/
```

Os controllers coordenam entrada e resposta. Form Requests validam dados. Actions executam as operações de escrita e delimitam transações. O seletor concentra a consulta da menor carga. Enums centralizam os valores permitidos e a definição de ativo. O formulário Vue compartilha campos e erros entre criação e edição. Criar outros arquivos somente quando a responsabilidade existir.

Eloquent pode ser usado diretamente nessa escala. Interfaces e repositories genéricos que apenas repetem `find`, `create` e `update` aumentariam a manutenção sem resolver um problema identificado. Uma abstração deve proteger uma regra ou uma dependência que realmente precisa variar.

| Tabela | Campos propostos |
| --- | --- |
| `assignees` | `id`, `name`, timestamps |
| `tickets` | `id`, `title`, `description`, `priority`, `status`, `assignee_id`, `created_at`, `updated_at` |

Usar `created_at` como abertura evita duplicar datas equivalentes. Chave estrangeira obrigatória para responsável, exclusão restrita e índice composto em `(assignee_id, status)` para a contagem. Não armazenar contadores derivados na tabela de responsáveis. Valores de enum persistidos como strings, com validação e casts; avaliar restrições CHECK nas migrations para integridade adicional.

Título obrigatório até 150 caracteres e descrição obrigatória até 5.000 são limites propostos. Não aceitar do cliente campos internos como ID e abertura. Usar somente dados validados, parâmetros vinculados nas consultas e lista permitida para ordenação. Renderizar descrição como texto, sem HTML arbitrário. Manter proteção CSRF nas rotas de escrita.

## 7. Experiência de uso e diferenciais

Priorizar três fluxos: encontrar um chamado, abrir um chamado e acompanhar/editar um chamado.

- Lista com número, título, status, prioridade, responsável e abertura; paginação de 20 itens.
- Filtros por status, prioridade e responsável, busca por título e parâmetros preservados na URL.
- Ordenação inicial por abertura mais recente, com ID como desempate. Eventual ordenação por prioridade precisa usar a ordem de negócio, não ordem alfabética.
- Formulário com atribuição automática claramente explicada, validação junto aos campos e estado de envio que evita cliques repetidos. Isso não equivale a idempotência garantida pelo servidor.
- Detalhe com descrição legível e ação de editar; feedback de sucesso e estado vazio orientando a primeira criação.
- Layout utilizável no celular, rótulos associados aos campos, foco visível e informação de status além da cor.

Após o núcleo estar validado, o diferencial visual mais útil é um resumo de carga ativa por responsável. Usar a mesma definição de ativo da distribuição. Se mostrar a carga no formulário, tratá-la como informativa: a decisão automática é recalculada no envio.

Histórico de alterações é uma evolução possível, mas fica depois dos testes e da documentação. Kanban, dark mode, IA, chat, notificações e dashboards elaborados têm prioridade baixa neste desafio.

## 8. Plano de testes baseado em risco

| Cenário | Resultado a verificar |
| --- | --- |
| Criar e editar com atribuição manual | Campos corretos e responsável escolhido persistidos |
| Menor carga com pessoa sem chamados | Pessoa com zero chamados também participa e vence |
| Status concluídos | Resolvidos e fechados não contam; em andamento conta |
| Empate | Mesmo conjunto produz o desempate documentado |
| Edição de título | Responsável e abertura permanecem |
| Redistribuição na edição | Próprio chamado é excluído da comparação |
| Reabertura | Carga aumenta para o responsável mantido |
| Dados inválidos | Campos vazios, enums desconhecidos e responsável inexistente não são gravados |
| Campos internos manipulados | Cliente não altera abertura nem ID |
| Nenhum responsável | Erro tratável e nenhuma gravação parcial |
| Listagem | Filtros combinados, paginação, ordem estável e estado vazio corretos |
| Chamado inexistente | Resposta 404 adequada |
| Concorrência | Duas escritas reais observam carga consistente após serialização |
| Interface | Fluxo de criar, visualizar, editar e filtrar funciona no navegador |

Usar testes de integração para banco e rotas; unitários para regras puras quando trouxerem valor. Não simular o banco justamente nos testes que validam contagem e persistência. Não perseguir 100% de cobertura com testes de getters; priorizar a falha que prejudicaria o usuário.

Pipeline planejado: instalar dependências dos lockfiles, preparar banco de teste, executar testes PHP, Pint, lint, checagem TypeScript e build. Uma prova de ponta a ponta do fluxo principal é um bom diferencial após o conjunto básico estar verde.

## 9. Riscos priorizados

| Prioridade | Risco | Tratamento |
| --- | --- | --- |
| Alta | Setup falhar no computador do avaliador | Resolver Composer/extensões e testar README em ambiente limpo |
| Alta | Distribuição ignorar zeros ou contar concluídos | Consulta agregada e testes com conjuntos mistos |
| Alta | Edição redistribuir sem intenção | Modo manual inicial e teste de regressão |
| Alta | Crescimento de escopo consumir a entrega | Fechar requisitos e testes antes de extras |
| Média | Corrida em atribuições simultâneas | Transação apropriada e teste em arquivo compartilhado |
| Média | Complexidade dificultar explicação na entrevista | Estrutura convencional e decisões justificadas |
| Média | Ausência de autenticação ser confundida com prontidão pública | Declarar escopo de demonstração local; reavaliar antes de disponibilizar publicamente |
| Baixa | Interface receber mais esforço que a regra central | Componentes simples e CSS de framework |

## 10. Ordem de execução

Estimativa inicial de 8 horas, alinhada ao documento, sem garantia: depende da familiaridade com a stack e da preparação do ambiente. Registrar o tempo real. Correções de ambiente e teste multiprocesso podem exigir margem adicional dentro dos 10 dias.

| Etapa | Orçamento inicial | Saída |
| --- | --- | --- |
| Ambiente e esqueleto | 45 min | Dependências, banco e página funcionando |
| Modelo, enums e seed | 45 min | Banco reproduzível com três responsáveis |
| Casos de uso e distribuição | 90 min | Criação/edição e testes centrais |
| Telas e navegação | 120 min | Fluxo completo pelo navegador |
| Filtros, validação e robustez | 60 min | Lista útil e erros tratados |
| Verificação e CI | 60 min | Testes, análise e build executados |
| README e ensaio de entrega | 60 min | Instalação limpa e demonstração preparada |

Se o tempo apertar, cortar extras visuais e histórico, preservando requisitos, testes centrais e instalação. Não esconder verificações pendentes.

## 11. Definição de pronto e apresentação

- [ ] Requisitos obrigatórios demonstráveis; atribuições manual e automática funcionam.
- [ ] Três responsáveis e dados demonstrativos reproduzíveis, sem dados pessoais reais.
- [ ] README contém pré-requisitos, extensões, instalação, `.env`, chave da aplicação, criação do SQLite, migrations, seeds, execução e testes.
- [ ] Instruções de reset de banco claramente identificadas como destrutivas e restritas ao ambiente de desenvolvimento.
- [ ] Escolhas de status, desempate, edição, datas, banco e autenticação documentadas.
- [ ] Testes, lint, checagem de tipos e build passam; limitações são declaradas.
- [ ] Clone/checkout limpo é executado seguindo apenas o README.
- [ ] `.env`, bancos locais, dependências e PDF original não entram no repositório; `.env.example` não contém segredos.
- [ ] Bibliotecas e referências externas estão creditadas.
- [ ] Commits pequenos e reais mostram evolução; não fabricar histórico de desenvolvimento.

Roteiro de demonstração: mostrar cargas diferentes, abrir automaticamente, verificar o responsável, resolver um chamado, observar a redução de carga e fazer uma atribuição manual. Depois mostrar um teste da regra e explicar uma decisão de arquitetura. Você deve conseguir modificar uma regra e prever quais testes mudarão; isso vale mais na entrevista que acumular padrões arquiteturais.

## 12. Referências técnicas consultadas

- [Laravel: versões e requisitos de PHP](https://laravel.com/framework/docs/releases).
- [Inertia: integração entre backend e frontend](https://inertiajs.com/).
- [Laravel: configuração de banco no esqueleto 13.x](https://github.com/laravel/laravel/blob/13.x/config/database.php).
- [Laravel: documentação de banco 13.x](https://github.com/laravel/docs/blob/13.x/database.md).
- [SQLite: transações e comportamento de IMMEDIATE](https://www.sqlite.org/lang_transaction.html).

As recomendações são propostas técnicas. Esta auditoria não certifica uma aplicação pronta nem substitui os testes da futura implementação.
