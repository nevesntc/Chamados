# Publicação: GitHub, Supabase e Vercel

## Fluxo de entrega

Push e pull request executam o workflow **Quality**: PHP 8.3/8.4, SQLite e PostgreSQL, frontend, Chromium desktop/celular e a imagem Docker. A Vercel, ligada à branch `main`, cria um deploy a cada push. O deploy não substitui a CI, e as migrations precisam ser aplicadas antes de publicar código que dependa delas.

## Banco

O SQLite continua sendo o padrão local. O arquivo `.env.production.example` documenta as variáveis do PostgreSQL. As credenciais ficam apenas no ambiente do servidor, nunca em variáveis `VITE_` ou no código Vue.

A conexão direta do Supabase exige IPv6. Use a conexão pelo **Session pooler**, em *Connect → Session pooler*: copie host e usuário exatos, na porta 5432, sem adivinhar a região. Não use transaction pooling para migrations.

`DB_SSLMODE=require` exige criptografia; para verificação completa de certificado, provisione a CA e configure `verify-full`. `DB_SCHEMA=chamados` separa a aplicação do schema `public`, e esse schema não deve ser exposto na Data API.

O runtime usa a role `chamados_runtime`, com leitura, uso das sequências e apenas as escritas necessárias por tabela dentro do schema da aplicação, sem criar objetos nem excluir chamados. Crie a role com senha aleatória fora do Git e execute `deploy/runtime-grants.sql` como administrador para reproduzir os grants. As migrations usam uma credencial administrativa separada; ao acrescentar uma tabela, conceda as permissões à role de runtime antes de publicar o código que a escreve. Rotacione periodicamente a senha administrativa no painel do Supabase e atualize os locais privados que a utilizarem.

`php artisan app:prepare-production-database` aceita somente PostgreSQL com o schema `chamados`, cria o schema quando necessário e aplica migrations e seed sem apagar dados. O seed padrão é vazio: as pessoas entram pelo cadastro real. O comando não importa dados do SQLite. Nunca execute PHPUnit ou `migrate:fresh` no banco de produção.

## Vercel

A Vercel oferece Container Images: o `vercel.json` aponta o serviço `app` para o mesmo Dockerfile que a CI constrói e testa, sem duplicar imagem nem trocar a stack. A porta padrão é 80, já usada pelo Apache.

O projeto `central-de-chamados`, na equipe `nevesntcs-projects`, está ligado a `nevesntc/Chamados`. Configure as variáveis de `.env.production.example` no ambiente **Production**, com `APP_URL` em HTTPS, `TRUST_PROXY=true`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true` e `SESSION_ENCRYPT=true`. `APP_KEY` e `DB_PASSWORD` são segredos e não vêm automaticamente dos GitHub Secrets. Gere a `APP_KEY` uma única vez com `php artisan key:generate --show` e não a regenere a cada deploy.

`https://central-de-chamados-neves.vercel.app` é um domínio público específico do projeto e o valor de `APP_URL` em Production. A proteção SSO `all_except_custom_domains` permanece nas URLs de deployment da Vercel; não desative o SSO global para publicar outro domínio.

Alterações em variáveis de ambiente só valem após um novo deployment. Ambientes de preview devem usar banco ou schema independente, nunca as credenciais de produção.

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
