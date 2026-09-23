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

**PostgreSQL/Supabase:** configuração e teste concorrente foram acrescentados na ADR-007. Migrations criam estrutura; não migram automaticamente registros de SQLite.

## ADR-004 — Regras e desempate

Ativos: aberto e em andamento. Resolvido e fechado não demandam atendimento corrente. Prioridade não pesa na distribuição porque a especificação pede quantidade.

Empate pelo menor ID. Em edição automática, excluir o próprio chamado da contagem. Em edição comum, manter o responsável. Reabertura mantém responsável salvo solicitação explícita de troca. Qualquer transição entre status válidos é permitida.

O desempate é previsível, porém não garante rodízio histórico. Histórico e escolha por última atribuição poderiam ser adicionados se surgisse esse requisito.

## ADR-005 — Autenticação e isolamento de workspaces

Cadastro e login reais, sem pessoas fictícias, mantêm os responsáveis ligados a contas de verdade. Laravel Auth com sessão e senha com hash atende isso no mesmo monólito; login regenera a sessão e logout a invalida. Rotas de trabalho passam por `auth` e `EnsureCurrentWorkspace`.

Cada cadastro cria usuário, workspace privado, vínculo de dono e responsável na mesma transação. Um usuário pode participar de vários workspaces; `current_workspace_id` define o ativo. Convite gerado pelo dono guarda apenas SHA-256 do código aleatório e expira em sete dias. Aceitar convite cria vínculo e responsável. A opção de troca só consulta os workspaces do usuário. Nomes de responsáveis acompanham a alteração do perfil.

Toda consulta de chamados e responsáveis usa o workspace ativo. Route binding devolve 404 para ID de outro workspace; Form Request recusa responsável de outra equipe. CreateTicket recebe workspaceId validado pelo middleware, e AssigneeSelector seleciona apenas nele, dentro do mesmo bloqueio de escrita. A proteção é aplicada no backend; tipos Vue não substituem autorização.

CSRF, escrita explícita dos campos validados, FK, enum no banco e texto escapado continuam. O seed padrão é vazio: em uso real, os responsáveis vêm de cadastros com convite; para avaliação local, o `DemoSeeder` da ADR-011 cria uma equipe pronta. Registros antigos sem workspace são preservados pela migration, porém não aparecem em equipes novas.

Sem exclusão, anexos, notificações, recuperação por e-mail, histórico ou bloqueio otimista. Escritas simultâneas são serializadas, mas um formulário antigo pode sobrescrever edição mais recente: última gravação vence. Não existe isolamento por papel dentro de uma equipe: membros veem e editam os chamados dela.

## ADR-006 — Qualidade e documentação como parte da mudança

PHPUnit para comportamento/integração e concorrência. Pint com strict_types. TypeScript, ESLint, Prettier e build. CI testa PHP 8.3/8.4, SQLite e PostgreSQL descartável, navegador e imagem Docker.

README é o ponto de entrada. Este arquivo guarda as decisões e requisitos.md rastreia o escopo. Documentação e testes são atualizados junto com o código que descrevem.

## Modelo

- users: conta, senha com hash, current_workspace_id.
- workspaces e workspace_members: equipes privadas e vínculos owner/member.
- workspace_invites: hash do código e expiração.
- assignees: id, name, workspace_id, user_id, deactivated_at, timestamps.
- tickets: id, title, description, priority, status, assignee_id, workspace_id, timestamps.
- ticket_write_locks: linha única id=1, version.
- Sessões usam arquivos localmente e PostgreSQL em produção; cache e jobs padrão são mantidos.

Carga é sempre derivada da consulta, sem contador redundante em assignees. Listagem tem paginação de 20 e ordenação estável por abertura/ID. Data exibida em São Paulo e armazenada em UTC.

## ADR-007 — PostgreSQL gerenciado e entrega contínua

GitHub, CI e PostgreSQL gerenciado foram acrescentados após a primeira versão local. O SQLite continua sendo o padrão local. Em produção, o PHP roda em container, sem API separada.

PostgreSQL usa TLS e schema `chamados`, separado de public. `app:prepare-production-database` cria esse schema e aplica migrations incrementais e seed; não usa migrate:fresh. Não expor esse schema na Data API. Em operação real, separar usuário de migration e usuário de runtime com privilégio mínimo.

Sessões no PostgreSQL, cache em memória por requisição, fila síncrona, logs em stderr e APP_KEY estável guardada como segredo. O disco do container não persiste dados. TRUST_PROXY habilita apenas a confiança no protocolo encaminhado pelo proxy da hospedagem.

A coordenação PostgreSQL usa o mesmo incremento antes da leitura, sob READ COMMITTED padrão. O teste concorrente cria schema aleatório exclusivo e o remove; a CI usa banco descartável. Não executar testes no banco de produção.

Migrations antecedem o rollout e nunca rodam no boot do container. Mudanças de schema precisam ser retrocompatíveis: rollback de código não reverte dados.

O login e o isolamento foram adicionados depois, na ADR-005. Consulte deploy.md.

## ADR-008 — Vercel como hospedagem

A Vercel foi escolhida por simplificar a publicação. O `vercel.json` reutiliza o Dockerfile existente através de Services, mantendo Laravel, PostgreSQL e sessões externas, sem duplicar imagens nem trocar a stack do produto. O projeto está ligado ao GitHub para deploys da `main`; as migrations são aplicadas antes do rollout.

O Dockerfile normaliza leitura/travessia do código recebido por upload. O entrypoint fixa umask e atribui ao usuário Apache os caches gerados na inicialização; não depende do umask do provedor. O smoke da CI inicializa com umask 077 para verificar esse caso.

O domínio `central-de-chamados-neves.vercel.app` foi adicionado ao projeto como domínio público específico. O alias público antigo `chamados-smoky-beta.vercel.app` foi removido. A configuração SSO `all_except_custom_domains` continua protegendo as URLs de deployment; somente o domínio novo permite visitantes chegarem ao cadastro. `APP_URL` aponta para ele para manter sessões e redirecionamentos no mesmo host.

## ADR-009 — Rotas e atualização da interface

URLs de trabalho ficam em `/workspace`, `/workspace/chamados`, `/workspace/equipe` e `/workspace/perfil`; `/chamados` redireciona para a nova lista. Cadastro e login ficam em `/cadastro` e `/entrar`. Inertia mantém rotas no Laravel; não há Vue Router nem API duplicada.

Painel e lista consultam o servidor a cada 10 segundos apenas quando a aba está visível. Isso atualiza trabalho compartilhado com latência máxima aproximada de dez segundos, sem infraestrutura de WebSocket. As gravações continuam imediatas e retornam o estado salvo; não prometemos push instantâneo. Formulários não são recarregados durante edição.

**Troca de tela:** cada item do menu lateral usa `prefetch` do Inertia no apontar e no pressionar, com cache de 30 segundos, então a resposta normalmente já está em memória quando o clique acontece. As atualizações periódicas usam `async`, para não cancelar nem enfileirar uma navegação em andamento. Não pré-carregamos tudo ao montar a página: seriam quatro requisições por visita, e em servidor de desenvolvimento de processo único elas atrasariam justamente o clique seguinte.

## ADR-010 — Restrições de runtime e recuperação operacional

O runtime publicado usa `chamados_runtime`, role PostgreSQL com `USAGE` no schema `chamados`, leitura das tabelas e uso das sequências. Escrita foi concedida por tabela/operação: criação e atualização necessárias para contas, sessões, convites e chamados. Não pode excluir chamados, inserir migrations nem criar objetos. Migrations permanecem tarefa administrativa separada; o código não roda migrations durante boot. Novas tabelas recebem leitura por padrão; uma migration que acrescente escrita exige grant explícito antes do rollout.

Sessões persistem cifradas no PostgreSQL em produção (`SESSION_ENCRYPT=true`). Respostas web incluem políticas de enquadramento, tipo de conteúdo, referenciador e permissões do navegador; conteúdo autenticado recebe `no-store`, e HTTPS recebe HSTS. O teste de middleware cobre os cabeçalhos básicos e o cache privado; HSTS e `no-store` foram conferidos no domínio publicado. Essas medidas não substituem rotação da senha administrativa, verificação de e-mail ou recuperação de conta.

O utilitário `scripts/backup-production.php` usa a role de runtime para gerar apenas o schema da aplicação em formato custom do PostgreSQL. Cifra o arquivo com AES-256-GCM e verifica autenticação e leitura da lista pelo `pg_restore`; chave e backup ficam em `.tools`, fora do Git. O dump foi restaurado em PostgreSQL temporário local e as contagens de dados conferiram. É um backup manual e local, com limite de 256 MiB por carregar o dump em memória. Manter cópia externa da chave e do backup continua necessário.

## ADR-011 — Demonstração local isolada do seed padrão

O seed padrão permanece vazio para que cadastro real e produção iniciem sem pessoas fictícias. O comando explícito `db:seed --class=DemoSeeder` funciona somente em ambiente `local`/`testing` com SQLite. Ele cria três usuários autenticáveis e um workspace com cinco chamados de exemplo; o fixture vincula os membros diretamente em transação e usa `WorkspaceMembership` para ativá-los, sem criar uma rota de entrada sem convite. Cada chamado passa por `CreateTicket` e, para mudar de status, `UpdateTicket`. Assim, a mesma transação de escrita protege a seleção automática usada pelo produto. O cenário termina com cargas ativas 2/1/0 e demonstra o efeito de resolvidos/fechados. Uma transação externa torna a carga atômica; uma segunda execução reconhece a equipe pronta sem duplicar registros. Conflitos de e-mail interrompem a carga. A equipe de demonstração local é independente das contas publicadas.

## ADR-012 — Gestão da equipe e desligamento sem perda de histórico

O dono do espaço renomeia a equipe e desliga pessoas; qualquer membro sai por conta própria. As três operações passam por `WorkspaceMembership`, que centraliza a verificação de posse (`isOwner`) antes usada em linha no controller.

**Problema:** `tickets.assignee_id` é chave estrangeira com `restrictOnDelete`, então apagar o responsável de quem sai é impossível enquanto existirem chamados dele. Reatribuir o histórico para outra pessoa resolveria a integridade, mas apagaria a informação de quem de fato atendeu.

**Decisão:** `assignees.deactivated_at` marca o desligamento. O vínculo em `workspace_members` é removido, mas a linha de `assignees` permanece. O escopo `assignable()` filtra quem está ativo e é aplicado na seleção automática, nas opções do formulário, na validação de responsável e na tela de Equipe. Os chamados já atendidos continuam nomeando a pessoa.

**Retorno:** aceitar um convite usa `updateOrCreate` e zera `deactivated_at`, reativando o mesmo registro em vez de criar um segundo responsável para o mesmo usuário — o índice único `(workspace_id, user_id)` já impediria a duplicata.

**Posse:** o dono não pode ser removido nem sair. Não há transferência de posse nesta versão, e permitir a saída deixaria a equipe sem quem convide ou administre. A regra vive no serviço e devolve erro de validação, não 403, porque é uma condição de negócio e não falta de permissão.

**Espaço ativo:** quem perde o vínculo tem `current_workspace_id` movido para outro espaço seu; sem nenhum, o middleware cria o espaço pessoal na requisição seguinte. Sem isso, a pessoa ficaria presa em 403 após ser desligada.

**Limite:** não há papéis intermediários. Membro e dono são os únicos níveis, e todo membro continua vendo e editando os chamados da equipe.

## ADR-013 — Limites de tentativa por ação

O `throttle:N,M` genérico do Laravel identifica um visitante por domínio e IP, sem considerar a rota. Login e cadastro, ambos anônimos, acabavam no mesmo contador: gastar tentativas em um consumia o outro, e o menor limite entre eles derrubava a próxima requisição. Em uma rede com saída única, poucas pessoas legítimas travariam a porta de entrada.

Cada ação sensível — login, cadastro, convite, entrada por código e troca de senha — passa a ter um limitador nomeado, registrado em `AppServiceProvider`, com chave própria por conta autenticada ou por IP. Os limites por minuto continuam os mesmos; muda apenas o isolamento entre eles. Um teste de integração fixa a regra: esgotar o login não impede um cadastro.

## ADR-014 — Duração da sessão

A sessão expira com 30 minutos de inatividade e não sobrevive ao fechamento do navegador (`SESSION_EXPIRE_ON_CLOSE`). O padrão do Laravel, 120 minutos com cookie persistente, é confortável para um produto de uso pessoal, mas este é um sistema interno de trabalho, aberto com frequência em máquina compartilhada do escritório descrito pelo cliente. Uma sessão longa gravada em disco significa que a próxima pessoa a usar o computador entra como a anterior.

Como cada requisição renova o prazo, o limite só incomoda quem parou de trabalhar. Os valores ficam em `SESSION_LIFETIME` e `SESSION_EXPIRE_ON_CLOSE`, e o padrão da aplicação em `config/session.php` já é o curto, para que uma instalação nova nasça com a política correta mesmo sem essas variáveis.

Um teste verifica o contrato observável: a resposta autenticada traz o cookie de sessão com expiração zero e marcado `HttpOnly`, e o prazo configurado não passa de 30 minutos. Em produção, o cookie também é `Secure` e o conteúdo da sessão é cifrado no PostgreSQL.
