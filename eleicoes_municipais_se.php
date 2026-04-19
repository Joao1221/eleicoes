<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('eleicoes-theme');
                var theme = savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : 'dark';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch (error) {}
        })();
    </script>
    <title>Eleições Municipais 2024 - Sergipe</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
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

        html {
            color-scheme: dark;
        }

        html[data-theme="light"] {
            color-scheme: light;
            --bg: #f5f8fc;
            --bg-soft: #e7eef6;
            --panel: rgba(255, 255, 255, 0.86);
            --panel-strong: rgba(255, 255, 255, 0.96);
            --line: rgba(15, 23, 42, 0.12);
            --text: #0f172a;
            --muted: #5b6473;
            --accent: #0f766e;
            --accent-2: #b45309;
            --accent-3: #2563eb;
            --shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
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

        html[data-theme="light"] body {
            background:
                radial-gradient(circle at top left, rgba(100, 181, 246, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.12), transparent 24%),
                linear-gradient(180deg, #f7fbff 0%, #edf3f9 40%, #f4f7fb 100%);
        }

        html[data-theme="light"] body::before {
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .theme-switcher {
            display: inline-flex;
            gap: 6px;
            padding: 4px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
        }

        .theme-switcher button {
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: var(--muted);
            padding: 8px 12px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .theme-switcher button:hover {
            transform: translateY(-1px);
        }

        .theme-switcher button.active {
            background: var(--accent);
            color: #fff;
        }

        html[data-theme="light"] .theme-switcher {
            background: rgba(255, 255, 255, 0.72);
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

        .topbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .page-context {
            margin: 6px 0 16px;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 700px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-actions {
                width: 100%;
                justify-content: flex-start;
            }
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

        html[data-theme="light"] .back-link {
            background: rgba(255, 255, 255, 0.88);
        }

        html[data-theme="light"] .hero {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(239, 246, 255, 0.96));
        }

        html[data-theme="light"] .eyebrow {
            background: rgba(239, 246, 255, 0.92);
            border-color: rgba(37, 99, 235, 0.16);
            color: var(--accent-3);
        }

        html[data-theme="light"] .hero-visual {
            background: rgba(255, 255, 255, 0.82);
            border-color: rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] .hero-card,
        html[data-theme="light"] .panel,
        html[data-theme="light"] .stat,
        html[data-theme="light"] .rank-item,
        html[data-theme="light"] .zone-item,
        html[data-theme="light"] .city-item,
        html[data-theme="light"] .type-card,
        html[data-theme="light"] .section-row {
            background: rgba(255, 255, 255, 0.84);
            border-color: rgba(15, 23, 42, 0.08);
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

        .hero-visual {
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(6, 18, 24, 0.8);
            border-radius: 22px;
            overflow: hidden;
        }

        .hero-visual img {
            display: block;
            width: 100%;
            height: auto;
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

        .candidate-link {
            display: block;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .candidate-link:focus-visible {
            outline: 2px solid rgba(108, 189, 255, 0.9);
            outline-offset: 4px;
            border-radius: 20px;
        }

        .candidate-link:hover .rank-item,
        .candidate-link:focus-visible .rank-item {
            border-color: rgba(108, 189, 255, 0.24);
            transform: translateY(-1px);
        }

        .candidate-link .rank-item {
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .candidate-link-inline {
            display: block;
            margin-top: 10px;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .candidate-link-inline:hover .rank-name,
        .candidate-link-inline:focus-visible .rank-name {
            text-decoration: underline;
        }

        html[data-theme="light"] .pill {
            background: rgba(15, 23, 42, 0.03);
            border-color: rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] .mini-select {
            background: rgba(255, 255, 255, 0.96);
            border-color: rgba(15, 23, 42, 0.12);
        }

        html[data-theme="light"] .status-nao-eleito {
            background: rgba(15, 23, 42, 0.04);
        }

        html[data-theme="light"] .bar {
            background: rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] .empty {
            background: rgba(255, 255, 255, 0.86);
            border-color: rgba(15, 23, 42, 0.12);
        }

        html[data-theme="light"] .candidate-link:hover .rank-item,
        html[data-theme="light"] .candidate-link:focus-visible .rank-item {
            border-color: rgba(37, 99, 235, 0.28);
        }

        html[data-theme="light"] #loadingOverlay {
            background: linear-gradient(135deg, #f7fbff 0%, #edf3f9 50%, #f4f7fb 100%);
        }

        html[data-theme="light"] .loading-title {
            color: var(--text);
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

        .mini-select {
            width: auto;
            min-width: 172px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(6, 18, 24, 0.92);
            color: var(--text);
            border-radius: 999px;
            padding: 7px 12px;
            font: inherit;
            font-size: 0.78rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 10px;
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            line-height: 1;
            margin-left: 8px;
        }

        .status-eleito {
            color: var(--accent);
            background: rgba(110, 243, 197, 0.14);
            border-color: rgba(110, 243, 197, 0.22);
        }

        .status-suplente {
            color: var(--accent-2);
            background: rgba(255, 209, 102, 0.14);
            border-color: rgba(255, 209, 102, 0.24);
        }

        .status-nao-eleito {
            color: var(--muted);
            background: rgba(255, 255, 255, 0.05);
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
            <a class="back-link" href="index.php">← Voltar ao painel estadual</a>
            <div class="topbar-actions">
                <div class="theme-switcher" role="group" aria-label="Selecionar tema">
                    <button type="button" data-theme-choice="dark">Modo escuro</button>
                    <button type="button" data-theme-choice="light">Modo claro</button>
                </div>
            </div>
        </div>
        <div class="page-context">Base oficial TSE por seção eleitoral • Sergipe 2024</div>

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
                    <div class="hero-visual" aria-hidden="true">
                        <img src="assets/urna-eletronica.png" alt="">
                    </div>
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
                    <label>Busca por nome de urna ou número</label>
                    <input id="buscaInput" type="text" placeholder="Ex.: 55, Emília, Bem Santana">
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
                            <div><div class="section-title" id="rankingTitle">Ranking do recorte</div><div class="section-copy" id="rankingCopy">Os 20 registros com maior volume de votos agregados no filtro atual.</div></div>
                            <div style="display:flex; align-items:flex-start; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                <select id="rankingModeSelect" class="mini-select" style="display:none; cursor:pointer;" aria-label="Modo de exibição do ranking">
                                    <option value="todos">Todos</option>
                                    <option value="eleitos">Apenas eleitos</option>
                                </select>
                                <button type="button" class="pill" id="toggleRankingBtn" style="display:none; cursor:pointer;">Ver todos</button>
                                <div class="pill" id="rankingContext">Atualizando...</div>
                            </div>
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

                <section class="section-card panel" id="municipiosSection">
                    <div class="section-head">
                        <div><div class="section-title">Mapa municipal de liderança</div><div class="section-copy">Municípios com maior volume de votos e o líder local de cada um.</div></div>
                        <div class="pill">Top 16 municípios</div>
                    </div>
                    <div class="municipios-grid" id="municipiosGrid"></div>
                </section>

                <section class="section-card panel" id="typesSection">
                    <div class="section-head">
                        <div><div class="section-title">Composição do voto</div><div class="section-copy">Leitura rápida dos tipos presentes na base filtrada.</div></div>
                    </div>
                    <div class="types-grid" id="typesGrid"></div>
                </section>

                <section class="section-card panel" id="sectionsSection">
                    <div class="section-head">
                        <div><div class="section-title" id="sectionsTitle">Seções mais intensas</div><div class="section-copy" id="sectionsCopy">Pontos com maior número de votos para um mesmo votável dentro do recorte.</div></div>
                        <button type="button" class="pill" id="toggleSectionsBtn" style="display:none; cursor:pointer;">Ver todas</button>
                    </div>
                    <div class="sections-table" id="sectionsTable"></div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const THEME_STORAGE_KEY = 'eleicoes-theme';

        function getPreferredTheme() {
            try {
                const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    return savedTheme;
                }
            } catch (error) {}

            return document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
        }

        function applyTheme(theme) {
            const nextTheme = theme === 'light' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            document.documentElement.style.colorScheme = nextTheme;

            document.querySelectorAll('[data-theme-choice]').forEach((button) => {
                const isActive = button.dataset.themeChoice === nextTheme;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
        }

        function initThemeSwitcher() {
            applyTheme(getPreferredTheme());

            document.querySelectorAll('[data-theme-choice]').forEach((button) => {
                button.addEventListener('click', () => {
                    const nextTheme = button.dataset.themeChoice === 'light' ? 'light' : 'dark';
                    try {
                        localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
                    } catch (error) {}
                    applyTheme(nextTheme);
                });
            });
        }

        initThemeSwitcher();

        const state = {
            cargo: 'Prefeito',
            turno: 1,
            municipio: '',
            zona: 0,
            tipo: 'candidato',
            busca: '',
            rankingMode: 'todos',
            showAllRanking: false,
            showAllSections: false
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
            rankingModeSelect: document.getElementById('rankingModeSelect'),
            toggleRankingBtn: document.getElementById('toggleRankingBtn'),
            statTotalVotos: document.getElementById('statTotalVotos'),
            statMunicipios: document.getElementById('statMunicipios'),
            statZonas: document.getElementById('statZonas'),
            statSecoes: document.getElementById('statSecoes'),
            statVotaveis: document.getElementById('statVotaveis'),
            rankingTitle: document.getElementById('rankingTitle'),
            rankingCopy: document.getElementById('rankingCopy'),
            rankingList: document.getElementById('rankingList'),
            zonasList: document.getElementById('zonasList'),
            municipiosSection: document.getElementById('municipiosSection'),
            municipiosGrid: document.getElementById('municipiosGrid'),
            typesSection: document.getElementById('typesSection'),
            typesGrid: document.getElementById('typesGrid'),
            sectionsSection: document.getElementById('sectionsSection'),
            sectionsTitle: document.getElementById('sectionsTitle'),
            sectionsCopy: document.getElementById('sectionsCopy'),
            toggleSectionsBtn: document.getElementById('toggleSectionsBtn'),
            sectionsTable: document.getElementById('sectionsTable')
        };

        let currentData = null;

        const formatNumber = (value) => new Intl.NumberFormat('pt-BR').format(Number(value || 0));
        const formatPercent = (value) => `${Number(value || 0).toFixed(2).replace('.', ',')}%`;

        function stripDiacritics(value) {
            const text = String(value || '');
            if (!text.normalize) {
                return text;
            }
            return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function getElectionStatusLabel(situacao) {
            const value = String(situacao || '').trim();
            return value || null;
        }

        function classifyElectionStatus(situacao) {
            const value = stripDiacritics(String(situacao || '').trim()).toUpperCase();
            if (!value) {
                return null;
            }

            if (value.includes('SUPLENTE')) {
                return 'suplente';
            }
            if (value.includes('NAO ELEITO')) {
                return 'nao_eleito';
            }
            if (value.includes('ELEITO')) {
                return 'eleito';
            }
            return 'outro';
        }

        function electionStatusClass(situacao) {
            const statusType = classifyElectionStatus(situacao);
            if (statusType === 'eleito') {
                return 'status-eleito';
            }
            if (statusType === 'suplente') {
                return 'status-suplente';
            }
            return 'status-nao-eleito';
        }

        function isElectedRow(row) {
            return classifyElectionStatus(row.situacao) === 'eleito';
        }

        function getRankingTitleText(isMunicipalWardView) {
            if (isMunicipalWardView) {
                return `Vereadores de ${state.municipio}`;
            }
            return 'Ranking do recorte';
        }

        function getRankingCopyText(isMunicipalWardView, mode, showAll, hasStatusRows) {
            if (!hasStatusRows) {
                return showAll
                    ? 'Mostrando todos os registros do filtro atual.'
                    : 'Os 20 registros com maior volume de votos agregados no filtro atual.';
            }

            if (isMunicipalWardView) {
                if (mode === 'eleitos') {
                    return `Mostrando apenas os vereadores eleitos de ${state.municipio}.`;
                }
                return showAll
                    ? `Mostrando todos os vereadores de ${state.municipio}.`
                    : `Mostrando os 20 vereadores mais votados de ${state.municipio}, com opcao de ver todos.`;
            }

            if (mode === 'eleitos') {
                return 'Mostrando apenas os candidatos eleitos do recorte atual.';
            }
            return hasStatusRows
                ? 'Os 20 registros com maior volume de votos agregados no filtro atual.'
                : 'Os 20 registros com maior volume de votos agregados no filtro atual.';
        }

        function getRankingRows(rows) {
            const list = Array.isArray(rows) ? rows.slice() : [];

            if (state.rankingMode === 'eleitos') {
                return list.filter(isElectedRow);
            }

            return list;
        }

        function updateRankingHeader(detailMode, rows) {
            const isMunicipalWardView = state.cargo === 'Vereador' && !!state.municipio;
            const hasStatusRows = rows.some((row) => String(row.situacao || '').trim() !== '');

            els.rankingTitle.textContent = getRankingTitleText(isMunicipalWardView);
            els.rankingCopy.textContent = getRankingCopyText(
                isMunicipalWardView,
                state.rankingMode,
                state.showAllRanking,
                hasStatusRows
            );

            if (els.rankingModeSelect) {
                const shouldShowModeSelect = hasStatusRows && !detailMode;
                els.rankingModeSelect.style.display = shouldShowModeSelect ? 'inline-block' : 'none';
                if (shouldShowModeSelect) {
                    els.rankingModeSelect.value = state.rankingMode;
                }
            }
        }

        function formatSectionLocal(row) {
            const local = row.nm_local_votacao || row.nm_municipio || '';
            const address = String(row.ds_local_votacao_endereco || '').trim();

            if (!address) {
                return local;
            }

            const normalized = stripDiacritics(address).toUpperCase();
            if (/^(POVOADO|VILA)\b/.test(normalized)) {
                return `${local} (${address})`;
            }

            return local;
        }

        function updateMunicipalWardPanels(isMunicipalWardView, detailMode) {
            const shouldHide = isMunicipalWardView && !detailMode;
            const panels = [els.municipiosSection, els.typesSection, els.sectionsSection];

            panels.forEach((panel) => {
                if (panel) {
                    panel.style.display = shouldHide ? 'none' : '';
                }
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function isCandidateRow(row) {
            return String(row && row.tipo_voto ? row.tipo_voto : '').trim().toLowerCase() === 'candidato';
        }

        function buildCandidateDetailHref(row, fallbackMunicipio = '') {
            const params = new URLSearchParams();
            params.set('cargo', state.cargo);
            params.set('turno', state.turno);
            params.set('tipo', 'candidato');

            const municipio = String(row && row.nm_municipio ? row.nm_municipio : fallbackMunicipio || '').trim();
            if (municipio) {
                params.set('municipio', municipio);
            }

            const busca = String(
                row && row.nr_votavel
                    ? row.nr_votavel
                    : (row && row.nm_urna_candidato ? row.nm_urna_candidato : (row && row.nm_votavel ? row.nm_votavel : ''))
            ).trim();

            if (busca) {
                params.set('busca', busca);
            }

            return `${location.pathname}?${params.toString()}`;
        }

        function updateQueryString() {
            const params = new URLSearchParams();
            params.set('cargo', state.cargo);
            params.set('turno', state.turno);
            if (state.municipio) params.set('municipio', state.municipio);
            if (state.zona) params.set('zona', state.zona);
            if (state.tipo !== 'candidato') params.set('tipo', state.tipo);
            if (state.busca) params.set('busca', state.busca);
            if (state.rankingMode && state.rankingMode !== 'todos') params.set('ranking', state.rankingMode);
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
            state.rankingMode = params.get('ranking') || 'todos';
            if (!['todos', 'eleitos'].includes(state.rankingMode)) {
                state.rankingMode = 'todos';
            }
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
                const leaderName = stats.lider.nm_urna_candidato || stats.lider.nm_votavel;
                els.heroLeaderName.textContent = `${leaderName} (${stats.lider.nr_votavel})`;
                const victoryPercent = Number(stats.lider.turno_2_percentual || 0) > 0
                    ? formatPercent(stats.lider.turno_2_percentual)
                    : '';
                const totalRecorte = stats.total_votos;
                const perc = totalRecorte > 0 ? (stats.lider.total_votos / totalRecorte * 100).toFixed(2).replace('.', ',') + '%' : '0%';
                const leaderStatus = getElectionStatusLabel(stats.lider.situacao);
                const statusPart = leaderStatus ? ` • ${leaderStatus}` : '';

                if (victoryPercent) {
                    els.heroLeaderMeta.textContent = `${formatNumber(stats.lider.total_votos)} votos (${victoryPercent}) • ${stats.lider.tipo_voto}${statusPart}`;
                } else {
                    els.heroLeaderMeta.textContent = `${formatNumber(stats.lider.total_votos)} votos • ${perc} no município • ${stats.lider.tipo_voto}${statusPart}`;
                }
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

        function renderRanking(rows, detailMode = false) {
            const orderedRows = detailMode ? (Array.isArray(rows) ? rows.slice() : []) : getRankingRows(rows);

            if (!orderedRows.length) {
                els.rankingList.innerHTML = `<div class="empty">${state.rankingMode === 'eleitos'
                    ? 'Nenhum candidato eleito encontrado para este recorte.'
                    : 'Nenhum resultado para este recorte.'}</div>`;
                if (els.toggleRankingBtn) {
                    els.toggleRankingBtn.style.display = 'none';
                }
                return;
            }

            const hasMoreThanTwenty = orderedRows.length > 20;
            const visibleRows = state.showAllRanking ? orderedRows : orderedRows.slice(0, 20);

            if (els.toggleRankingBtn) {
                if (hasMoreThanTwenty && !detailMode) {
                    els.toggleRankingBtn.style.display = 'inline-flex';
                    els.toggleRankingBtn.textContent = state.showAllRanking
                        ? 'Ver apenas 20'
                        : `Ver todos (${orderedRows.length})`;
                } else {
                    els.toggleRankingBtn.style.display = 'none';
                }
            }

            const maxVotes = visibleRows.reduce((max, row) => Math.max(max, Number(row.total_votos || 0)), 1);

            els.rankingList.innerHTML = visibleRows.map((row, index) => {
                const displayName = row.nm_urna_candidato || row.nm_votavel || 'Votável';
                const candidateStatus = getElectionStatusLabel(row.situacao);
                const statusHtml = candidateStatus
                    ? `<span class="status-badge ${electionStatusClass(row.situacao)}">${escapeHtml(candidateStatus)}</span>`
                    : '';
                const cityLabel = row.nm_municipio || row.cidade_forte || (row.municipios ? `${row.municipios} municípios` : 'Município');
                const partyLabel = row.sg_partido ? ` • ${escapeHtml(row.sg_partido)}` : '';
                const statusLabel = candidateStatus ? ` • ${escapeHtml(candidateStatus)}` : '';
                const voteTypeLabel = row.tipo_voto ? ` • ${escapeHtml(row.tipo_voto)}` : '';
                const showTurn2Total = state.cargo === 'Prefeito'
                    && String(row.nm_municipio || '').toUpperCase() === 'ARACAJU'
                    && Number(row.turno_2_total_votos || 0) > 0;
                const victoryPercent = Number(row.turno_2_percentual || 0) > 0
                    ? formatPercent(row.turno_2_percentual)
                    : '';
                const percentLabel = victoryPercent || formatPercent(row.share);
                const turn2Html = showTurn2Total
                    ? `<div class="rank-code">2º turno: ${formatNumber(row.turno_2_total_votos)} votos${victoryPercent ? ` (${victoryPercent})` : ''}</div>`
                    : '';
                const candidateHref = isCandidateRow(row)
                    ? buildCandidateDetailHref(row, row.nm_municipio || state.municipio)
                    : '';
                const cardStart = candidateHref
                    ? `<a class="candidate-link" href="${escapeHtml(candidateHref)}" aria-label="Abrir detalhes de ${escapeHtml(displayName)}">`
                    : '';
                const cardEnd = candidateHref ? '</a>' : '';
                return `
                ${cardStart}<article class="rank-item">
                    <div class="rank-topline">
                        <div>
                            <div class="pill">#${index + 1} • ${escapeHtml(cityLabel)}${statusHtml}</div>
                            <div class="rank-name">${escapeHtml(displayName)}</div>
                            <div class="rank-code">Número ${row.nr_votavel}${partyLabel}${statusLabel}${voteTypeLabel}</div>
                            ${turn2Html}
                        </div>
                        <div style="text-align:right">
                            <div class="value-strong">${formatNumber(row.total_votos)}</div>
                            <div class="rank-code">${percentLabel}</div>
                        </div>
                    </div>
                    <div class="bar"><span style="width:${Math.max(6, (row.total_votos / maxVotes) * 100)}%"></span></div>
                </article>${cardEnd}
            `}).join('');
        }

        function renderZones(rows) {
            if (!rows.length) {
                els.zonasList.innerHTML = currentData && currentData.insights_deferred
                    ? '<div class="empty">Refine o filtro com zona ou busca para carregar as zonas em destaque.</div>'
                    : '<div class="empty">Nenhuma zona encontrada.</div>';
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

            els.municipiosGrid.innerHTML = rows.map((row) => {
                const leaderHref = row.lider && isCandidateRow(row.lider)
                    ? buildCandidateDetailHref(row.lider, row.nm_municipio)
                    : '';
                const leaderBlock = row.lider ? (leaderHref
                    ? `
                        <a class="candidate-link-inline" href="${escapeHtml(leaderHref)}" aria-label="Abrir detalhes de ${escapeHtml(row.lider.nm_votavel)}">
                            <div class="rank-name" style="margin-top:10px">${escapeHtml(row.lider.nm_votavel)} (${row.lider.nr_votavel})</div>
                            <div class="city-meta">${formatNumber(row.lider.total_votos)} votos • ${formatPercent(row.lider_percentual)} no município</div>
                        </a>
                    `
                    : `
                        <div class="rank-name" style="margin-top:10px">${escapeHtml(row.lider.nm_votavel)} (${row.lider.nr_votavel})</div>
                        <div class="city-meta">${formatNumber(row.lider.total_votos)} votos • ${formatPercent(row.lider_percentual)} no município</div>
                    `) : '<div class="city-meta">Sem líder local disponível.</div>';

                return `
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
                    ${leaderBlock}
                </article>
            `;
            }).join('');
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

        function renderSections(rows, detailMode = false) {
            if (!rows.length) {
                els.sectionsTable.innerHTML = currentData && currentData.insights_deferred
                    ? '<div class="empty">Refine o filtro com zona ou busca para carregar as seções mais intensas.</div>'
                    : '<div class="empty">Nenhuma seção encontrada.</div>';
                if (els.toggleSectionsBtn) {
                    els.toggleSectionsBtn.style.display = 'none';
                }
                return;
            }

            const hasMoreThanTen = rows.length > 10;
            const visibleRows = state.showAllSections ? rows : rows.slice(0, 10);

            if (els.toggleSectionsBtn) {
                if (hasMoreThanTen) {
                    els.toggleSectionsBtn.style.display = 'inline-flex';
                    els.toggleSectionsBtn.textContent = state.showAllSections
                        ? 'Ver apenas 10'
                        : `Ver todas (${rows.length})`;
                } else {
                    els.toggleSectionsBtn.style.display = 'none';
                }
            }

            if (detailMode) {
                els.sectionsCopy.textContent = state.showAllSections
                    ? `Mostrando todas as ${rows.length} seções do candidato selecionado.`
                    : `Mostrando as 10 seções com mais votos de um total de ${rows.length}.`;
            } else if (hasMoreThanTen) {
                els.sectionsCopy.textContent = state.showAllSections
                    ? `Mostrando todas as ${rows.length} seções deste recorte.`
                    : `Mostrando as 10 seções com mais votos deste recorte.`;
            }

            els.sectionsTable.innerHTML = visibleRows.map((row) => {
                const sectionLocal = formatSectionLocal(row);
                const address = String(row.ds_local_votacao_endereco || '').trim();
                const shouldHideAddressLine = sectionLocal !== (row.nm_local_votacao || row.nm_municipio || '');
                const titleHtml = detailMode
                    ? `<div class="rank-name">${escapeHtml(sectionLocal)}</div>`
                    : `<div class="rank-name">${escapeHtml(row.nm_votavel)} (${row.nr_votavel})</div>`;

                const metaHtml = detailMode
                    ? (shouldHideAddressLine ? '' : `<div class="section-meta">${escapeHtml(address || row.nm_municipio)}</div>`)
                    : `<div class="section-meta">${escapeHtml(row.tipo_voto)}</div>${row.nm_local_votacao ? `<div class="section-meta">${escapeHtml(sectionLocal)}</div>` : ''}`;

                return `
                <article class="section-row">
                    <div class="section-topline">
                        <div>
                            <div class="pill">${escapeHtml(row.nm_municipio)} • Zona ${row.nr_zona} • Seção ${row.nr_secao}</div>
                            ${titleHtml}
                            ${metaHtml}
                        </div>
                        <div style="text-align:right">
                            <div class="value-strong">${formatNumber(row.total_votos)}</div>
                            <div class="section-meta">votos</div>
                        </div>
                    </div>
                </article>
            `;
            }).join('');
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
                tipo: state.tipo,
                ranking: state.rankingMode
            });

            if (state.municipio) params.set('municipio', state.municipio);
            if (state.zona) params.set('zona', state.zona);
            if (state.busca) params.set('busca', state.busca);

            try {
                const response = await fetch(`api_municipais_2024.php?${params.toString()}`, {
                    cache: 'no-store'
                });
                const data = await response.json();
                
                if (!response.ok || data.error) {
                    throw new Error(data.message || 'Falha ao carregar o painel');
                }

                currentData = data;
                renderFilters(data.filters);
                renderStats(data.stats);

                if (data.detail_mode && data.focused_candidate) {
                    const candidateLabel = data.focused_candidate.nm_urna_candidato || data.focused_candidate.nm_votavel;
                    const turn2Part = data.focused_candidate.turno_2_total_votos
                        ? ` • 2º turno: ${formatNumber(data.focused_candidate.turno_2_total_votos)} votos`
                        : '';
                    els.rankingContext.textContent = `${candidateLabel} • ${data.focused_candidate.nr_votavel}${turn2Part}`;
                    els.rankingTitle.textContent = 'Detalhe do candidato';
                    els.rankingCopy.textContent = 'Resumo do candidato selecionado no recorte atual.';
                    els.sectionsTitle.textContent = 'Seções do candidato';
                    els.sectionsCopy.textContent = 'Todas as seções onde este candidato recebeu votos, com o total de cada uma.';
                } else {
                    const isMunicipalWardView = state.cargo === 'Vereador' && !!state.municipio;
                    els.rankingContext.textContent = data.stats && data.stats.lider
                        ? `${isMunicipalWardView ? `${state.municipio} • ` : ''}${formatNumber(data.stats.lider.total_votos)} votos no líder`
                        : (isMunicipalWardView ? `${state.municipio} • vereadores` : 'Sem liderança disponível');
                    if (isMunicipalWardView) {
                        els.rankingTitle.textContent = `Vereadores de ${state.municipio}`;
                        els.rankingCopy.textContent = state.showAllRanking
                            ? `Mostrando todos os vereadores de ${state.municipio}.`
                            : `Mostrando os 20 vereadores mais votados de ${state.municipio}, com opção de ver todos.`;
                    } else {
                        els.rankingTitle.textContent = 'Ranking do recorte';
                        els.rankingCopy.textContent = 'Os 20 registros com maior volume de votos agregados no filtro atual.';
                    }
                    els.sectionsTitle.textContent = 'Seções mais intensas';
                    els.sectionsCopy.textContent = 'Pontos com maior número de votos para um mesmo votável dentro do recorte.';
                    if (data.insights_deferred) {
                        els.sectionsCopy.textContent = 'Refine zona ou busca para carregar as seções e zonas em destaque sem estourar o tempo limite.';
                    }
                }
                
                const isMunicipalWardView = state.cargo === 'Vereador' && !!state.municipio;
                updateMunicipalWardPanels(isMunicipalWardView, !!data.detail_mode);
                updateRankingHeader(!!data.detail_mode, data.ranking || []);

                renderRanking(data.ranking || [], !!data.detail_mode);
                renderZones(data.zonas_destaque || []);
                renderCities(data.municipios || []);
                renderTypes(data.tipo_resumo || []);
                renderSections(data.secoes || [], !!data.detail_mode);
            } catch (error) {
                console.error(error);
                els.rankingList.innerHTML = `<div class="empty">${escapeHtml(error.message || 'Erro inesperado.')}</div>`;
                if (els.toggleRankingBtn) {
                    els.toggleRankingBtn.style.display = 'none';
                }
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
            state.showAllRanking = false;
            state.showAllSections = false;
            loadData();
        }

        function resetForm() {
            state.cargo = 'Prefeito';
            state.turno = 1;
            state.municipio = '';
            state.zona = 0;
            state.tipo = 'candidato';
            state.busca = '';
            state.rankingMode = 'todos';
            state.showAllRanking = false;
            state.showAllSections = false;
            syncButtons();
            loadData();
        }

        document.querySelectorAll('.cargo-btn').forEach((button) => {
            button.addEventListener('click', () => {
                state.cargo = button.dataset.value;
                state.turno = 1;
                state.municipio = '';
                state.zona = 0;
                state.showAllRanking = false;
                state.showAllSections = false;
                syncButtons();
                loadData();
            });
        });

        document.querySelectorAll('.tipo-btn').forEach((button) => {
            button.addEventListener('click', () => {
                state.tipo = button.dataset.value;
                state.showAllRanking = false;
                state.showAllSections = false;
                syncButtons();
                loadData();
            });
        });

        if (els.toggleRankingBtn) {
            els.toggleRankingBtn.addEventListener('click', () => {
                state.showAllRanking = !state.showAllRanking;
                if (currentData) {
                    updateRankingHeader(!!currentData.detail_mode, currentData.ranking || []);
                    renderRanking(currentData.ranking || [], !!currentData.detail_mode);
                    if (!currentData.detail_mode && state.rankingMode === 'todos') {
                        const isMunicipalWardView = state.cargo === 'Vereador' && !!state.municipio;
                        if (isMunicipalWardView) {
                            els.rankingCopy.textContent = state.showAllRanking
                                ? `Mostrando todos os vereadores de ${state.municipio}.`
                                : `Mostrando os 20 vereadores mais votados de ${state.municipio}, com opção de ver todos.`;
                        }
                    }
                }
            });
        }

        if (els.rankingModeSelect) {
            els.rankingModeSelect.addEventListener('change', () => {
                state.rankingMode = els.rankingModeSelect.value || 'todos';
                if (!['todos', 'eleitos'].includes(state.rankingMode)) {
                    state.rankingMode = 'todos';
                }
                state.showAllRanking = false;
                updateQueryString();
                loadData();
            });
        }

        if (els.toggleSectionsBtn) {
            els.toggleSectionsBtn.addEventListener('click', () => {
                state.showAllSections = !state.showAllSections;
                if (currentData) {
                    renderSections(currentData.secoes || [], !!currentData.detail_mode);
                }
            });
        }

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
