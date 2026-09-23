# Publicação: GitHub, Supabase, Vercel e Cloudflare

## Fluxo de entrega

Push/PR executam Quality: PHP, SQLite/PostgreSQL, frontend, Chromium desktop/celular e imagem Docker. A Vercel, ligada à main, cria um deploy a cada push; isso não substitui a CI, e migrations devem ser aplicadas antes de publicar código que as exige. Para a opção Cloudflare, execute **Deploy Cloudflare** na main para repetir a CI e então migrar/publicar. Esse CD usa environment `production`.

## Banco

SQLite continua padrão local. `.env.production.example` documenta PostgreSQL. Credenciais ficam apenas no servidor, nunca em VITE_ ou Vue.

O endereço direto fornecido exige IPv6, indisponível no teste local. A conexão pelo Session pooler foi validada e o schema foi inicializado em 2026-09-22. Em **Connect → Session pooler**, copie host e usuário exatos, porta 5432. Não adivinhe região. Para migrations, não usar transaction pooling.

DB_SSLMODE=require exige criptografia. Para verificação completa de certificado, provisionar CA e configurar verify-full. DB_SCHEMA=chamados separa o app do public; não exponha esse schema na Data API. O runtime da Vercel agora usa `chamados_runtime`, com leitura, sequências e apenas escritas necessárias por tabela no schema da aplicação, sem criação de objetos nem exclusão de chamados. Crie a role com senha aleatória fora do Git e execute `deploy/runtime-grants.sql` como administrador para reproduzir os grants. Migrations usam credencial administrativa separada; conceda à role de runtime as permissões de uma nova tabela antes de publicar código que a escreva. A senha administrativa compartilhada na conversa ainda precisa ser rotacionada no painel Supabase e atualizada nos locais privados que a utilizarem.

`php artisan app:prepare-production-database` aceita somente PostgreSQL/schema chamados, cria o schema se necessário e aplica migrations/seed sem apagar dados. O seed padrão é vazio; pessoas entram pelo cadastro real. Não importa SQLite. Nunca execute PHPUnit ou migrate:fresh no banco remoto de produção.

## Configuração Cloudflare

PHP executa em Cloudflare Containers atrás de Worker. Verifique Workers Paid/compatibilidade e preços antes de publicar. Docker deve estar ativo. Limite de uma instância, suspensão após cinco minutos ociosos e possível cold start.

O Account ID enviado tinha 31 caracteres: confirmar o valor completo com 32. Não foi colocado no código.

O environment **production** já existe. APP_KEY, DB_PASSWORD, DB_HOST e DB_USERNAME foram configurados. Faltam Account ID correto, token Cloudflare e APP_URL final. A sessão Wrangler local disponível pertence a outra conta.

Configurações do environment:

| Tipo | Nome | Conteúdo |
| --- | --- | --- |
| Variable | CLOUDFLARE_ACCOUNT_ID | ID completo da conta |
| Variable | APP_URL | URL HTTPS final |
| Variable | DB_HOST | Host do Session pooler |
| Variable | DB_USERNAME | Usuário exibido no Supabase |
| Secret | CLOUDFLARE_API_TOKEN | Token da conta com permissões Workers/Containers |
| Secret | APP_KEY | Chave Laravel estável |
| Secret | DB_PASSWORD | Senha do banco sem percent-encoding |

DB_DATABASE=postgres, DB_PORT=5432 e DB_SCHEMA=chamados estão no wrangler.jsonc. Gere APP_KEY uma vez com `php artisan key:generate --show` e guarde no Secret. Não regenere a cada deploy. Antes de uso real, substitua a senha compartilhada no chat e atualize o Secret DB_PASSWORD e o arquivo privado local correspondente.

O workflow valida configurações, constrói a imagem, prepara o schema, migra e publica com Wrangler. Secrets são arquivos temporários ignorados pelo Git/Docker e removidos ao fim. Não são argumentos da linha de comando nem conteúdo da imagem. CI não usa credenciais Supabase: o PostgreSQL de testes é descartável.

A aplicação exige login e isola workspaces. A implantação Cloudflare ainda depende de credenciais e conta correta; proteja também endpoints administrativos da plataforma.

## Local e operação

```sh
npm ci
# Crie .dev.vars privado contendo APP_KEY e DB_PASSWORD para tipagem dos secrets.
npm run cf:typecheck
# Docker ativo e configuração preenchida:
npx wrangler dev
```

Deploy pelo computador: `npx wrangler login`, configure vars e Account ID, prepare o banco e use `npx wrangler deploy --secrets-file caminho-privado.json`. O workflow Actions é preferível por exigir testes.

Após publicação, confira `/up`, redirecionamento de `/workspace` para `/entrar`, cadastro, equipe por convite, criação/edição e persistência após reinício. `/up` confirma boot, mas não valida login ou banco. Migrations devem manter compatibilidade com a versão anterior; rollback do Worker não restaura dados. Planeje backups do PostgreSQL.

## Referências

- [Containers](https://developers.cloudflare.com/containers/get-started/)
- [Segredos](https://developers.cloudflare.com/containers/examples/env-vars-and-secrets/)
- [Preços](https://developers.cloudflare.com/containers/pricing/)
- [Conexão Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres)

## Alternativa Vercel

Autorizada como opção pelo usuário. A documentação atual oferece Container Images em beta; vercel.json aponta o serviço app para o mesmo Dockerfile que a CI constrói e testa. Nenhuma cópia do Dockerfile e nenhum runtime PHP comunitário foram adicionados. A porta padrão é 80, já usada pelo Apache.

O projeto `central-de-chamados` na equipe `nevesntcs-projects` está ligado a `nevesntc/Chamados`. Configure as variáveis de `.env.production.example` no ambiente **Production**, com APP_URL final HTTPS, TRUST_PROXY=true, SESSION_DRIVER=database e SESSION_SECURE_COOKIE=true. APP_KEY e DB_PASSWORD são segredos: as configurações GitHub Secrets não são transferidas automaticamente para Vercel. Use os valores privados do ambiente existente, sem colocá-los no repositório.

O projeto agora se chama `central-de-chamados`, com o mesmo ID e vínculo GitHub. `https://central-de-chamados-neves.vercel.app` foi adicionado como domínio público específico e é o valor de APP_URL em Production. O usuário autorizou essa exceção; a proteção SSO `all_except_custom_domains` permanece nas URLs de deployment da Vercel. Não desative o SSO global para publicar outro domínio.

Em Production, `DB_USERNAME` e `DB_PASSWORD` são da role restrita, `SESSION_ENCRYPT=true`, `SESSION_DRIVER=database` e `SESSION_SECURE_COOKIE=true`. Alterações em variáveis de ambiente exigem novo deployment. Trocar a senha administrativa do Supabase não deve alterar a role de runtime; confirme `/up`, cadastro e login após a rotação. Credenciais de demonstração foram geradas localmente em `.tools/demo-accounts.json`, fora do Git. Não reutilize essas contas para dados reais.

O schema Supabase já está preparado. Para esta versão, aplique a migration `2026_09_22_000000_create_workspaces` antes do rollout; não há migration no boot do container. Previews devem usar banco/schema independente, nunca credenciais da produção. A autenticação protege os dados da aplicação; controles da hospedagem continuam sob responsabilidade da conta.

A versão com autenticação passou por smoke no domínio público: TLS, sessão/CSRF, cadastro, login/logout, rotas, criação de chamado e persistência após novo login. O script local removeu a conta e o chamado temporários. Convite e isolamento foram conferidos em testes de integração e navegador local; não foram repetidos por esse smoke remoto. Resultados em `validacao.md`. O CD Cloudflare continua disponível; não executar dois destinos por padrão.

[Documentação oficial de Container Images](https://vercel.com/docs/functions/container-images).

## Backup manual validado

O utilitário versionado `scripts/backup-production.php` exige `pg_dump` e `pg_restore` da mesma versão principal do servidor (17 neste projeto), além do arquivo privado `.tools/runtime-credential.json`. No Windows deste ambiente:

```powershell
$env:PG_DUMP_BIN='C:\Program Files\PostgreSQL\17\bin\pg_dump.exe'
$env:PG_RESTORE_BIN='C:\Program Files\PostgreSQL\17\bin\pg_restore.exe'
php -c .tools/php.ini scripts/backup-production.php create
php -c .tools/php.ini scripts/backup-production.php verify .tools/backups/NOME_DO_ARQUIVO.dump.enc
```

O comando `create` gera dump custom apenas do schema `chamados`, cifra com AES-256-GCM e valida a lista de objetos com `pg_restore`. `.tools/backup-key.base64` e o arquivo `.dump.enc` devem ser guardados **separadamente**, fora deste computador, com acesso limitado. A chave não pode ser recuperada do repositório. O ensaio local usou `decrypt BACKUP DESTINO` e `pg_restore` em PostgreSQL temporário isolado; as contagens recuperadas foram conferidas e o dump decifrado foi apagado. Nunca restaure por cima da produção sem plano aprovado. O utilitário limita o dump a 256 MiB e carrega o conteúdo em memória, por isso precisa ser substituído por backup streaming para volumes maiores. Ainda não há cópia externa periódica; ver [auditoria final](auditoria-final.md).
