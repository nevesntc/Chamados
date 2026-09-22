# Decisões de arquitetura

Status: implementadas nesta primeira versão, salvo evolução explicitamente indicada. Atualizar este documento e o README sempre que uma decisão mudar.

## ADR-001 — Monólito Laravel, Inertia e Vue

**Contexto:** equipe pequena, aplicação interna e requisito de execução local simples.

**Decisão:** rotas Laravel e páginas Vue por Inertia. TypeScript descreve as propriedades recebidas; Form Requests validam no servidor. Sem API REST separada ou Vue Router.

**Consequências:** um deploy e menor duplicação. A interface depende dos contratos Inertia. Uma futura aplicação móvel demandará endpoints próprios, reutilizando os casos de uso.

## ADR-002 — Separação pragmática de responsabilidades

Controllers coordenam HTTP. Requests validam. Actions implementam criação/edição. Enums centralizam status/prioridade. AssigneeSelector calcula a menor carga. TicketWriteTransaction coordena transações. Models usam Eloquent.

Não há repository genérico, service para cada model ou interface sem variação real. A regra pode ser testada pelo caso de uso com banco de teste. É uma arquitetura convencional com código limpo; não é independência estrita do framework.

O formulário é compartilhado entre criação e edição. Filtros, indicadores e carga da equipe são componentes separados. CSS está organizado em blocos de layout, lista, formulário e breakpoints. O Tailwind examina apenas resources/js por configuração explícita, para manter o build independente de arquivos temporários ou da presença de um repositório Git. A tipografia usa fontes do sistema, sem dependência de download externo.

## ADR-003 — Banco local e concorrencia

**Decisão:** SQLite por padrão, FK obrigatória e índice em responsável/status. Manter as conexões do framework disponíveis, sem afirmar validação em outros bancos.

**Descoberta:** no Laravel 13.32, SQLiteConnection só aplica a opção transaction_mode por SQL quando PHP >= 8.4. No PHP 8.3, inicia transação pelo PDO. A opção IMMEDIATE, isoladamente, não resolveria este ambiente.

**Implementação:** toda escrita de chamado passa pela linha única de ticket_write_locks. A primeira query da transação incrementa sua versão e adquire a reserva de escrita SQLite antes da seleção. A versão é apenas um dado de coordenação, não cache de carga. Se a operação falhar, incremento e chamado sofrem rollback juntos. A espera por banco ocupado é limitada a 5 segundos por tentativa e há até três tentativas.

**Verificação:** dois processos separados usam o mesmo arquivo SQLite; o primeiro mantém o bloqueio até um sinal, o segundo espera e então seleciona usando a carga nova.

**Trade-off:** serializamos todas as escritas de chamados. É apropriado ao tamanho do desafio, mas limita throughput. Não realizar I/O externo dentro dessa transação.

**PostgreSQL/Supabase:** configuração e teste concorrente foram acrescentados na ADR-007. Consulte validacao.md para resultados reais de CI e situação da conexão Supabase. Migrations criam estrutura; não migram automaticamente registros de SQLite.

## ADR-004 — Regras e desempate

Ativos: aberto e em andamento. Resolvido e fechado não demandam atendimento corrente. Prioridade não pesa na distribuição porque a especificação pede quantidade.

Empate pelo menor ID. Em edição automática, excluir o próprio chamado da contagem. Em edição comum, manter o responsável. Reabertura mantém responsável salvo solicitação explícita de troca. Qualquer transição entre status válidos é permitida.

O desempate é previsível, porém não garante rodízio histórico. Histórico e escolha por última atribuição poderiam ser adicionados se surgisse esse requisito.

## ADR-005 — Segurança e escopo de demonstração

Sem login na versão local: a especificação não define perfis nem autenticação. O comentário em authorize() torna essa decisão visível. Não apresentar essa configuração como pronta para exposição pública.

CSRF do framework, escrita explícita dos campos validados, FK restrita, enum no banco e texto escapado. Data de abertura vem do servidor. Não aceitar HTML em descrição. Credenciais e banco local ficam fora do Git.

Sem exclusão, anexos, notificações, permissões, histórico ou bloqueio otimista. Escritas simultâneas são serializadas, mas um formulário antigo pode sobrescrever uma edição mais recente: última gravação vence. Essa é uma limitação documentada, não um controle de conflito completo.

## ADR-006 — Qualidade e documentação como parte da mudança

PHPUnit para comportamento/integração e concorrência. Pint com strict_types. TypeScript, ESLint, Prettier e build. CI testa PHP 8.3/8.4, SQLite e PostgreSQL descartável, navegador e imagem Docker.

README é o ponto de entrada. Este arquivo guarda decisões; requisitos.md rastreia escopo; validacao.md registra evidências. AGENTS.md orienta todos os assistentes, e CLAUDE.md encaminha para ele. A auditoria inicial é histórica e não substitui documentação atual.

## Modelo

- assignees: id, name, timestamps.
- tickets: id, title, description, priority, status, assignee_id, timestamps.
- ticket_write_locks: linha única id=1, version.
- As migrations padrão de usuários/sessões, cache e jobs do esqueleto Laravel são mantidas; não representam funcionalidades disponíveis na interface. Os drivers locais usam arquivos/síncrono.

Carga é sempre derivada da consulta, sem contador redundante em assignees. Listagem tem paginação de 20 e ordenação estável por abertura/ID. Data exibida em São Paulo e armazenada em UTC.

## ADR-007 — Cloudflare, PostgreSQL e entrega contínua

O pedido posterior autorizou GitHub, CI/CD, Supabase e estrutura Cloudflare. SQLite continua local. PHP roda em Containers atrás de um Worker, sem API separada. Uma instância nomeada e limitada a uma atende a demonstração. Containers requer plano compatível e cobrança por uso; nenhuma assinatura foi ativada.

PostgreSQL usa TLS e schema `chamados`, separado de public. `app:prepare-production-database` cria esse schema e aplica migrations incrementais e seed; não usa migrate:fresh. Não expor esse schema na Data API. Em operação real, separar usuário de migration e usuário de runtime com privilégio mínimo.

Sessões no PostgreSQL, cache em memória por requisição, fila síncrona, logs stderr e APP_KEY estável em secret. O disco do container não persiste dados. TRUST_PROXY habilita somente confiança no protocolo encaminhado; o Worker substitui esse cabeçalho pela conexão recebida.

A coordenação PostgreSQL usa o mesmo incremento antes da leitura, sob READ COMMITTED padrão. O teste concorrente cria schema aleatório exclusivo e o remove; a CI usa banco descartável. Não executar testes no banco de produção.

CD manual na main, environment production e CI obrigatória. Secrets passam por arquivos temporários ignorados pelo Git e Docker, removidos ao fim do job. Migrations antecedem rollout, nunca rodam no boot. Mudanças de schema precisam ser retrocompatíveis: rollback de código não reverte dados.

Não adicionamos login. Controle de acesso na infraestrutura deve preceder uso real. Publicação efetiva depende de credenciais Cloudflare e conexão Supabase alcançável. Consulte deploy.md e validacao.md.

## ADR-008 — Vercel como alternativa de hospedagem

O usuário autorizou Vercel se simplificar a publicação. A documentação atual suporta Container Images em beta. vercel.json reutiliza o Dockerfile existente através de Services, mantendo Laravel, Postgres e sessões externas. Evitamos duplicar imagens ou mudar o produto para outra stack. Configuração adicionada, mas execução no provedor só pode ser considerada validada após deploy e smoke test autenticados. A opção Cloudflare permanece; não há publicação dupla automática.

O Dockerfile normaliza leitura/travessia do código recebido por upload. O entrypoint fixa umask e atribui ao usuário Apache os caches gerados na inicialização; não depende do umask do provedor. O smoke da CI inicializa com umask 077 para verificar esse caso.
