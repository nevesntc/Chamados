# GitHub, Supabase e Cloudflare

## Fluxo de entrega

Push/PR executam Quality: PHP, SQLite/PostgreSQL, frontend, Chromium desktop/celular e imagem Docker. Em Actions, execute **Deploy Cloudflare** na main para repetir a CI e então migrar/publicar. O CD usa environment `production`; configure revisores se desejar aprovação. Não há publicação automática a cada commit.

## Banco

SQLite continua padrão local. `.env.production.example` documenta PostgreSQL. Credenciais ficam apenas no servidor, nunca em VITE_ ou Vue.

O endereço direto fornecido exige IPv6, indisponível no teste local. Em **Connect → Session pooler**, copie host e usuário exatos, porta 5432. Não adivinhe região. Para migrations, não usar transaction pooling.

DB_SSLMODE=require exige criptografia. Para verificação completa de certificado, provisionar CA e configurar verify-full. DB_SCHEMA=chamados separa o app do public; não exponha esse schema na Data API. Em produção real, use credenciais separadas para migration e runtime com privilégios mínimos.

`php artisan app:prepare-production-database` aceita somente PostgreSQL/schema chamados, cria o schema se necessário e aplica migrations/seed sem apagar dados. Não importa SQLite. Nunca execute PHPUnit ou migrate:fresh no banco remoto de produção.

## Configuração Cloudflare

PHP executa em Cloudflare Containers atrás de Worker. Verifique Workers Paid/compatibilidade e preços antes de publicar. Docker deve estar ativo. Limite de uma instância, suspensão após cinco minutos ociosos e possível cold start.

O Account ID enviado tinha 31 caracteres: confirmar o valor completo com 32. Não foi colocado no código.

No GitHub, configure environment **production**:

| Tipo | Nome | Conteúdo |
| --- | --- | --- |
| Variable | CLOUDFLARE_ACCOUNT_ID | ID completo da conta |
| Variable | APP_URL | URL HTTPS final |
| Variable | DB_HOST | Host do Session pooler |
| Variable | DB_USERNAME | Usuário exibido no Supabase |
| Secret | CLOUDFLARE_API_TOKEN | Token da conta com permissões Workers/Containers |
| Secret | APP_KEY | Chave Laravel estável |
| Secret | DB_PASSWORD | Senha do banco sem percent-encoding |

DB_DATABASE=postgres, DB_PORT=5432 e DB_SCHEMA=chamados estão no wrangler.jsonc. Gere APP_KEY uma vez com `php artisan key:generate --show` e guarde no Secret. Não regenere a cada deploy. Antes de uso real, substitua a senha compartilhada no chat e guarde a nova somente no gerenciador de segredos.

O workflow valida configurações, constrói a imagem, prepara o schema, migra e publica com Wrangler. Secrets são arquivos temporários ignorados pelo Git/Docker e removidos ao fim. Não são argumentos da linha de comando nem conteúdo da imagem. CI não usa credenciais Supabase: o PostgreSQL de testes é descartável.

A aplicação não possui login. Restrinja acesso na infraestrutura antes de usar dados reais e considere também a URL workers.dev. A presença de um cabeçalho não constitui autenticação. Políticas de acesso e deploy efetivo precisam ser configurados no ambiente.

## Local e operação

```sh
npm ci
# Crie .dev.vars privado contendo APP_KEY e DB_PASSWORD para tipagem dos secrets.
npm run cf:typecheck
# Docker ativo e configuração preenchida:
npx wrangler dev
```

Deploy pelo computador: `npx wrangler login`, configure vars e Account ID, prepare o banco e use `npx wrangler deploy --secrets-file caminho-privado.json`. O workflow Actions é preferível por exigir testes.

Após publicação, confira /up, /chamados, criação/edição e persistência após reinício. /up confirma boot, listagem confirma banco. Migrations devem manter compatibilidade com a versão anterior; rollback do Worker não restaura dados. Planeje backups do PostgreSQL.

## Referências

- [Containers](https://developers.cloudflare.com/containers/get-started/)
- [Segredos](https://developers.cloudflare.com/containers/examples/env-vars-and-secrets/)
- [Preços](https://developers.cloudflare.com/containers/pricing/)
- [Conexão Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres)
