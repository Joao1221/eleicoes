# Plano PWA do ApoiaCandidato

Análise atualizada em 17/05/2026 para transformar o sistema local `eleicoes` e o produto em produção `apoiacandidato.com.br` no app instalável `ApoiaCandidato`.

## Objetivo

Transformar o ApoiaCandidato em um Progressive Web App instalável, com experiência de app no celular/desktop, abertura direta do escritório Premium e boa adaptação mobile, sem implementar modo offline neste momento.

O PWA deve nascer com:

- Nome público: `ApoiaCandidato`.
- Site: `https://apoiacandidato.com.br`.
- Entrada recomendada do app: `/premium`, porque o uso recorrente é o escritório de campanha.
- Escopo recomendado: `/`, para cobrir a landing page, o Premium, páginas auxiliares e relatórios.
- Funcionamento atual: online-first. Se não houver internet, o navegador seguirá o comportamento normal de falha/conexão indisponível.

## Decisão de escopo

Neste ciclo, não vamos implementar offline.

Isso significa:

- Não criar `sw.js` agora.
- Não criar `offline.html` agora.
- Não usar Cache Storage, IndexedDB, Background Sync ou fila de ações.
- Não cachear dados autenticados do Premium.
- Não prometer leitura ou edição sem internet.
- Não alterar o fluxo de formulários POST em `premium_actions.php`.

O foco será deixar o ApoiaCandidato instalável e com aparência/comportamento de app, mantendo a aplicação dependente da conexão com o servidor.

Decisão adicional de acesso:

- Após o login, a sessão Premium deve permanecer ativa por um período prolongado.
- Ao abrir o app pelo ícone, o `start_url` leva para `/premium`.
- Se a sessão ainda estiver válida, o sistema reaproveita `premium_campaign_id` e abre a última campanha ativa.
- O usuário só deve inserir e-mail/senha novamente quando fizer logout, a sessão expirar, a conta perder acesso ou o navegador/dispositivo limpar cookies.

## Diagnóstico do sistema atual

O projeto é um app PHP tradicional, sem bundler frontend e sem dependências Node visíveis. A estrutura principal está em arquivos PHP na raiz, com CSS e JS em `assets/`.

Pontos favoráveis:

- O `.htaccess` já força HTTPS e domínio canônico `apoiacandidato.com.br`, requisito essencial para PWA em produção.
- As páginas principais já usam `viewport`, favicon e layout responsivo.
- O módulo Premium já concentra a experiência recorrente do usuário em `premium.php`, `assets/css/premium.css` e `assets/js/premium.js`.
- Há preferência de tema e onboarding em `localStorage`, o que combina bem com uma experiência instalada.
- A autenticação Premium já redireciona usuários logados para `/premium`, favorecendo o `start_url` do app.

Lacunas para o PWA instalável:

- Não há `manifest.webmanifest`.
- Não há ícones PWA dedicados; o `assets/favicon.png` atual é grande demais para servir sozinho como conjunto de ícones do app.
- Não há `apple-touch-icon`.
- Não há tags PWA nos heads.
- Não há script de UX para instalação, como captura de `beforeinstallprompt` no Chrome/Edge ou orientação manual para Safari/iOS.
- As tags PWA precisariam ser repetidas em várias páginas, pois ainda não existe um head/template compartilhado.

Pontos que ficam fora do escopo atual:

- Service worker.
- Tela offline.
- Cache persistente de páginas, APIs ou dados Premium.
- Snapshots offline de campanha.
- Escrita offline e sincronização posterior.

## Requisitos técnicos confirmados

Para ser instalável, o app precisa de um manifesto referenciado nas páginas, com `name` ou `short_name`, ícones de 192px e 512px, `start_url`, `display`/`display_override` e HTTPS em produção.

Service workers são úteis para offline, cache avançado, push e background sync, mas não serão implementados agora porque o escopo atual é instalação e experiência de app online.

No iOS, vale incluir metatags específicas e `apple-touch-icon` para controlar melhor título, ícone e modo standalone.

Referências oficiais:

- MDN, installabilidade PWA: https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable
- MDN, ícones PWA: https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/How_to/Define_app_icons
- Chrome DevTools, depuração de manifest e instalação: https://developer.chrome.com/docs/devtools/progressive-web-apps
- Apple, configuração de web apps no Safari/iOS: https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html

## Decisões de produto recomendadas

1. O app deve abrir em `/premium`.
   - Usuário logado entra direto no escritório.
   - Usuário deslogado vê login/apresentação.
   - A landing `/` continua existindo para aquisição e SEO.

2. O MVP será instalável, mas online-only.
   - O app depende da conexão com `apoiacandidato.com.br`.
   - Consultas, relatórios, login e ações do Premium continuam pedindo servidor.
   - Nenhum dado de campanha será salvo localmente para uso offline.
   - A sessão autenticada deve persistir para evitar novo login a cada abertura do app.

3. O nome visual do app será `ApoiaCandidato`.
   - Usar exatamente esse nome no manifesto, no iOS e nas mensagens da interface.
   - O domínio continua `apoiacandidato.com.br`.

4. A experiência instalada deve parecer app, sem virar outro produto.
   - Manter identidade visual atual.
   - Adicionar apenas controles discretos de instalação.
   - Evitar popups agressivos.

## Arquitetura proposta

Arquivos novos:

- `manifest.webmanifest`
- `assets/js/pwa.js`
- `assets/icons/icon-192.png`
- `assets/icons/icon-512.png`
- `assets/icons/icon-maskable-192.png`
- `assets/icons/icon-maskable-512.png`
- `apple-touch-icon.png`

Não criar nesta fase:

- `sw.js`
- `offline.html`
- qualquer arquivo de fila, snapshot ou banco local

Alterações em PHP:

- Criar em `premium_helpers.php` uma função como `premium_render_pwa_tags()` para emitir:
  - `<link rel="manifest" href="manifest.webmanifest">`
  - `<meta name="theme-color" ...>`
  - `<meta name="application-name" content="ApoiaCandidato">`
  - `<meta name="apple-mobile-web-app-capable" content="yes">`
  - `<meta name="apple-mobile-web-app-title" content="ApoiaCandidato">`
  - `<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">`
  - `<link rel="apple-touch-icon" href="apple-touch-icon.png">`
  - `<script src="assets/js/pwa.js" defer></script>`
- Usar essa função em `index.php`, `premium.php`, `premium_conselheiro.php`, `premium_dicas_campanha.php`, `premium_perfil_eleitor.php`, `premium_pesquisas.php`, `eleicoes_municipais_se.php` e `eleicoes_estadual_se_2022.php`.
- Adicionar tags manualmente nas páginas que não carregam `premium_helpers.php`, se houver alguma exceção.

Manifesto recomendado:

- `name`: `ApoiaCandidato`
- `short_name`: `ApoiaCandidato`
- `id`: `/`
- `start_url`: `/premium?utm_source=pwa`
- `scope`: `/`
- `display`: `standalone`
- `display_override`: `["window-controls-overlay", "standalone", "minimal-ui"]`
- `orientation`: `portrait-primary`
- `theme_color`: cor principal do Premium
- `background_color`: cor de fundo inicial
- `icons`: 192, 512 e maskable
- `shortcuts`:
  - `Escritório`: `/premium`
  - `Estratégias`: `/premium_dicas_campanha.php`
  - `Perfil do eleitorado`: `/premium_perfil_eleitor.php`
  - `Pesquisas`: `/premium_pesquisas.php`

Para funcionar no subdiretório local `/eleicoes`, pode ser melhor usar URLs relativas no manifesto durante o desenvolvimento. Em produção, o ideal é manter o manifesto na raiz do domínio.

## UX de instalação

O arquivo `assets/js/pwa.js` deve cuidar apenas da experiência de instalação:

- Detectar `beforeinstallprompt` em navegadores Chromium.
- Exibir botão discreto "Instalar app" quando o prompt estiver disponível.
- Esconder o botão quando o app já estiver instalado ou rodando em `display-mode: standalone`.
- Detectar iOS/Safari e mostrar uma orientação curta para "Adicionar à Tela de Início".
- Não registrar service worker.
- Não manipular cache.

Locais possíveis para o botão:

- Topbar do Premium, junto das ações do usuário.
- Landing page, perto do CTA principal.
- Páginas auxiliares Premium, de forma discreta.

Texto sugerido:

- Botão: `Instalar app`
- Ajuda iOS: `No iPhone, toque em Compartilhar e depois em Adicionar à Tela de Início.`

## Plano por fases

### Fase 1 - MVP instalável online-only

Meta: o usuário consegue instalar o `ApoiaCandidato` e abrir o Premium em modo app, mantendo tudo dependente de conexão.

Tarefas:

- Criar `manifest.webmanifest`.
- Criar ícones PWA e `apple-touch-icon`.
- Criar `assets/js/pwa.js` sem service worker.
- Ajustar a sessão Premium para cookie persistente e renovável.
- Adicionar tags PWA nos heads.
- Adicionar botão discreto "Instalar app" no Premium quando `beforeinstallprompt` estiver disponível.
- Adicionar orientação manual para iOS/Safari, sem tentar forçar prompt.
- Garantir `start_url` em `/premium?utm_source=pwa`.
- Testar no Chrome DevTools Application e Lighthouse.

Critério de aceite:

- Manifest sem erros.
- App instalável no Chrome/Edge.
- Ícone e nome `ApoiaCandidato` aparecem corretamente.
- App abre em `/premium`.
- iPhone/Safari usa título e ícone corretos ao adicionar à tela inicial.
- Login, logout e ações POST continuam funcionando online.
- Usuário logado volta para a campanha ativa ao abrir pelo ícone enquanto a sessão estiver válida.
- Não existe cache persistente de dados Premium criado pelo PWA.

### Fase 2 - Acabamento de app

Meta: melhorar a experiência do app instalado sem mudar a arquitetura.

Tarefas:

- Ajustar `theme_color` para combinar com o tema Premium.
- Definir ícones maskable com boa área segura.
- Adicionar screenshots no manifesto se quisermos uma experiência de instalação mais rica.
- Adicionar `shortcuts` úteis no manifesto.
- Ajustar espaçamentos em telas pequenas quando aberto em standalone.
- Avaliar `safe-area-inset-*` no CSS para iPhone com notch.
- Opcionalmente self-host das fontes do Google para reduzir dependência externa e melhorar consistência visual.

Critério de aceite:

- App instalado parece intencional, sem barras ou espaçamentos estranhos.
- Ícone fica bem no Android, Windows e iOS.
- Atalhos do manifesto aparecem onde o navegador suportar.
- Nenhuma página quebra quando aberta em modo standalone.

### Fase 3 - Performance online

Meta: melhorar velocidade mantendo o modelo online.

Tarefas:

- Ajustar headers HTTP de assets estáticos no `.htaccess`.
- Definir cache de navegador para CSS, JS, imagens e ícones, sem service worker.
- Garantir que `manifest.webmanifest` seja servido com MIME correto.
- Avaliar compactação gzip/brotli no servidor.
- Avaliar redução de peso de `assets/favicon.png`, `assets/urna-eletronica.png` e `assets/agente-estrategica.png`.
- Revisar carregamento das fontes e imagens da landing.

Critério de aceite:

- Melhor tempo de carregamento em segunda visita.
- Nenhum dado autenticado é salvo em cache persistente controlado por JavaScript.
- Alterações de CSS/JS continuam chegando após deploy.

### Fase futura - Offline, se for retomado

Offline fica explicitamente fora do escopo atual.

Se a necessidade aparecer depois, tratar como novo projeto, com análise própria:

- Service worker.
- `offline.html`.
- Cache de assets.
- Snapshot somente leitura de campanha.
- Limpeza no logout.
- Consentimento explícito do usuário.
- Política de expiração dos dados locais.
- Escrita offline somente com fila, sincronização, resolução de conflitos e testes pesados em iOS/Android.

Recomendação: só retomar offline se houver uma dor real de uso em campo sem conexão. Para campanha, sincronização silenciosa mal resolvida pode ser pior do que exigir conexão.

## Ajustes de segurança recomendados

- Configurar cookie de sessão antes de `session_start()` com `Secure`, `HttpOnly` e `SameSite=Lax` ou `Strict`.
- Garantir que `manifest.webmanifest` seja servido como `application/manifest+json`.
- Não registrar service worker nesta fase.
- Não cachear HTML Premium com JavaScript.
- Não guardar snapshots de campanha no navegador.
- Considerar `Clear-Site-Data` no logout somente em fase futura com cache privado.
- Rever `Access-Control-Allow-Origin: *` em `api.php`; se a API é exclusivamente do site, restringir origem.

## Testes obrigatórios

- Chrome Desktop:
  - DevTools > Application > Manifest
  - validação dos ícones
  - instalação pelo botão da barra/endereço ou pelo botão interno
  - abertura em janela standalone
  - Lighthouse PWA
- Android Chrome:
  - instalação
  - abertura pelo ícone
  - nome e ícone corretos
  - abertura em `/premium`
  - login e logout
- iPhone Safari:
  - "Adicionar à Tela de Início"
  - ícone correto
  - título `ApoiaCandidato`
  - modo standalone
  - navegação entre Premium e páginas auxiliares
- Premium:
  - login
  - logout
  - criação/edição de campanha
  - busca de lideranças
  - relatórios
  - sessão expirada
- Segurança:
  - nenhum `sw.js` registrado
  - nenhum cache privado criado pelo PWA
  - POST continua exigindo conexão e CSRF válido

## Ordem recomendada de implementação

1. Criar ícones e manifesto.
2. Criar `assets/js/pwa.js` apenas para UX de instalação.
3. Injetar tags PWA nas páginas.
4. Adicionar botão/orientação de instalação.
5. Testar instalabilidade.
6. Ajustar visual em modo standalone.
7. Otimizar headers e peso de assets, sem implementar offline.

## Conclusão

O ApoiaCandidato está bem posicionado para virar PWA porque já roda em HTTPS, tem UI responsiva e uma área Premium com fluxo recorrente claro. Com a decisão atual, o caminho é entregar primeiro um app instalável e polido, sem offline, sem service worker e sem cache persistente de dados de campanha.
