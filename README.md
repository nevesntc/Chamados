# Central de Chamados

[![Quality](https://github.com/nevesntc/Chamados/actions/workflows/ci.yml/badge.svg)](https://github.com/nevesntc/Chamados/actions/workflows/ci.yml)

Aplicação web para organizar solicitações internas, acompanhar o atendimento e distribuir trabalho entre responsáveis. Construída com **Laravel 13, Inertia 2, Vue 3 e TypeScript**, com SQLite local.

## O que funciona

- Criação, edição, listagem paginada e visualização de chamados.
- Título, descrição, prioridade, status, responsável e data/hora de abertura.
- Três responsáveis cadastrados por seed e selecionáveis nos formulários.
- Atribuição automática pela menor quantidade de chamados ativos ou escolha manual.
- Busca por título, filtros combináveis e preservados na URL.
- Resumo geral e carga atual por responsável.
- Interface responsiva em português, validação por campo e feedback de gravação.
- Testes de regras e rotas, incluindo duas atribuições concorrentes em processos independentes.
- CI: PHP 8.3/8.4, SQLite/PostgreSQL 17, navegador desktop/celular e Docker.
- CD manual para Cloudflare Workers + Containers e configuração Supabase.

O projeto é uma **demonstração local sem login**, não uma instalação aberta ao público. Repositório: [nevesntc/Chamados](https://github.com/nevesntc/Chamados). A comunicação aos avaliadores cabe ao candidato; consulte [a matriz de requisitos](docs/requisitos.md).

## Pré-requisitos

- PHP 8.3 ou superior com extensões do Laravel, incluindo ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_sqlite, session, tokenizer e XML. ZIP facilita a instalação pelo Composer.
- Composer 2.
- Node.js 22.13+ e npm. Validado localmente com Node 22.21 e PHP 8.3.30.
- Git para obter o projeto. SQLite funciona pelo driver PHP; não precisa instalar um servidor de banco.
- Diretórios `storage` e `bootstrap/cache` graváveis.

Verifique `php -v`, `php -m`, `composer --version` e `node --version`. As versões resolvidas estão em `composer.lock` e `package-lock.json`.

## Instalação rápida

Na pasta do projeto, após clonar ou extrair o código:

```sh
composer install
composer run setup
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000**. O setup cria `.env` e o SQLite se não existirem, gera uma chave somente se estiver ausente, executa migrations e seed de responsáveis, instala o frontend com `npm ci` e gera o build. Não apaga chamados existentes.

### Windows com PHP sem extensões configuradas ou Composer no PATH

Há um preparador opcional que usa o PHP e o Node já instalados, cria uma configuração PHP **somente do projeto** em `.tools` e baixa o Composer oficial com verificação SHA-256 quando necessário:

```powershell
.\scripts\setup.ps1
.\scripts\php.ps1 artisan serve --host=127.0.0.1 --port=8000
```

O script requer acesso à internet. Não altera o `php.ini` global nem a política de execução do PowerShell. Se a política local impedir scripts, use os passos manuais com seu PHP configurado.

Neste computador, a configuração local e o Composer já foram preparados. O helper `scripts/php.ps1` utiliza essa configuração automaticamente. Ela não é versionada, pois contém caminhos específicos da máquina.

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

O comando manual `key:generate` é para a primeira instalação. Não regenere a chave de um ambiente já em uso sem planejar o impacto em dados criptografados e sessões.

### Dados demonstrativos

```sh
php artisan db:seed --class=DemoSeeder
```

Cria oito chamados fictícios com diferentes status e prioridades, somente se a tabela estiver vazia. Pode ser executado novamente sem duplicar os dados. O seed padrão cria Ana Martins, Bruno Costa e Carla Souza, sem duplicação. Não há cadastro de responsáveis na interface, conforme o escopo.

### Desenvolvimento com atualização automática

Em terminais separados:

```sh
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Ou `composer run dev` para os dois processos. No Windows com o PHP local, defina antes `$env:PHPRC = (Resolve-Path .tools/php.ini).Path` e use `php .tools/composer.phar run dev` se o Composer não estiver no PATH.

O Vite só é necessário durante desenvolvimento. Após `npm run build`, o Laravel serve o frontend compilado. Se interromper o Vite de forma abrupta e o navegador buscar assets na porta errada, remova apenas o arquivo temporário `public/hot` e gere o build novamente.

## Configuração e banco

O `.env.example` contém todas as variáveis necessárias para execução local. A conexão padrão é `sqlite`, e o arquivo é `database/database.sqlite`. Se definir `DB_DATABASE`, prefira caminho absoluto. Sessões e cache usam arquivos; filas são síncronas. Nenhum serviço externo é exigido.

Datas são gravadas em UTC e apresentadas em `America/Sao_Paulo`. A abertura usa `created_at`, gerado no servidor e preservado na edição.

Para recomeçar **somente em um banco local descartável**, `php artisan migrate:fresh --seed` apaga todas as tabelas e seus dados. Esse comando não faz parte do setup normal e não deve ser usado em banco com dados a preservar.

## Arquitetura e escolhas

É um monólito com renderização via Inertia. Laravel cuida das rotas, validação, persistência e regras; Vue apresenta as páginas e formulários. Um único repositório e uma única aplicação reduzem atrito entre frontend e backend.

```text
HTTP → Form Request → Controller → Action → TicketWriteTransaction
                                           ↓
                                    AssigneeSelector → Eloquent → SQLite
HTTP ← redirect / Inertia props ← Controller
             ↓
         páginas Vue → componentes de formulário, filtros e indicadores
```

| Local | Responsabilidade |
| --- | --- |
| `app/Enums` | Status, prioridades, rótulos e definição de carga ativa |
| `app/Http/Requests` | Validação de criação, edição e filtros |
| `app/Http/Controllers` | Coordenar respostas e consultas para a interface |
| `app/Actions/Tickets` | Casos de uso de criação e edição |
| `app/Services/Tickets` | Escolha de responsável e serialização das escritas |
| `app/Models` | Relações, casts e escopo de chamados ativos |
| `database/migrations` e `seeders` | Esquema reproduzível e dados iniciais |
| `resources/js/Pages` | Telas de lista, criação, edição e detalhe |
| `resources/js/Components/Tickets` | Formulário compartilhado, filtros, badges e carga |
| `resources/css/app.css` | Estilos e breakpoints da interface, com Tailwind 4 |
| `tests` | Regras, HTTP, persistência e concorrência real |

Aplicamos responsabilidade única, nomes explícitos, tipos, validação centralizada e compartilhamento do formulário. Eloquent é utilizado diretamente; não criamos interfaces e repositories que apenas repetiriam o ORM. Isso é código limpo dentro das convenções do Laravel, não Clean Architecture estrita independente de framework.

**Por que essa stack:** Laravel, Inertia e Vue alinham-se às tecnologias indicadas no desafio. Inertia dispensa uma API separada e roteamento duplicado para este frontend. TypeScript ajuda a manter os contratos de propriedades. Tailwind e ícones Lucide sustentam uma interface consistente sem um sistema visual complexo. SQLite facilita a avaliação local.

Mudanças de arquitetura devem atualizar esta seção, as [decisões registradas](docs/arquitetura.md), os testes afetados e a [matriz de requisitos](docs/requisitos.md) na mesma alteração.

## Regras de negócio e respostas às ambiguidades

1. **Ativos:** `open` (aberto) e `in_progress` (em andamento). `resolved` e `closed` são concluídos: o atendimento já terminou e não ocupa a carga.
2. **Criação:** sempre inicia em aberto. O cliente não escolhe ID nem data de abertura.
3. **Prioridade:** baixa, média ou alta. Não recebe peso na distribuição, pois o requisito compara a quantidade de chamados.
4. **Automático:** menor quantidade de ativos, incluindo pessoas com zero; empate pelo menor ID. É determinístico, sem promessa de rodízio histórico.
5. **Manual:** a escolha válida é respeitada independentemente da carga.
6. **Edição:** começa mantendo o responsável atual. A redistribuição exige selecionar automático; nesse caso, o próprio chamado é excluído da comparação.
7. **Mudança de status:** permitimos quaisquer transições entre os quatro valores, inclusive reabertura. Reabrir mantém a pessoa responsável salvo escolha explícita diferente.
8. **Sem responsáveis:** erro de validação e nenhum chamado incompleto salvo.
9. **Busca e lista:** título, status, prioridade e responsável; 20 itens por página; abertura decrescente e ID decrescente como desempate. Os resumos representam toda a base, não apenas o filtro.
10. **Limites:** título de até 150 caracteres e descrição de até 5.000, obrigatórios e validados no backend.
11. **Atualizações simultâneas:** as escritas são serializadas; duas edições do mesmo chamado usam a última gravação recebida. Não há bloqueio otimista contra formulários antigos nesta versão.

### Concorrência na atribuição

Todas as Actions de escrita usam `TicketWriteTransaction`. A primeira instrução dentro da transação atualiza a linha única de `ticket_write_locks`, antes de qualquer leitura da carga. No SQLite, isso adquire a reserva de escrita e faz outra gravação aguardar. Selecionar o responsável e salvar o chamado acontecem na mesma transação.

A linha é infraestrutura de coordenação, não uma contagem de chamados. Evita depender de `IMMEDIATE`, que a versão instalada do Laravel só aplica no PHP 8.4+. Há espera de 5 segundos por bloqueio, até três tentativas transacionais e erro compreensível se o SQLite continuar ocupado. Não use escrita direta fora das Actions para operações da aplicação.

O teste `ConcurrentAssignmentTest` usa **dois processos PHP**, com o mesmo arquivo SQLite ou schema PostgreSQL isolado, com sinais para sobrepor as transações. Ele verifica que o segundo processo espera e escolhe com base na gravação anterior. Não é apenas um teste sequencial ou uma simulação de banco.

## SQLite, PostgreSQL e publicação

SQLite permanece como padrão local e pode servir produção quando persistência, backup e volume forem compatíveis. Para esta publicação, usamos PostgreSQL gerenciado no Supabase, acessado somente pelo Laravel.

A estrutura é **Cloudflare Worker → container PHP/Apache → Supabase**. PHP permanece no container. Sessões ficam no PostgreSQL; arquivos do container são descartáveis. Não há API separada.

A configuração aceita schema dedicado e TLS. A suíte e a concorrência real passaram na CI com PostgreSQL 17. O Supabase PostgreSQL 17.6 também recebeu migrations e seed por Session pooler com TLS; os testes destrutivos não rodam nele. Veja [evidências](docs/validacao.md).

A alternativa Vercel usa o mesmo Dockerfile por Container Images (beta), configurado em vercel.json. Ainda exige validação no provedor após autenticação.

Siga [o guia de publicação](docs/deploy.md) para configurar segredos, conexão e CD manual. Migrations não importam registros do SQLite. A aplicação continua sem login; restrinja acesso antes de usar dados reais.

## Qualidade e testes

```sh
php artisan test
php vendor/bin/pint --test
npm run lint
npm run typecheck
npm run build
```

No Windows com a configuração do projeto, substitua `php` por `.\\scripts\\php.ps1`. Para usar o PHPUnit diretamente: `.\\scripts\\php.ps1 vendor/bin/phpunit`.

Formatar: `php vendor/bin/pint` e `npm run format`. ESLint cuida dos problemas de código; Prettier cuida do layout dos arquivos Vue/TS/CSS.

A suíte cobre atribuições manual/automática, empate, pessoa sem chamados, status concluídos, reabertura, edição sem troca implícita, redistribuição, campos inválidos e internos, filtros, paginação, seeds, 404 e concorrência. Localmente, os testes comuns usam SQLite em memória; o concorrente usa arquivo temporário. Na CI PostgreSQL, os testes usam banco descartável e a concorrência cria um schema temporário exclusivo.

A [CI](.github/workflows/ci.yml) verifica PHP 8.3/8.4, Node 22, PostgreSQL descartável, Chromium desktop/celular e imagem Docker. O [CD](.github/workflows/deploy.yml) roda manualmente na main e exige a CI aprovada. Veja as evidências locais e o roteiro de navegador em [validação](docs/validacao.md).

## Segurança e limites deliberados

- CSRF nas rotas web, consultas parametrizadas, validação no servidor e campos de escrita selecionados explicitamente.
- Descrição renderizada como texto por Vue, sem HTML arbitrário.
- Chaves estrangeiras e valores permitidos de status/prioridade definidos nas migrations.
- `.env`, SQLite, dependências e ferramentas locais ignorados pelo Git.
- Sem login, permissões, exclusão, anexos, SLA, notificações, comentários ou histórico de eventos nesta primeira versão.
- O servidor de desenvolvimento fica em `127.0.0.1`; autenticação e configuração de produção são necessárias antes de publicar para uso real.
- O bloqueio global de escrita privilegia correção e simplicidade para esta escala; não é uma solução de alto volume distribuído.
- Não há promessa de ausência de bugs. Verificações executadas e limitações conhecidas estão registradas.

## Colaboração humana e com IA

Leia [AGENTS.md](AGENTS.md) antes de alterar código. [CLAUDE.md](CLAUDE.md) aponta para a mesma fonte de instruções. Os documentos descrevem invariantes, estrutura e comandos para evitar regras divergentes entre assistentes.

A [auditoria inicial](docs/auditoria-e-plano.md) é um registro histórico anterior à implementação. Em caso de diferença, o README e as decisões de arquitetura atuais descrevem o estado implementado.

## Entrega e demonstração

1. Abra a lista e mostre carga e filtros.
2. Crie um chamado automático e confira o responsável.
3. Edite para resolvido e observe a carga diminuir.
4. Mostre a seleção manual e um teste da regra.
5. Explique o desempate e a proteção contra concorrência.

Antes de entregar: executar o setup em cópia limpa, revisar as pendências da matriz, conferir o repositório GitHub, conceder acesso aos avaliadores quando privado e enviar o endereço pelo canal combinado. O enunciado original e suas reproduções não devem ser incluídos no repositório.

## Referências e bibliotecas

- [Laravel](https://laravel.com/docs/13.x), framework PHP e esqueleto oficial; [notas da versão](https://laravel.com/docs/13.x/releases).
- [Inertia](https://inertiajs.com/docs/v2/installation/server-side-setup), integração Laravel/Vue.
- [Vue](https://vuejs.org/guide/introduction.html) e [TypeScript](https://www.typescriptlang.org/docs/).
- [Tailwind CSS](https://tailwindcss.com/docs) e [Lucide](https://lucide.dev/), estilos e ícones.
- [Vite](https://vite.dev/guide/), build e desenvolvimento.
- [PHPUnit](https://docs.phpunit.de/), [Laravel Pint](https://laravel.com/docs/13.x/pint), [ESLint](https://eslint.org/) e [Prettier](https://prettier.io/).
- [SQLite: usos apropriados](https://www.sqlite.org/whentouse.html) e [transações](https://www.sqlite.org/lang_transaction.html).
- [Supabase: conexão PostgreSQL](https://supabase.com/docs/guides/database/connecting-to-postgres), referência para evolução futura.

Interface própria, sem template visual externo. Assistência de IA usada para planejamento, implementação e verificação; o candidato deve revisar e entender as decisões antes de apresentar.
