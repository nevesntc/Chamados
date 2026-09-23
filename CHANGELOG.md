# Histórico

## 0.5.0 — 2026-09-23

- Sessão expira com 30 minutos de inatividade e termina ao fechar o navegador.
- Aplicação publicada passa a rodar na mesma região do banco, cortando cerca de 1 segundo por tela.
- Limites de tentativa isolados por ação, e permissões de produção para a gestão de equipe.

## 0.4.0 — 2026-09-23

- Gestão da equipe: o dono renomeia o espaço e desliga pessoas, e cada membro pode sair por conta própria.
- Quem sai deixa de receber chamados, mas continua nomeado nos que já atendeu; retornar por convite reativa o mesmo responsável.
- Troca de tela mais rápida: os itens do menu usam `prefetch` do Inertia e as atualizações periódicas deixam de disputar com a navegação.
- Teste de navegador cobrindo renomear, remover e sair da equipe.

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
