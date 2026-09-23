# Matriz de requisitos

Esta matriz resume, com redação própria, os requisitos do desafio e onde cada um foi atendido. Atualize o estado quando a implementação mudar.

| Item | Critério | Implementação | Estado |
| --- | --- | --- | --- |
| 1.0 | Repositório GitHub | [nevesntc/Chamados](https://github.com/nevesntc/Chamados) | Publicado |
| 1.1 | README com instalação e execução | Setup rápido, manual e Windows; dados de demonstração e testes | Implementado |
| 1.2 | Justificativas técnicas e arquiteturais | README e [arquitetura.md](arquitetura.md) | Implementado |
| 2.1 | Criar, editar, listar e visualizar | `TicketController`, Actions e quatro páginas Vue | Implementado |
| 2.2 | Todos os campos mínimos | Migration de tickets, Form Requests, formulário e detalhe | Implementado |
| 2.3 | Campos adicionais opcionais | ID legível e data da última atualização | Implementado |
| 3.1–3.4 | Responsáveis selecionáveis, no mínimo três | `DemoSeeder` opcional cria três contas locais com login e cargas 2/1/0; cadastro e convite continuam disponíveis para equipes reais | Implementado e verificável em instalação limpa |
| 4.1 | Atribuição automática pela menor carga | `AssigneeSelector`, com testes de carga zero, empate e concorrência | Implementado |
| 4.2 | Escolha manual | Formulário e validação do responsável no espaço ativo | Implementado |
| 4.3 | Definição explícita de "em aberto" | `TicketStatus::activeValues` e justificativa no README | Implementado |
| 5.1 | Lista de chamados | `Index.vue`, paginação e detalhe | Implementado |
| 5.2 | Organização útil ao trabalho | Busca, filtros, prioridades e carga por responsável | Implementado |
| 6.1–6.2 | Execução local reproduzível | SQLite, migrations, seed padrão vazio e `DemoSeeder` opcional documentado no README | Implementado |
| Adicional | Cadastro, login e logout | Sessão Laravel, hash de senha, CSRF e limite de tentativas | Implementado |
| Adicional | Espaço de trabalho, equipe e perfil | Convite temporário, escopo por espaço, rotas próprias e troca de equipe | Implementado |
| Adicional | Gestão da equipe | Dono renomeia o espaço e desliga pessoas; membro sai por conta própria, e o histórico de atendimento é preservado | Implementado |
| Fundamentos | Testes, componentização e manutenção | PHPUnit, tipos, ESLint, Pint e formulário compartilhado | Implementado |
| Estilos | Framework CSS moderno e interface organizada | Tailwind 4 e ícones Lucide | Implementado |
| Referências | Bibliotecas e fontes informadas | Seção de referências no README | Implementado |

## Escopo adicional

Além do pedido, o projeto tem autenticação com espaços de trabalho isolados (justificada no README), CI em PostgreSQL e SQLite com PHP 8.3/8.4, navegador e Docker, e publicação em Vercel com PostgreSQL gerenciado no Supabase, por TLS e schema dedicado. Consulte o [guia de publicação](deploy.md).

Endereço público: [Central de Chamados](https://central-de-chamados-neves.vercel.app).
