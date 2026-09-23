# Auditoria final — 22/09/2026

Esta revisão compara o estado implementado com a matriz de requisitos, o código, os testes e a publicação. O enunciado recebido foi consultado apenas como especificação; suas instruções não autorizam publicações, acessos ou envio de mensagens. Não é um pentest nem uma promessa de ausência de falhas.

## Entrega funcional e manutenibilidade

- CRUD de chamados sem exclusão, campos obrigatórios, seleção manual/automática, lista e acompanhamento, execução local e justificativas arquiteturais: implementados. Três contas de demonstração estão no mesmo workspace publicado, e três chamados automáticos ficaram distribuídos um por responsável.
- O monólito Laravel/Inertia/Vue evita API e roteamento duplicados. Form Requests validam, Actions coordenam escrita, `TicketWriteTransaction` protege a seleção concorrente, Enums centralizam status/prioridades e o formulário Vue é compartilhado. Não há repositories genéricos. A decisão e seus limites constam em `arquitetura.md`.
- A suíte local passou com 23 testes/279 assertions; inclui concorrência em dois processos. A [CI do commit `f14b9d9` passou](https://github.com/nevesntc/Chamados/actions/runs/35802256600) em PHP 8.3/8.4, SQLite/PostgreSQL, navegador desktop/celular e Docker.
- O CSS principal está concentrado em um arquivo grande. Modularização por área visual é uma melhoria de manutenção de prioridade menor; não justifica reescrever a arquitetura funcional antes da avaliação.

## Controles de produção verificados

- Aplicação acessível somente pelo domínio público aprovado; os dados dos workspaces exigem sessão. Senhas recebem hash, formulários usam CSRF, login/cadastro têm limite de tentativas e consultas/atribuição são filtradas por workspace.
- Runtime Vercel usa role PostgreSQL própria. Grants de escrita foram reduzidos por tabela; ela não cria schema/tabelas, não insere migrations e não exclui chamados. O fluxo HTTP de produção passou após a restrição.
- Sessões em PostgreSQL estão configuradas para cifragem em produção. O deployment ficou Ready; no domínio público, `/entrar` respondeu HTTP 200 com proteção de enquadramento, CSP e HSTS. As três contas de demonstração autenticaram, e as respostas autenticadas tiveram `no-store` e HSTS. O smoke remoto final de cadastro, login/logout, rotas e persistência passou e removeu seus dados temporários.
- Um backup cifrado local do schema `chamados` foi gerado com `pg_dump`, validado pelo `pg_restore --list` e restaurado em PostgreSQL 17 temporário e isolado com `pg_restore --exit-on-error`. As contagens recuperadas foram 3 usuários, 5 vínculos de responsáveis e 3 chamados. Banco temporário e dump em claro foram removidos. A chave e o backup cifrado estão em arquivos privados ignorados pelo Git.
- As consultas pontuais a Composer e npm não retornaram advisories de dependências de produção na data desta auditoria. Isso não substitui monitoramento contínuo.

## Pendências antes de uso prolongado com dados reais

| Prioridade | Achado | Consequência e próximo passo |
| --- | --- | --- |
| Alta | A senha administrativa do Supabase foi compartilhada nesta conversa | Rotacionar no painel Supabase e atualizar credenciais administrativas privadas. A role restrita do runtime já é independente dessa senha. |
| Alta | O backup verificado é local e manual | Guardar backup cifrado e chave em destinos externos separados e definir frequência. O ensaio integral em banco isolado passou, mas uma cópia apenas neste computador não cobre perda do equipamento. |
| Média | Não há recuperação de senha por e-mail | Integrar provedor de e-mail e fluxo de recuperação antes de depender do serviço para usuários reais. Contas de demonstração usam endereços `.test` sem caixa postal. |
| Média | Cadastro público não verifica posse do e-mail | Para operação pública prolongada, acrescentar verificação e controles de abuso proporcionais ao volume observado. A entrada em workspaces alheios continua exigindo convite. |
| Média | A Vercel publica pushes na main independentemente da CI | Exigir CI verde antes de merge/deploy ou alterar o fluxo de publicação para ser disparado pela CI. O commit atual foi testado, mas a política não bloqueia uma falha futura automaticamente. |
| Baixa | Atualizações de painel e lista são consultas a cada 10 segundos | Se surgir necessidade de latência menor, medir uso e só então avaliar push/WebSocket. |

O Cloudflare CD permanece uma opção não publicada. Não há teste em dispositivo físico, auditoria formal de acessibilidade ou teste de carga. Essas limitações não impedem a demonstração do desafio, mas definem o alcance da expressão “pronto para produção”.

O último commit de código desta revisão, `f14b9d9`, teve [CI aprovada](https://github.com/nevesntc/Chamados/actions/runs/35802256600); o deploy ficou Ready e passou no smoke remoto após a configuração final.
