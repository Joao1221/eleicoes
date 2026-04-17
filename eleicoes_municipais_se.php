<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eleições Municipais 2024 - Sergipe</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #04131a;
            --bg-soft: #0b1d26;
            --panel: rgba(10, 28, 37, 0.88);
            --panel-strong: rgba(12, 34, 46, 0.98);
            --line: rgba(116, 207, 169, 0.16);
            --text: #ebfff7;
            --muted: #9bc1b7;
            --accent: #6ef3c5;
            --accent-2: #ffd166;
            --accent-3: #64d2ff;
            --shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(100, 210, 255, 0.13), transparent 28%),
                radial-gradient(circle at top right, rgba(110, 243, 197, 0.12), transparent 24%),
                linear-gradient(180deg, #04131a 0%, #071a22 40%, #031017 100%);
            color: var(--text);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(circle at center, black 42%, transparent 95%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: min(1440px, calc(100vw - 32px));
            margin: 0 auto;
            padding: 24px 0 48px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
            color: var(--muted);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(7, 19, 26, 0.7);
        }

        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(11, 29, 38, 0.95), rgba(7, 36, 49, 0.9));
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 34px;
            box-shadow: var(--shadow);
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 28px;
            align-items: end;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid rgba(110, 243, 197, 0.2);
            background: rgba(5, 15, 20, 0.55);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 18px;
        }

        h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(2.2rem, 5vw, 4.2rem);
            line-height: 0.98;
            margin-bottom: 14px;
            max-width: 10ch;
        }

        .hero p,
        .sidebar-copy,
        .section-copy,
        .hero-card .sub,
        .stat .meta,
        .rank-code,
        .city-meta,
        .section-meta,
        .pill,
        .type-card .sub {
            color: var(--muted);
            line-height: 1.6;
        }

        .hero-note,
        .content,
        .rank-list,
        .zones-list,
        .sections-table {
            display: grid;
            gap: 14px;
        }

        .hero-card,
        .panel,
        .stat,
        .rank-item,
        .zone-item,
        .city-item,
        .type-card,
        .section-row {
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(6, 18, 24, 0.72);
            border-radius: 20px;
        }

        .hero-card,
        .stat,
        .type-card {
            padding: 18px;
        }

        .hero-card .label,
        .field label,
        .type-card .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.76rem;
        }

        .hero-card .value,
        .stat .kpi,
        .type-card .amount,
        .value-strong {
            font-weight: 800;
        }

        .layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 22px;
            margin-top: 22px;
        }

        .panel {
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .sidebar {
            position: sticky;
            top: 20px;
            align-self: start;
            padding: 16px;
            display: grid;
            gap: 14px;
        }

        .sidebar-title,
        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
        }

        .sidebar-copy {
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            font-size: 0.75rem;
        }

        .field select,
        .field input,
        .toggle-group button,
        .type-group button,
        .filter-actions button {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.08);
            background: var(--panel-strong);
            color: var(--text);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 0.85rem;
            font: inherit;
        }

        @media (max-width: 768px) {
            .field select,
            .field input,
            .toggle-group button,
            .type-group button,
            .filter-actions button {
                padding: 8px 10px;
                font-size: 0.8rem;
                border-radius: 10px;
            }
        }

        .toggle-group,
        .type-group,
        .filter-actions,
        .stats-grid,
        .section-grid,
        .municipios-grid,
        .types-grid {
            display: grid;
            gap: 10px;
        }

        .toggle-group {
            grid-template-columns: repeat(2, 1fr);
        }

        .type-group {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .filter-actions {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .toggle-group button.active {
            color: #02241c;
            background: linear-gradient(135deg, var(--accent), #98ffd7);
            border-color: transparent;
        }

        .type-group button.active {
            background: rgba(100, 210, 255, 0.18);
            color: var(--accent-3);
            border-color: rgba(100, 210, 255, 0.4);
        }

        .apply-btn {
            background: linear-gradient(135deg, var(--accent-2), #ffefb0);
            color: #2c2300;
            font-weight: 800;
        }

        .stats-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .section-grid {
            grid-template-columns: 1.15fr 0.85fr;
        }

        .section-card {
            padding: 22px;
        }

        .section-head,
        .rank-topline,
        .city-topline,
        .section-topline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 16px;
        }

        .rank-item,
        .city-item,
        .section-row {
            padding: 16px;
        }

        .zone-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 14px;
            padding: 16px;
        }

        .rank-name,
        .city-name {
            font-weight: 800;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(255,255,255,0.02);
            font-size: 0.84rem;
        }

        .bar {
            height: 11px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            overflow: hidden;
        }

        .bar > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent), var(--accent-3));
        }

        .zone-badge {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: rgba(255, 209, 102, 0.1);
            color: var(--accent-2);
            font-weight: 800;
        }

        .municipios-grid,
        .types-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .empty {
            padding: 22px;
            border-radius: 18px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed rgba(255,255,255,0.08);
            background: rgba(6, 18, 24, 0.6);
        }

        .loading {
            opacity: 0.55;
            pointer-events: none;
        }

        /* ========== MODERN LOADING OVERLAY ========== */
        #loadingOverlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #04131a 0%, #0b1d26 50%, #071a22 100%);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #loadingOverlay.visible {
            display: flex;
            opacity: 1;
        }
        .loader-container {
            position: relative;
            width: 120px;
            height: 120px;
        }
        .loader-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
        }
        .loader-ring-1 {
            border-top-color: var(--accent-2);
            border-right-color: var(--accent-2);
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        .loader-ring-2 {
            top: 15px;
            left: 15px;
            width: 90px;
            height: 90px;
            border-top-color: var(--accent);
            border-right-color: var(--accent);
            animation: spin 1s cubic-bezier(0.5, 0, 0.5, 1) infinite reverse;
        }
        .loader-ring-3 {
            top: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            border-top-color: var(--accent-3);
            animation: spin 0.8s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        .loader-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: var(--accent-2);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--accent-2), 0 0 40px var(--accent-2);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
            50% { transform: translate(-50%, -50%) scale(1.3); opacity: 0.7; }
        }
        .loading-content {
            margin-top: 2rem;
            text-align: center;
        }
        .loading-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
            letter-spacing: 0.02em;
        }
        .loading-dots {
            display: inline-flex;
            gap: 0.3rem;
        }
        .loading-dots span {
            width: 6px;
            height: 6px;
            background: var(--accent-2);
            border-radius: 50%;
            animation: dotPulse 1.4s ease-in-out infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 1220px) {
            .layout,
            .hero-grid,
            .section-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 780px) {
            .shell {
                width: min(100vw - 20px, 100%);
                padding-top: 12px;
            }

            .hero,
            .sidebar,
            .section-card {
                padding: 18px;
            }

            .stats-grid,
            .municipios-grid,
            .types-grid,
            .toggle-group,
            .type-group,
            .filter-actions {
                grid-template-columns: 1fr;
            }

            .rank-topline,
            .city-topline,
            .section-topline,
            .section-head,
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loader-container">
            <div class="loader-ring loader-ring-1"></div>
            <div class="loader-ring loader-ring-2"></div>
            <div class="loader-ring loader-ring-3"></div>
            <div class="loader-center"></div>
        </div>
        <div class="loading-content">
            <div class="loading-title" id="loadingText">Carregando dados</div>
            <div class="loading-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <div class="shell">
        <div class="topbar">
            <a class="back-link" href="index.php">← Voltar ao painel principal</a>
            <div>Modo escuro ativo • Base oficial TSE por seção eleitoral • Sergipe 2024</div>
        </div>

        <section class="hero">
            <div class="hero-grid">
                <div>
                    <span class="eyebrow">Radar Municipal • 2024</span>
                    <h1>Eleições Municipais de Sergipe</h1>
                    <p>
                        Um painel novo para explorar votos por seção, zona e município nas disputas de prefeito e vereador.
                        O foco aqui é velocidade de filtro, leitura visual forte e uma camada de síntese útil para investigação.
                    </p>
                </div>
                <div class="hero-note">
                    <div class="hero-card">
                        <div class="label">Liderança Atual do Recorte</div>
                        <div class="value" id="heroLeaderName">Carregando...</div>
                        <div class="sub" id="heroLeaderMeta">Aguardando leitura da base.</div>
                    </div>
                    <div class="hero-card">
                        <div class="label">Escopo Ativo</div>
                        <div class="sub" id="heroScope">Prefeito • 1º turno • todos os municípios • apenas candidatos</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="layout">
            <aside class="sidebar panel">
                <div>
                    <div class="sidebar-title">Filtro de leitura</div>
                    <p class="sidebar-copy">Escolha o cargo, refine por turno, município, zona e tipo de voto. O ranking, as zonas e as seções acompanham o recorte em tempo real.</p>
                </div>

                <div class="field">
                    <label>Cargo</label>
                    <div class="toggle-group">
                        <button type="button" class="cargo-btn active" data-value="Prefeito">Prefeito</button>
                        <button type="button" class="cargo-btn" data-value="Vereador">Vereador</button>
                    </div>
                </div>

                <div class="field">
                    <label>Turno</label>
                    <select id="turnoSelect"></select>
                </div>

                <div class="field">
                    <label>Município</label>
                    <select id="municipioSelect">
                        <option value="">Todos os municípios</option>
                    </select>
                </div>

                <div class="field">
                    <label>Zona</label>
                    <select id="zonaSelect">
                        <option value="0">Todas as zonas</option>
                    </select>
                </div>

                <div class="field">
                    <label>Tipo de voto</label>
                    <div class="type-group">
                        <button type="button" class="tipo-btn active" data-value="candidato">Candidatos</button>
                        <button type="button" class="tipo-btn" data-value="todos">Todos</button>
                        <button type="button" class="tipo-btn" data-value="legenda">Legenda</button>
                    </div>
                </div>

                <div class="field">
                    <label>Busca por nome ou número</label>
                    <input id="buscaInput" type="text" placeholder="Ex.: 55, Emília, 44444">
                </div>

                <div class="filter-actions">
                    <button type="button" class="apply-btn" id="applyBtn">Aplicar</button>
                    <button type="button" id="resetBtn">Limpar</button>
                </div>
            </aside>

            <main class="content" id="contentRoot">
                <section class="stats-grid">
                    <article class="stat panel"><div class="pill">Votos no recorte</div><div class="kpi" id="statTotalVotos">-</div><div class="meta">Volume agregado do filtro ativo.</div></article>
                    <article class="stat panel"><div class="pill">Municípios</div><div class="kpi" id="statMunicipios">-</div><div class="meta">Cidades cobertas pelo recorte.</div></article>
                    <article class="stat panel"><div class="pill">Zonas</div><div class="kpi" id="statZonas">-</div><div class="meta">Combinações ativas de município e zona.</div></article>
                    <article class="stat panel"><div class="pill">Seções com voto</div><div class="kpi" id="statSecoes">-</div><div class="meta">Seções que registraram esse filtro.</div></article>
                    <article class="stat panel"><div class="pill">Votáveis</div><div class="kpi" id="statVotaveis">-</div><div class="meta">Nomes ou tipos de voto presentes.</div></article>
                </section>

                

                <section class="section-grid">
                    <article class="section-card panel">
                        <div class="section-head">
                            <div><div class="section-title">Ranking do recorte</div><div class="section-copy">Os 20 registros com maior volume de votos agregados no filtro atual.</div></div>
                            <div class="pill" id="rankingContext">Atualizando...</div>
                        </div>
                        <div class="rank-list" id="rankingList"></div>
                    </article>

                    <article class="section-card panel">
                        <div class="section-head">
                            <div><div class="section-title">Zonas em destaque</div><div class="section-copy">Onde a disputa está mais concentrada no recorte selecionado.</div></div>
                            <div class="pill">Top 10 zonas</div>
                        </div>
                        <div class="zones-list" id="zonasList"></div>
                    </article>
                </section>

                <section class="section-card panel">
                    <div class="section-head">
                        <div><div class="section-title">Mapa municipal de liderança</div><div class="section-copy">Municípios com maior volume de votos e o líder local de cada um.</div></div>
                        <div class="pill">Top 16 municípios</div>
                    </div>
                    <div class="municipios-grid" id="municipiosGrid"></div>
                </section>

                <section class="section-card panel">
                    <div class="section-head">
                        <div><div class="section-title">Composição do voto</div><div class="section-copy">Leitura rápida dos tipos presentes na base filtrada.</div></div>
                    </div>
                    <div class="types-grid" id="typesGrid"></div>
                </section>

                <section class="section-card panel">
                    <div class="section-head">
                        <div><div class="section-title">Seções mais intensas</div><div class="section-copy">Pontos com maior número de votos para um mesmo votável dentro do recorte.</div></div>
                        <div class="pill">Top 18 seções</div>
                    </div>
                    <div class="sections-table" id="sectionsTable"></div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const state = {
            cargo: 'Prefeito',
            turno: 1,
            municipio: '',
            zona: 0,
            tipo: 'candidato',
            busca: ''
        };

        const els = {
            root: document.getElementById('contentRoot'),
            turno: document.getElementById('turnoSelect'),
            municipio: document.getElementById('municipioSelect'),
            zona: document.getElementById('zonaSelect'),
            busca: document.getElementById('buscaInput'),
            apply: document.getElementById('applyBtn'),
            reset: document.getElementById('resetBtn'),
            heroLeaderName: document.getElementById('heroLeaderName'),
            heroLeaderMeta: document.getElementById('heroLeaderMeta'),
            heroScope: document.getElementById('heroScope'),
            rankingContext: document.getElementById('rankingContext'),
            statTotalVotos: document.getElementById('statTotalVotos'),
            statMunicipios: document.getElementById('statMunicipios'),
            statZonas: document.getElementById('statZonas'),
            statSecoes: document.getElementById('statSecoes'),
            statVotaveis: document.getElementById('statVotaveis'),
            rankingList: document.getElementById('rankingList'),
            zonasList: document.getElementById('zonasList'),
            municipiosGrid: document.getElementById('municipiosGrid'),
            typesGrid: document.getElementById('typesGrid'),
            sectionsTable: document.getElementById('sectionsTable')
        };

        const formatNumber = (value) => new Intl.NumberFormat('pt-BR').format(Number(value || 0));
        const formatPercent = (value) => `${Number(value || 0).toFixed(2).replace('.', ',')}%`;

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function updateQueryString() {
            const params = new URLSearchParams();
            params.set('cargo', state.cargo);
            params.set('turno', state.turno);
            if (state.municipio) params.set('municipio', state.municipio);
            if (state.zona) params.set('zona', state.zona);
            if (state.tipo !== 'candidato') params.set('tipo', state.tipo);
            if (state.busca) params.set('busca', state.busca);
            history.replaceState({}, '', `${location.pathname}?${params.toString()}`);
        }

        function readQueryString() {
            const params = new URLSearchParams(location.search);
            state.cargo = params.get('cargo') || 'Prefeito';
            state.turno = Number(params.get('turno') || 1);
            state.municipio = params.get('municipio') || '';
            state.zona = Number(params.get('zona') || 0);
            state.tipo = params.get('tipo') || 'candidato';
            state.busca = params.get('busca') || '';
        }

        function syncButtons() {
            document.querySelectorAll('.cargo-btn').forEach((button) => {
                button.classList.toggle('active', button.dataset.value === state.cargo);
            });

            document.querySelectorAll('.tipo-btn').forEach((button) => {
                button.classList.toggle('active', button.dataset.value === state.tipo);
            });
        }

        function fillSelect(select, options, placeholder, selectedValue) {
            select.innerHTML = [placeholder, ...options].join('');
            select.value = String(selectedValue);
        }

        function renderFilters(filters) {
            const turnos = filters.turnos || [];
            const municipios = filters.municipios || [];
            const zonas = filters.zonas || [];

            fillSelect(
                els.turno,
                turnos.map((turno) => `<option value="${turno}">${turno}º turno</option>`),
                '',
                state.turno
            );

            if (turnos.length && !turnos.includes(state.turno)) {
                state.turno = turnos[0];
                els.turno.value = String(state.turno);
            }

            fillSelect(
                els.municipio,
                municipios.map((nome) => `<option value="${escapeHtml(nome)}">${escapeHtml(nome)}</option>`),
                '<option value="">Todos os municípios</option>',
                state.municipio
            );

            if (state.municipio && !municipios.includes(state.municipio)) {
                state.municipio = '';
                els.municipio.value = '';
            }

            fillSelect(
                els.zona,
                zonas.map((zona) => `<option value="${zona}">Zona ${zona}</option>`),
                '<option value="0">Todas as zonas</option>',
                state.zona
            );

            if (state.zona && !zonas.includes(state.zona)) {
                state.zona = 0;
                els.zona.value = '0';
            }

            els.busca.value = state.busca;
            syncButtons();
        }

        function renderStats(stats) {
            els.statTotalVotos.textContent = formatNumber(stats.total_votos);
            els.statMunicipios.textContent = formatNumber(stats.total_municipios);
            els.statZonas.textContent = formatNumber(stats.total_zonas);
            els.statSecoes.textContent = formatNumber(stats.total_secoes);
            els.statVotaveis.textContent = formatNumber(stats.total_votaveis);

            if (stats.lider) {
                els.heroLeaderName.textContent = `${stats.lider.nm_votavel} (${stats.lider.nr_votavel})`;
                const totalRecorte = stats.total_votos;
                const perc = totalRecorte > 0 ? (stats.lider.total_votos / totalRecorte * 100).toFixed(2).replace('.', ',') + '%' : '0%';
                els.heroLeaderMeta.textContent = `${formatNumber(stats.lider.total_votos)} votos • ${perc} no município • ${stats.lider.tipo_voto}`;
            } else {
                els.heroLeaderName.textContent = 'Sem liderança disponível';
                els.heroLeaderMeta.textContent = 'O filtro atual não retornou votos.';
            }

            const scope = [
                state.cargo,
                `${state.turno}º turno`,
                state.municipio || 'todos os municípios',
                state.zona ? `zona ${state.zona}` : 'todas as zonas',
                state.tipo === 'candidato' ? 'apenas candidatos' : (state.tipo === 'todos' ? 'todos os tipos de voto' : state.tipo)
            ];

            els.heroScope.textContent = scope.join(' • ');
            
        }

        function renderRanking(rows) {
            if (!rows.length) {
                els.rankingList.innerHTML = '<div class="empty">Nenhum resultado para este recorte.</div>';
                return;
            }

            const maxVotes = rows[0].total_votos || 1;

            els.rankingList.innerHTML = rows.map((row, index) => {
                const municipioInfo = row.nm_municipio ? row.nm_municipio : (row.municipios ? row.municipios + ' municípios • ' + row.zonas + ' zonas' : '1 município');
                const situacaoBadge = row.situacao ? `<span class="pill" style="margin-left:6px;${row.situacao.includes('ELEITO') ? 'background:var(--success);color:#000' : 'background:var(--panel);color:var(--text-muted)'}">${row.situacao}</span>` : '';
                return `
                <article class="rank-item">
                    <div class="rank-topline">
                        <div>
                            <div class="pill">#${index + 1} • ${row.tipo_voto}${row.cidade_forte || row.nm_municipio ? '<span class="pill" style="margin-left:6px;background:var(--accent-2);color:#000">' + (row.cidade_forte || row.nm_municipio) + '</span>' : ''}${situacaoBadge}</div>
                            <div class="rank-name">${escapeHtml(row.nm_votavel)}</div>
                            <div class="rank-code">Número ${row.nr_votavel} • ${municipioInfo}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="value-strong">${formatNumber(row.total_votos)}</div>
                            <div class="rank-code">${formatPercent(row.share)}</div>
                        </div>
                    </div>
                    <div class="bar"><span style="width:${Math.max(6, (row.total_votos / maxVotes) * 100)}%"></span></div>
                </article>
            `}).join('');
        }

        function renderZones(rows) {
            if (!rows.length) {
                els.zonasList.innerHTML = '<div class="empty">Nenhuma zona encontrada.</div>';
                return;
            }

            const maxVotes = rows[0].total_votos || 1;

            els.zonasList.innerHTML = rows.map((row) => `
                <article class="zone-item">
                    <div class="zone-badge">${row.nr_zona}</div>
                    <div>
                        <div class="rank-name">Zona ${row.nr_zona}</div>
                        <div class="rank-code">${formatPercent(row.share)} do total do recorte</div>
                        <div class="bar" style="margin-top:8px"><span style="width:${Math.max(8, (row.total_votos / maxVotes) * 100)}%"></span></div>
                    </div>
                    <div class="value-strong">${formatNumber(row.total_votos)}</div>
                </article>
            `).join('');
        }

        function renderCities(rows) {
            if (!rows.length) {
                els.municipiosGrid.innerHTML = '<div class="empty">Nenhum município disponível para esse filtro.</div>';
                return;
            }

            const maxVotes = rows[0].total_votos || 1;

            els.municipiosGrid.innerHTML = rows.map((row) => `
                <article class="city-item">
                    <div class="city-topline">
                        <div>
                            <div class="pill">${row.zonas} zonas ativas</div>
                            <div class="city-name">${escapeHtml(row.nm_municipio)}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="value-strong">${formatNumber(row.total_votos)}</div>
                            <div class="city-meta">votos agregados</div>
                        </div>
                    </div>
                    <div class="bar"><span style="width:${Math.max(8, (row.total_votos / maxVotes) * 100)}%"></span></div>
                    ${row.lider ? `
                        <div class="rank-name" style="margin-top:10px">${escapeHtml(row.lider.nm_votavel)} (${row.lider.nr_votavel})</div>
                        <div class="city-meta">${formatNumber(row.lider.total_votos)} votos • ${formatPercent(row.lider_percentual)} no município</div>
                    ` : '<div class="city-meta">Sem líder local disponível.</div>'}
                </article>
            `).join('');
        }

        function renderTypes(rows) {
            if (!rows.length) {
                els.typesGrid.innerHTML = '<div class="empty">Sem composição disponível.</div>';
                return;
            }

            els.typesGrid.innerHTML = rows.map((row) => `
                <article class="type-card">
                    <div class="label">${escapeHtml(row.tipo_voto)}</div>
                    <div class="amount">${formatNumber(row.total_votos)}</div>
                    <div class="sub">${formatPercent(row.share)} do recorte</div>
                </article>
            `).join('');
        }

        function renderSections(rows) {
            if (!rows.length) {
                els.sectionsTable.innerHTML = '<div class="empty">Nenhuma seção encontrada.</div>';
                return;
            }

            els.sectionsTable.innerHTML = rows.map((row) => `
                <article class="section-row">
                    <div class="section-topline">
                        <div>
                            <div class="pill">${escapeHtml(row.nm_municipio)} • Zona ${row.nr_zona}</div>
                            <div class="rank-name">${escapeHtml(row.nm_votavel)} (${row.nr_votavel})</div>
                            <div class="section-meta">${escapeHtml(row.tipo_voto)}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="value-strong">${formatNumber(row.total_votos)}</div>
                            <div class="section-meta">votos</div>
                        </div>
                    </div>
                </article>
            `).join('');
        }

        let _loadingCount = 0;
        function showLoading(msg) {
            _loadingCount++;
            const overlay = document.getElementById('loadingOverlay');
            document.getElementById('loadingText').textContent = msg || 'Carregando dados...';
            overlay.style.display = 'flex';
            requestAnimationFrame(() => overlay.classList.add('visible'));
        }
        function hideLoading() {
            _loadingCount = Math.max(0, _loadingCount - 1);
            if (_loadingCount === 0) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.classList.remove('visible');
                setTimeout(() => { if (!overlay.classList.contains('visible')) overlay.style.display = 'none'; }, 200);
            }
        }

        async function loadData() {
            updateQueryString();
            els.root.classList.add('loading');
            showLoading('Carregando dados...');

            const params = new URLSearchParams({
                cargo: state.cargo,
                turno: state.turno,
                tipo: state.tipo
            });

            if (state.municipio) params.set('municipio', state.municipio);
            if (state.zona) params.set('zona', state.zona);
            if (state.busca) params.set('busca', state.busca);

            try {
                const response = await fetch(`api_municipais_2024.php?${params.toString()}`);
                const data = await response.json();
                
                if (!response.ok || data.error) {
                    throw new Error(data.message || 'Falha ao carregar o painel');
                }

                renderFilters(data.filters);
                renderStats(data.stats);
                
                renderRanking(data.ranking || []);
                renderZones(data.zonas_destaque || []);
                renderCities(data.municipios || []);
                renderTypes(data.tipo_resumo || []);
                renderSections(data.secoes || []);
            } catch (error) {
                console.error(error);
                els.rankingList.innerHTML = `<div class="empty">${escapeHtml(error.message || 'Erro inesperado.')}</div>`;
                els.zonasList.innerHTML = '<div class="empty">Falha ao carregar as zonas.</div>';
                els.municipiosGrid.innerHTML = '<div class="empty">Falha ao carregar os municípios.</div>';
                els.typesGrid.innerHTML = '<div class="empty">Falha ao carregar a composição do voto.</div>';
                els.sectionsTable.innerHTML = '<div class="empty">Falha ao carregar as seções.</div>';
            } finally {
                els.root.classList.remove('loading');
                hideLoading();
            }
        }

        function applyForm() {
            state.turno = Number(els.turno.value || 1);
            state.municipio = els.municipio.value || '';
            state.zona = Number(els.zona.value || 0);
            state.busca = els.busca.value.trim();
            loadData();
        }

        function resetForm() {
            state.cargo = 'Prefeito';
            state.turno = 1;
            state.municipio = '';
            state.zona = 0;
            state.tipo = 'candidato';
            state.busca = '';
            syncButtons();
            loadData();
        }

        document.querySelectorAll('.cargo-btn').forEach((button) => {
            button.addEventListener('click', () => {
                state.cargo = button.dataset.value;
                state.turno = 1;
                state.municipio = '';
                state.zona = 0;
                syncButtons();
                loadData();
            });
        });

        document.querySelectorAll('.tipo-btn').forEach((button) => {
            button.addEventListener('click', () => {
                state.tipo = button.dataset.value;
                syncButtons();
                loadData();
            });
        });

        els.apply.addEventListener('click', applyForm);
        els.reset.addEventListener('click', resetForm);
        els.turno.addEventListener('change', applyForm);
        els.municipio.addEventListener('change', applyForm);
        els.zona.addEventListener('change', applyForm);
        els.busca.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                applyForm();
            }
        });

        readQueryString();
        syncButtons();
        loadData();
    </script>
</body>
</html>
