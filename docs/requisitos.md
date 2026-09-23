# Matriz de requisitos e entrega

Esta matriz resume os requisitos com redação própria; não reproduz o documento confidencial. Atualize o status quando a implementação ou a entrega mudar.

| Item | Critério | Implementação / evidência | Estado |
| --- | --- | --- | --- |
| 1.0 | GitHub e acesso da equipe | [Repositório](https://github.com/nevesntc/Chamados); envio aos avaliadores pelo candidato | Publicado; envio aos avaliadores pendente |
| 1.1 | README com instalação e execução | Setup rápido, manual e Windows; dados e testes | Implementado |
| 1.2 | Justificativas técnicas e arquiteturais | README e arquitetura.md | Implementado |
| 2.1 | Criar, editar, listar e visualizar | TicketController, Actions e quatro páginas Vue | Implementado |
| 2.2 | Todos os campos mínimos | Migration tickets, Form Requests, formulário e detalhe | Implementado |
| 2.3 | Campos adicionais opcionais | ID legível e última atualização | Implementado |
| 3.1–3.4 | Responsáveis selecionáveis, mínimo três | Cadastro e convite criam responsáveis; produção possui três contas de demonstração no mesmo workspace e três chamados automáticos balanceados | Implementado e verificado na publicação; instalação limpa requer três cadastros |
| 4.1 | Atribuição automática pela menor carga | AssigneeSelector e testes com zero, empate e concorrência | Implementado |
| 4.2 | Escolha manual | Formulário e validação de responsável existente | Implementado |
| 4.3 | Definição explícita de não concluído | TicketStatus::activeValues e documentação | Implementado |
| 5.1 | Lista de chamados | Index.vue, paginação e detalhes | Implementado |
| 5.2 | Organização útil ao trabalho | Busca, filtros, prioridades e carga por responsável | Implementado |
| 6.1–6.2 | Execução local reproduzível | SQLite, migrations sem dados fictícios e comandos README | Implementado; evidência em validacao.md |
| Adicional | Cadastro, login e logout | Sessão Laravel, hash de senha, CSRF e limite de tentativas | Implementado; [CI aprovada](https://github.com/nevesntc/Chamados/actions/runs/35775044139) |
| Adicional | Workspace privado, equipe e perfil | Convite temporário, escopo por workspace, rotas próprias e troca de equipe | Implementado; [CI aprovada](https://github.com/nevesntc/Chamados/actions/runs/35775044139) |
| Fundamentos | Testes, componentização e manutenção | PHPUnit, tipos, lint, Pint e formulário compartilhado | Implementado |
| Estilos | Framework CSS moderno e interface organizada | Tailwind 4, CSS da aplicação, Lucide | Implementado |
| Referências | Bibliotecas e fontes informadas | Seção de referências no README | Implementado |

## Antes de enviar

- [x] Repositório informado pelo usuário: nevesntc/Chamados, público.
- [x] CI remota aprovada: PHP 8.3/8.4, SQLite/PostgreSQL, navegador e Docker.
- [x] Três responsáveis disponíveis e distribuição automática demonstrada na versão publicada.
- [ ] Conceder acesso se o repositório for privado.
- [ ] Revisar README e demonstrar o fluxo completo em instalação limpa.
- [ ] Enviar o link aos avaliadores pelo canal combinado.
- [ ] Conferir o prazo real: o documento conta 10 dias da confirmação de recebimento, cuja data não foi informada.
- [ ] Preparar a explicação das decisões; não apresentar código que o candidato não compreenda.

Não incluir PDF, texto colado, credenciais ou bancos locais na publicação.

## Escopo adicional

CI com PostgreSQL/SQLite, PHP 8.3/8.4, navegador e Docker. CD manual Cloudflare. Supabase com TLS/schema; conexão pelo Session pooler verificada; migrations e seed aplicados no schema dedicado. Ver [deploy](deploy.md) e [validação](validacao.md).

Vercel: projeto ligado ao GitHub, Container Image com autenticação publicada e migration aplicada. Domínio público principal: [Central de Chamados](https://central-de-chamados-neves.vercel.app). Cadastro, login, chamado e persistência foram conferidos nesse endereço; veja [validação](validacao.md).
