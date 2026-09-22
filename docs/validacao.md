# Evidências de validação

Data: 21/09/2026. Escopo: primeira versão local. Resultados observados, sem promessa de ausência absoluta de bugs.

## Ambiente

- Windows, PHP 8.3.30, Node 22.21.0 e npm 10.9.4.
- Laravel 13.32.0 e Inertia Laravel 2.0.27, conforme composer.lock.
- SQLite via PDO; configuração PHP local em .tools, sem alteração do php.ini global.
- Servidor em http://127.0.0.1:8000, somente loopback.

## Verificações executadas

| Verificação | Resultado |
| --- | --- |
| PHPUnit / php artisan test | 16 testes, 171 assertions, todos aprovados |
| Concorrência real | Dois processos, um arquivo SQLite, segundo aguarda o primeiro e escolhe outro responsável |
| Pint --test | Aprovado |
| npm run lint | Aprovado, zero warnings |
| npm run typecheck | Aprovado |
| npm run build | Aprovado |
| composer validate --strict | Manifesto válido |
| composer audit | Sem avisos de vulnerabilidade na consulta realizada |
| npm audit --omit=dev | Zero vulnerabilidades na consulta realizada |
| npm ci na cópia limpa | Instalou 228 pacotes, auditou 229 e reportou zero vulnerabilidades |
| scripts/php.ps1 -v | Helper executou PHP 8.3.30 corretamente |
| Parser PowerShell de scripts/setup.ps1 | Sintaxe validada; o preparador Windows completo não foi executado do zero |
| Git ignore | .env, ferramentas, banco, vendor e node_modules ignorados |
| /up | HTTP 200 |

O build final gera aproximadamente 22 kB de CSS e 300 kB de JavaScript antes de gzip (cerca de 6 kB e 103 kB comprimidos). São tamanhos de build, não medições de Core Web Vitals.

## Instalação limpa

Foi criada uma cópia apenas dos arquivos destinados ao repositório, sem .env, vendor, node_modules e database.sqlite. Usando o PHP configurado e o Composer local como ferramentas do ambiente, foram executados:

1. composer install --no-interaction --prefer-dist --no-progress.
2. composer run setup.
3. php artisan test.

Resultado: dependências instaladas pelos lockfiles, chave gerada, banco criado, cinco migrations executadas, três responsáveis populados, frontend compilado e os mesmos 16 testes/171 assertions aprovados.

Essa verificação foi feita no mesmo computador, em diretório separado. Não equivale a teste em outra máquina, Linux, PostgreSQL ou CI remota. Depois dela houve refinamento de estilos, foco de navegação e formatação, novamente verificados por lint, tipos, build e navegador. A configuração de fontes do Tailwind foi restringida explicitamente a resources/js para evitar que arquivos temporários influenciem o CSS.

## Navegador

Verificado em navegador Chromium integrado, com o backend real e assets compilados:

- Lista inicial com oito chamados demonstrativos.
- Abertura automática de um chamado de validação: empatados com dois ativos cada, o menor ID recebeu a solicitação.
- Detalhe exibiu título, descrição, prioridade, status, responsável e abertura.
- Edição abriu mantendo o responsável; mudança manual para Carla e status resolvido persistiu.
- Data de abertura permaneceu; data de atualização mudou.
- Busca por título encontrou somente o registro esperado; limpar filtros restaurou a lista.
- Busca sem correspondência exibiu orientação de estado vazio.
- Título só com espaços foi rejeitado pelo backend; mensagem em português e demais campos preservados.
- Desktop em 1440 × 1000 e celular em 390 × 844.
- Formulário no celular com foco de teclado visível; Tab levou do título à descrição.
- Navegação móvel abriu com Enter, moveu o foco para o menu e fechou com Escape, devolvendo o foco ao botão.
- Menu fechado não permanece acessível ao foco por estar invisível no breakpoint móvel.
- Corrigido excesso de largura causado pelo texto acessível da tabela. A página ficou com 375 px de conteúdo em viewport de 390 px, descontada a barra de rolagem; a tabela conserva sua rolagem interna.
- Nenhum erro ou warning no console na revisão final.

O banco local ficou com nove registros: oito do seed e um chamado demonstrativo criado no teste do navegador, posteriormente resolvido. Os testes automatizados não alteram esse banco.

## Limites e pendências

- CI remota configurada, ainda não executada no GitHub.
- PHP 8.4 incluído na matriz, mas não executado localmente.
- PostgreSQL/Supabase não configurado nem validado.
- Não foi feita certificação formal de acessibilidade, teste em dispositivo físico ou auditoria de desempenho.
- Fluxos no navegador foram verificados manualmente por automação interativa; não há suíte de navegador versionada.
- Sem autenticação/autorização para exposição pública.
- Última gravação vence em edições concorrentes do mesmo chamado; não há detecção de formulário desatualizado.
- Criar repositório remoto, conceder acesso e enviar o link continuam na matriz de entrega.

## Roteiro para repetir a revisão visual

1. Instalar seguindo README e executar DemoSeeder em banco local vazio.
2. Abrir a lista, filtrar por status/prioridade/responsável e buscar um título.
3. Abrir automaticamente e verificar a menor carga; editar manualmente e resolver.
4. Validar título vazio/composto por espaços e conferir a mensagem.
5. Conferir desktop, celular, Tab, Enter, Escape e foco visível.
6. Verificar console e executar as ferramentas de qualidade após mudanças.

## Ampliação em 2026-09-22

Executados localmente: PHPUnit 16 testes/171 assertions com PHPRC local; Pint; ESLint; vue-tsc; build Vite; tipos Wrangler/TypeScript. npm audit na instalação: zero vulnerabilidades.

Infraestrutura adicionada: matriz PostgreSQL 17/SQLite e PHP 8.3/8.4; Chromium desktop/celular; Docker com smoke HTTP; CD manual condicionado à CI. Resultados remotos serão registrados após a execução.

Supabase: DNS retorna apenas IPv6; conexão TCP indisponível nesta rede. Nenhuma credencial transmitida nem migration executada no projeto remoto. Session pooler solicitado. Docker local instalado, daemon inativo. Account ID Cloudflare recebido incompleto; token não fornecido. Deploy não executado.

### Resultado final da ampliação

[CI Quality aprovada](https://github.com/nevesntc/Chamados/actions/runs/35685673239), commit `8855443`:

- PHP 8.3 + SQLite: aprovado.
- PHP 8.4 + SQLite: aprovado.
- PHP 8.3 + PostgreSQL 17: aprovado.
- PHP 8.4 + PostgreSQL 17: aprovado.
- Chromium desktop/celular: ambos os fluxos de criação, detalhe e resolução aprovados.
- Docker: imagem PHP/Apache construída, schema dedicado preparado, migrations/seed aplicados e resposta Inertia de /chamados conferida contra PostgreSQL real do job.

O teste de concorrência realmente usa dois processos em ambos os bancos. A primeira rodada PostgreSQL revelou uma expectativa indevida de IDs reiniciados após rollback e um fixture com texto fora de UTF-8; ambos foram corrigidos. Não houve remoção de asserts para mascarar a diferença: a paginação compara os IDs efetivamente criados, com relógio congelado.

Wrangler 4.136.1: geração de tipos, TypeScript e `wrangler deploy --dry-run --containers-rollout none` aprovados. Esse dry-run compila o Worker sem publicar ou construir container; a imagem foi verificada no job Docker. Revisão dos 118 arquivos versionados confirmou UTF-8 válido e nenhuma ocorrência dos padrões de credenciais examinados; isso não equivale a auditoria de segurança completa.

Após receber os parâmetros Session pooler, conexão TLS ao Supabase PostgreSQL 17.6 confirmada. Schema chamados inicialmente ausente; comando app:prepare-production-database criou estrutura e executou seed. Verificação posterior: 3 responsáveis, 0 tickets, 1 linha de coordenação. Nenhum teste destrutivo foi executado no Supabase e nenhum outro schema foi migrado. O SQLite local foi preservado.

GitHub environment production criado, secrets APP_KEY/DB_PASSWORD e variables DB_HOST/DB_USERNAME configurados. Credenciais locais ficam em .env.supabase ignorado; nenhum segredo é parte do código ou imagem. Cloudflare: sessão local encontrada em conta diferente da solicitada. A publicação permanece pendente de Account ID correto, token da conta e URL final. Não houve deploy, contratação de plano ou ativação de acesso público à aplicação.

A interrupção temporária da revisão automática de permissões foi resolvida após a orientação do usuário para continuar; não houve contorno da revisão.

### Alternativa Vercel

CI da revisão documental 92097bd também aprovada: https://github.com/nevesntc/Chamados/actions/runs/35736539964. vercel.json foi validado com o schema oficial https://openapi.vercel.sh/vercel.json, reutilizando o Dockerfile testado. CLI Vercel 59.25.0 disponível, inicialmente sem sessão; publicação depende do login do usuário e configuração do projeto. Nenhum deploy Vercel validado ainda.
