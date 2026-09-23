# Publicação: GitHub, Supabase e Vercel

## Fluxo de entrega

Push e pull request executam o workflow **Quality**: PHP 8.3/8.4, SQLite e PostgreSQL, frontend, Chromium desktop/celular e a imagem Docker. A Vercel, ligada à branch `main`, cria um deploy a cada push. O deploy não substitui a CI, e as migrations precisam ser aplicadas antes de publicar código que dependa delas.

## Banco

O SQLite continua sendo o padrão local; o PostgreSQL é usado apenas na versão publicada. As credenciais ficam somente no ambiente do servidor, nunca em variáveis `VITE_` ou no código Vue.

A conexão direta do Supabase exige IPv6. Use a conexão pelo **Session pooler**, em *Connect → Session pooler*: copie host e usuário exatos, na porta 5432, sem adivinhar a região. Não use transaction pooling para migrations.

`DB_SSLMODE=require` exige criptografia; para verificação completa de certificado, provisione a CA e configure `verify-full`. `DB_SCHEMA=chamados` separa a aplicação do schema `public`, e esse schema não deve ser exposto na Data API.

O runtime usa a role `chamados_runtime`, com leitura, uso das sequências e apenas as escritas necessárias por tabela dentro do schema da aplicação, sem criar objetos nem excluir chamados. Crie a role com senha aleatória fora do Git e execute `deploy/runtime-grants.sql` como administrador para reproduzir os grants. As migrations usam uma credencial administrativa separada; ao acrescentar uma tabela, conceda as permissões à role de runtime antes de publicar o código que a escreve. Rotacione periodicamente a senha administrativa no painel do Supabase e atualize os locais privados que a utilizarem.

**Reaplique `deploy/runtime-grants.sql` ao publicar a gestão de equipe.** Renomear o espaço exige `UPDATE` em `workspaces`, e desligar alguém exige `DELETE` em `workspace_members`; sem esses grants as duas ações falham apenas em produção. O arquivo também concede escrita em `cache` e `cache_locks`, necessária porque `CACHE_STORE=database` guarda os contadores de tentativa: com o valor `array` anterior, o contador vivia só durante a requisição e o limite não valia entre chamadas.

`php artisan app:prepare-production-database` aceita somente PostgreSQL com o schema `chamados`, cria o schema quando necessário e aplica migrations e seed sem apagar dados. O seed padrão é vazio: as pessoas entram pelo cadastro real. O comando não importa dados do SQLite. Nunca execute PHPUnit ou `migrate:fresh` no banco de produção.

## Vercel

A Vercel oferece Container Images: o `vercel.json` aponta o serviço `app` para o mesmo Dockerfile que a CI constrói e testa, sem duplicar imagem nem trocar a stack. A porta padrão é 80, já usada pelo Apache.

O projeto `central-de-chamados`, na equipe `nevesntcs-projects`, está ligado a `nevesntc/Chamados`. Configure no ambiente **Production** as variáveis abaixo. `APP_KEY` e `DB_PASSWORD` são segredos, não vêm automaticamente dos GitHub Secrets e nunca entram no repositório. Gere a `APP_KEY` uma única vez com `php artisan key:generate --show` e não a regenere a cada deploy.

```ini
APP_NAME="Central de Chamados"
APP_ENV=production
APP_KEY=              # segredo
APP_DEBUG=false
APP_URL=https://seu-projeto.vercel.app
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
TRUST_PROXY=true
DB_CONNECTION=pgsql
DB_HOST=host-do-session-pooler
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=chamados_runtime.referencia-do-projeto
DB_PASSWORD=          # segredo
DB_SCHEMA=chamados
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_LIFETIME=30
SESSION_EXPIRE_ON_CLOSE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

`https://central-de-chamados-neves.vercel.app` é um domínio público específico do projeto e o valor de `APP_URL` em Production. A proteção SSO `all_except_custom_domains` permanece nas URLs de deployment da Vercel; não desative o SSO global para publicar outro domínio.

Alterações em variáveis de ambiente só valem após um novo deployment. Ambientes de preview devem usar banco ou schema independente, nunca as credenciais de produção.

## Região da aplicação

A computação da Vercel roda em `pdx1` (Oregon), fixada em `vercel.json`, porque o PostgreSQL do Supabase está em `us-west-2`. Antes disso a aplicação subia em `iad1` (Washington) e cada consulta atravessava os Estados Unidos: `/up`, que não toca o banco, respondia em 0,24 s, enquanto `/entrar`, que lê a sessão no PostgreSQL, levava 1,43 s. Como a sessão vive no banco, toda requisição paga esse trajeto ao menos duas vezes antes de qualquer consulta da tela.

Ao trocar de banco ou de região, confira o cabeçalho `X-Vercel-Id` da resposta: ele mostra a borda e a região de computação, nessa ordem.

## Proteção contra migration pendente

O container recusa subir quando o banco está atrás das migrations que o código espera. O `entrypoint` chama `php artisan app:assert-database-ready` depois de gerar os caches: o comando compara os arquivos de migration com o que foi aplicado e sai com erro listando o que falta. Sem isso, um deploy publicado antes da migration serve telas quebradas com 500, e a causa só aparece no console de quem estiver usando.

O comando não altera nada e depende apenas de leitura na tabela `migrations`, já permitida à role de runtime. Em compensação, um banco inacessível também impede o boot: é falha visível no deploy em vez de aplicação no ar sem conseguir responder.

A CI verifica os dois lados: o container precisa recusar subir contra um banco ainda não preparado e subir normalmente depois de `app:prepare-production-database`.

## Verificação após publicar

Confira `/up`, o redirecionamento de `/workspace` para `/entrar`, cadastro, equipe por convite, criação e edição de chamado, e persistência após novo login. O `/up` confirma que a aplicação subiu, mas não valida login nem banco.

As migrations devem manter compatibilidade com a versão anterior: o rollback do código não restaura dados. Planeje backups do PostgreSQL.

## Backup manual

O utilitário `scripts/backup-production.php` exige `pg_dump` e `pg_restore` da mesma versão principal do servidor (17 neste projeto), além de um arquivo privado de credenciais fora do Git. No Windows:

```powershell
$env:PG_DUMP_BIN='C:\Program Files\PostgreSQL\17\bin\pg_dump.exe'
$env:PG_RESTORE_BIN='C:\Program Files\PostgreSQL\17\bin\pg_restore.exe'
php scripts/backup-production.php create
php scripts/backup-production.php verify CAMINHO_DO_ARQUIVO.dump.enc
```

O comando `create` gera um dump em formato custom apenas do schema `chamados`, cifra com AES-256-GCM e valida a lista de objetos com `pg_restore`. A chave e o arquivo `.dump.enc` devem ser guardados **separadamente**, fora do computador de origem e com acesso limitado; a chave não pode ser recuperada do repositório. Nunca restaure por cima da produção sem um plano aprovado. O utilitário limita o dump a 256 MiB e carrega o conteúdo em memória, então precisa ser substituído por backup em streaming para volumes maiores. Ainda não há cópia externa periódica configurada.

## Referências

- [Vercel: Container Images](https://vercel.com/docs/functions/container-images)
- [Supabase: conexão PostgreSQL](https://supabase.com/docs/guides/database/connecting-to-postgres)
