# Histórico

## 0.3.0 — 2026-09-22

- `DemoSeeder` opcional para avaliação local: três contas com login, chamados nos quatro status e cargas ativas 2/1/0. O seed padrão continua vazio.
- O helper PHP do Windows propaga a configuração local aos processos filhos, corrigindo `artisan test`.
- O filtro de responsável na listagem passa a validar o espaço de trabalho ativo.
- README e matriz de requisitos revisados para a instalação limpa, com a justificativa do escopo além do enunciado.

## 0.2.0 — 2026-09-22

- Cadastro, login e espaços de trabalho isolados, com convite temporário e troca de equipe.
- Publicação no GitHub e CI com SQLite e PostgreSQL 17 em PHP 8.3/8.4.
- Testes de navegador em Chromium desktop e celular, e smoke da imagem Docker.
- Publicação em Vercel sobre PostgreSQL gerenciado no Supabase, com TLS e schema dedicado.

## 0.1.0 — 2026-09-21

- Primeira versão com Laravel, Inertia, Vue, TypeScript e SQLite.
- Criação, edição, consulta e listagem de chamados com filtros e paginação.
- Atribuição manual e automática com proteção contra escritas concorrentes.
- Interface responsiva em português e validação no servidor.
- Testes de regras e integração, e documentação de arquitetura e requisitos.
