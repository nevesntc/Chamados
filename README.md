# Central de Chamados

[![Quality](https://github.com/nevesntc/Chamados/actions/workflows/ci.yml/badge.svg)](https://github.com/nevesntc/Chamados/actions/workflows/ci.yml)

Aplicação web para registrar solicitações internas, acompanhar o atendimento e distribuir o trabalho entre os responsáveis. Construída com **Laravel 13, Inertia 2, Vue 3 e TypeScript**, usando SQLite no ambiente local.

Versão publicada: [central-de-chamados-neves.vercel.app](https://central-de-chamados-neves.vercel.app). Os chamados só ficam acessíveis após login.

## O que a aplicação faz

- Criação, edição, listagem paginada e visualização de chamados.
- Título, descrição, prioridade, status, responsável e data/hora de abertura.
- Atribuição automática ao responsável com menos chamados ativos, ou escolha manual.
- Busca por título e filtros combináveis de status, prioridade e responsável, preservados na URL.
- Resumo geral dos chamados e carga atual por responsável.
- Cadastro e login com sessão; cada conta cria um espaço de trabalho próprio e passa a ser um responsável.
- Equipe por código de convite temporário, com troca de espaço para quem participa de mais de um.
- Gestão da equipe: o dono renomeia o espaço e desliga pessoas; cada membro pode sair por conta própria.
- Interface responsiva em português, com validação por campo e retorno de gravação.
- Testes de regras, rotas e isolamento, incluindo duas atribuições concorrentes em processos independentes.
- CI em PHP 8.3/8.4, SQLite e PostgreSQL 17, navegador desktop/celular e imagem Docker.

## Pré-requisitos

- PHP 8.3 ou superior com as extensões usuais do Laravel: ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_sqlite, session, tokenizer e XML. ZIP facilita a instalação pelo Composer.
- Composer 2.
- Node.js 22.13+ e npm. Validado com Node 22.21 e PHP 8.3.30.
- Git. SQLite funciona pelo driver do PHP; não é preciso instalar um servidor de banco.
- Diretórios `storage` e `bootstrap/cache` graváveis.

Confira com `php -v`, `php -m`, `composer --version` e `node --version`. As versões resolvidas estão em `composer.lock` e `package-lock.json`.

## Instalação

Na pasta do projeto, após clonar:

```sh
composer install
composer run setup
php artisan db:seed --class=DemoSeeder
php artisan serve --host=127.0.0.1 --port=8000
```

O `composer run setup` cria `.env` e o arquivo SQLite se não existirem, gera a chave da aplicação apenas quando ausente, executa as migrations, instala o frontend com `npm ci` e gera o build. Ele não apaga chamados existentes.

O `DemoSeeder` é opcional e serve para avaliar a aplicação sem precisar criar contas na mão. Ele só roda com SQLite em ambiente local ou de teste, e não deve ser executado em produção. Depois dele, abra **http://127.0.0.1:8000/entrar**; para começar do zero com uma conta própria, pule o seed e use `/cadastro`.

### Contas de demonstração

O seed cria o espaço **Equipe Demonstração Local** com três responsáveis, cinco chamados cobrindo os quatro status e cargas ativas de 2, 1 e 0. O próximo chamado automático vai para Carla, que está sem chamados ativos. As três contas usam a senha `Demo12345!`:

| Pessoa | E-mail |
| --- | --- |
| Ana Demonstração | `ana.demo@example.test` |
| Bruno Demonstração | `bruno.demo@example.test` |
| Carla Demonstração | `carla.demo@example.test` |

São endereços `.test` sem caixa postal e senha fixa, destinados apenas à avaliação local. Executar o seed novamente reconhece a equipe já criada e não altera os dados; contas conflitantes interrompem a carga com erro explícito.

### Instalação manual equivalente

```sh
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

O `key:generate` vale para a primeira instalação. Não regenere a chave de um ambiente em uso sem planejar o impacto em sessões e dados cifrados.

### Windows sem PHP configurado ou Composer no PATH

Há um preparador opcional que aproveita o PHP e o Node já instalados, cria uma configuração de PHP **restrita ao projeto** em `.tools` e baixa o Composer oficial com verificação SHA-256 quando necessário:

```powershell
.\scripts\setup.ps1
.\scripts\php.ps1 artisan db:seed --class=DemoSeeder
.\scripts\php.ps1 artisan serve --host=127.0.0.1 --port=8000
```

O script precisa de acesso à internet. Ele não altera o `php.ini` global nem a política de execução do PowerShell. O helper `scripts/php.ps1` usa `.tools/php.ini` quando existir e repassa essa configuração aos subprocessos PHP, o que é necessário porque comandos como `artisan test` iniciam processos filhos. Essa configuração não é versionada, pois contém caminhos da máquina.

### Equipe com pessoas reais

Cadastre uma conta em `/cadastro`: ela recebe um espaço de trabalho próprio e já aparece como responsável. Na aba **Equipe**, o dono gera um código de convite válido por sete dias. As outras pessoas criam suas contas e informam o código para entrar na mesma equipe, passando a receber chamados de forma automática ou manual. O seed padrão não cria usuário nenhum.

### Desenvolvimento com recarga automática

Em terminais separados:

```sh
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Ou `composer run dev` para subir os dois processos. O Vite só é necessário durante o desenvolvimento: depois de `npm run build`, o Laravel serve o frontend compilado. Se o Vite for interrompido de forma abrupta e o navegador passar a buscar assets na porta errada, apague o arquivo temporário `public/hot` e gere o build de novo.

## Configuração e banco

O `.env.example` traz as variáveis da execução local. A conexão padrão é `sqlite`, com o arquivo em `database/database.sqlite`; se definir `DB_DATABASE`, prefira caminho absoluto. Sessões e cache usam arquivos e as filas são síncronas, então nenhum serviço externo é exigido localmente. Na versão publicada, as sessões ficam no PostgreSQL e os cookies exigem HTTPS.

As datas são gravadas em UTC e exibidas em `America/Sao_Paulo`. A data de abertura é o `created_at`, gerado no servidor e preservado nas edições.

Para recomeçar **apenas em um banco local descartável**, `php artisan migrate:fresh --seed` apaga todas as tabelas e seus dados. Esse comando não faz parte do setup normal.

## Arquitetura e escolhas

A aplicação é um monólito com renderização via Inertia. O Laravel cuida de rotas, validação, persistência e regras de negócio; o Vue apresenta as páginas e formulários. Um único repositório e uma única aplicação reduzem o atrito entre frontend e backend.

```text
HTTP → autenticação + espaço de trabalho → Form Request → Controller → Action → TicketWriteTransaction
                                          ↓
                                   AssigneeSelector → Eloquent → SQLite/PostgreSQL
HTTP ← redirect / props Inertia ← Controller
            ↓
        páginas Vue → componentes de formulário, filtros e indicadores
```

| Local | Responsabilidade |
| --- | --- |
| `app/Enums` | Status, prioridades, rótulos e definição de carga ativa |
| `app/Http/Requests` | Validação de criação, edição e filtros |
| `app/Http/Controllers` | Coordenar respostas e consultas da interface |
| `app/Actions/Tickets` | Casos de uso de criação e edição |
| `app/Services/Tickets` | Escolha do responsável e serialização das escritas |
| `app/Services/Workspaces` | Criação e ativação de vínculos de equipe |
| `app/Models` | Relações, casts e escopo de chamados ativos |
| `database/migrations` e `seeders` | Esquema reproduzível e demonstração local opcional |
| `resources/js/Pages` | Telas de lista, criação, edição e detalhe |
| `resources/js/Components/Tickets` | Formulário compartilhado, filtros, badges e carga |
| `resources/css/app.css` | Estilos e breakpoints, com Tailwind 4 |
| `tests` | Regras, HTTP, persistência e concorrência real |

**Por que essa stack.** Laravel, Inertia e Vue cobrem o problema sem exigir uma API separada nem roteamento duplicado, que seria o custo de um SPA com backend REST para uma aplicação interna deste tamanho. O TypeScript mantém os contratos das propriedades entre servidor e telas. Tailwind e os ícones Lucide dão consistência visual sem construir um design system. O SQLite deixa a avaliação local trivial, e o mesmo código roda em PostgreSQL gerenciado na publicação.

**Como separei as responsabilidades.** Controllers coordenam HTTP, Form Requests validam, Actions implementam os casos de uso de escrita, Services isolam a escolha do responsável e a transação, e Enums são a fonte única de status e prioridades. Uso o Eloquent diretamente: não criei repositories ou interfaces que apenas repetiriam o ORM sem uma segunda implementação real. É código limpo dentro das convenções do Laravel, não Clean Architecture estrita e independente de framework.

### Escopo além do enunciado

O enunciado não pedia cadastro de responsáveis com tela própria: bastava que existissem e pudessem ser selecionados. Optei por ir além e implementar cadastro, login e espaços de trabalho isolados, por três motivos:

1. O cliente descreve um ambiente compartilhado, em que funcionários abrem chamados e o suporte acompanha. Sem identificar quem está usando o sistema, "responsável claro" continuaria sem solução: qualquer pessoa poderia reatribuir ou fechar o chamado de outra.
2. Os responsáveis passam a ser contas reais em vez de registros fictícios, o que torna a lista de responsáveis um reflexo da equipe, e não um cadastro paralelo a manter.
3. Permite publicar a aplicação em uma URL pública para demonstração sem expor os dados de quem a testar.

O custo é mais superfície de código e mais o que manter. Para compensar, o isolamento entre espaços é verificado por testes (route binding, validação de responsável e consultas), e o `DemoSeeder` mantém a avaliação local em um comando, sem obrigar quem for avaliar a criar três contas e trocar convites.

## Regras de negócio e decisões sobre pontos em aberto

1. **Chamados ativos:** `open` (aberto) e `in_progress` (em andamento). `resolved` e `closed` são concluídos: o atendimento terminou e não ocupa mais a carga de ninguém. É essa a definição de "em aberto" usada na distribuição.
2. **Criação:** todo chamado começa em aberto. O cliente não escolhe ID nem data de abertura.
3. **Prioridade:** baixa, média ou alta. Não pesa na distribuição, porque o requisito compara a quantidade de chamados, não o esforço.
4. **Automático:** escolhe a menor quantidade de ativos, incluindo quem está com zero, e desempata pelo menor ID. É determinístico, sem promessa de rodízio histórico.
5. **Manual:** a escolha válida é respeitada independentemente da carga.
6. **Edição:** mantém o responsável atual por padrão. Redistribuir exige selecionar o modo automático e, nesse caso, o próprio chamado sai da comparação.
7. **Status:** qualquer transição entre os quatro valores é permitida, inclusive reabertura. Reabrir mantém o responsável, salvo escolha explícita diferente.
8. **Sem responsáveis:** erro de validação, sem gravar chamado incompleto.
9. **Lista:** busca por título e filtros de status, prioridade e responsável; 20 itens por página; ordenação por abertura decrescente com ID como desempate. Os resumos consideram todo o espaço de trabalho, não apenas o filtro aplicado.
10. **Limites:** título até 150 caracteres e descrição até 5.000, obrigatórios e validados no servidor.
11. **Edições simultâneas:** as escritas são serializadas, e duas edições do mesmo chamado terminam na última gravação recebida. Não há bloqueio otimista contra formulários abertos há muito tempo.
12. **Isolamento:** o cadastro cria um espaço privado e só se entra em outro por convite. Chamados e opções de responsáveis são filtrados pelo espaço ativo, inclusive nas rotas de detalhe e edição. Convites são guardados como hash e expiram em sete dias.
13. **Saída da equipe:** quem sai, ou é desligado pelo dono, deixa de aparecer nas opções e na distribuição automática, mas continua nomeado nos chamados que já atendeu. Preferi desativar o vínculo a apagá-lo, porque apagar exigiria reatribuir o histórico e perderia a informação de quem atendeu o quê. Quem retorna por convite reativa o mesmo registro, sem duplicar responsáveis.
14. **Papéis:** só o dono renomeia o espaço e desliga pessoas. O dono não pode ser removido nem sair do espaço que criou; como não há transferência de posse nesta versão, permitir isso deixaria a equipe sem responsável administrativo.

### Concorrência na atribuição

Escolher o responsável com menos chamados e gravar o chamado são duas operações que precisam acontecer juntas: se dois chamados forem abertos ao mesmo tempo, ambos podem ler a mesma carga e cair sobre a mesma pessoa.

Todas as Actions de escrita passam por `TicketWriteTransaction`. A primeira instrução dentro da transação atualiza a linha única de `ticket_write_locks`, antes de qualquer leitura de carga. No SQLite isso adquire a reserva de escrita e faz a outra gravação aguardar. A seleção do responsável e a gravação do chamado ficam na mesma transação.

Essa linha é infraestrutura de coordenação, não uma contagem de chamados. Ela evita depender do modo `IMMEDIATE`, que a versão do Laravel usada só aplica no PHP 8.4+. Há espera de 5 segundos por bloqueio, até três tentativas e uma mensagem compreensível se o banco continuar ocupado.

O `ConcurrentAssignmentTest` usa **dois processos PHP** sobre o mesmo arquivo SQLite, ou um schema PostgreSQL isolado, com sinais para sobrepor as transações. Ele verifica que o segundo processo espera e decide a partir da gravação anterior; não é um teste sequencial nem uma simulação.

## SQLite, PostgreSQL e publicação

O SQLite é o padrão local e atende produção quando persistência, backup e volume forem compatíveis. Para a versão publicada usei PostgreSQL gerenciado no Supabase, acessado somente pelo Laravel.

A publicação é **Vercel Container Image → PHP/Apache → Supabase**, com o mesmo Dockerfile que a CI constrói e testa. As sessões ficam no PostgreSQL, porque os arquivos do container são descartáveis. A suíte e o teste de concorrência passaram na CI com PostgreSQL 17.

O [guia de publicação](docs/deploy.md) descreve segredos, conexão e migrations. As migrations criam a estrutura e não importam registros do SQLite.

## Qualidade e testes

```sh
php artisan test
php vendor/bin/pint --test
npm run lint
npm run typecheck
npm run build
```

No Windows com a configuração local do projeto, use `.\scripts\php.ps1 artisan test`; o helper propaga o `.tools/php.ini` aos subprocessos. Também é possível chamar `php -c .tools\php.ini vendor\bin\phpunit` diretamente. Para formatar: `php vendor/bin/pint` e `npm run format`.

A suíte cobre atribuição manual e automática, empate, responsável sem chamados, status concluídos, reabertura, edição sem troca implícita, redistribuição, campos inválidos e internos, filtros, paginação, cadastro, login, isolamento entre espaços, convites, perfil, 404, o seed de demonstração e a concorrência. Os testes comuns usam SQLite em memória; o de concorrência usa arquivo temporário e, na CI com PostgreSQL, um schema exclusivo.

A [CI](.github/workflows/ci.yml) roda PHP 8.3/8.4, Node 22, PostgreSQL descartável, Chromium desktop e celular, e a imagem Docker.

## Segurança e limites assumidos

- CSRF nas rotas web, consultas parametrizadas, validação no servidor e campos de escrita declarados explicitamente.
- Descrições renderizadas como texto pelo Vue, sem HTML arbitrário.
- Chaves estrangeiras e valores de status e prioridade restritos nas migrations.
- Login por sessão do Laravel, senhas com hash, limite de tentativas e espaço isolado por vínculo de equipe. Convites são guardados como hash e expiram.
- O route binding de chamados e a validação de responsável são limitados ao espaço ativo, então um ID de outra equipe resulta em 404 ou erro de validação.
- Em produção, uma role PostgreSQL restrita ao schema da aplicação, sessões cifradas no banco, cabeçalhos contra enquadramento e adivinhação de tipo, e `no-store` nas páginas autenticadas.
- `.env`, banco local, dependências e ferramentas de máquina ficam fora do Git.
- Nesta versão não há exclusão de chamados, anexos, SLA, notificações, comentários, recuperação de senha por e-mail, verificação de e-mail ou histórico de eventos. A recuperação de senha depende de integrar um provedor de e-mail.
- O servidor de desenvolvimento escuta em `127.0.0.1`. Em produção, mantenha HTTPS, `APP_KEY` estável, backup do banco e segredos fora do repositório.
- O bloqueio global de escrita privilegia correção e simplicidade nesta escala; não é uma solução para alto volume distribuído.

## Demonstração

1. Rode o `DemoSeeder`, entre como Ana e abra a aba **Equipe** para ver os três responsáveis e suas cargas.
2. Crie um chamado com distribuição automática e confira que ele vai para Carla, que está com carga zero.
3. Edite um chamado para resolvido e observe a carga do responsável diminuir.
4. Mostre a seleção manual e o isolamento entre espaços de trabalho.
5. Explique o desempate por menor ID e a proteção contra escritas concorrentes.

## Referências e bibliotecas

- [Laravel](https://laravel.com/docs/13.x), framework PHP e esqueleto oficial.
- [Inertia](https://inertiajs.com/docs/v2/installation/server-side-setup), integração entre Laravel e Vue.
- [Vue](https://vuejs.org/guide/introduction.html) e [TypeScript](https://www.typescriptlang.org/docs/).
- [Tailwind CSS](https://tailwindcss.com/docs) e [Lucide](https://lucide.dev/), estilos e ícones.
- [Vite](https://vite.dev/guide/), build e desenvolvimento.
- [PHPUnit](https://docs.phpunit.de/), [Laravel Pint](https://laravel.com/docs/13.x/pint), [ESLint](https://eslint.org/) e [Prettier](https://prettier.io/).
- [SQLite: quando usar](https://www.sqlite.org/whentouse.html) e [transações](https://www.sqlite.org/lang_transaction.html).
- [Supabase: conexão PostgreSQL](https://supabase.com/docs/guides/database/connecting-to-postgres).

A interface é própria, sem template visual externo. As decisões de arquitetura estão registradas aqui e em [docs/arquitetura.md](docs/arquitetura.md).
