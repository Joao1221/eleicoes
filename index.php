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
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <title>Eleições Sergipe 2022 - Painel Estatístico</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #0f3460;
            --highlight: #e94560;
            --text: #eaeaea;
            --text-muted: #a0a0a0;
            --card-bg: #1f1f3a;
            --border: #2a2a4a;
            --success: #00d9a5;
            --warning: #ffc107;
            --danger: #ff4757;
        }

        html {
            color-scheme: dark;
        }

        html[data-theme="light"] {
            color-scheme: light;
            --primary: #f5f8fc;
            --secondary: #e7eef6;
            --accent: #dbeafe;
            --highlight: #2563eb;
            --text: #0f172a;
            --text-muted: #5b6473;
            --card-bg: #ffffff;
            --border: #d7e0ea;
            --success: #0f9d58;
            --warning: #d97706;
            --danger: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary);
            color: var(--text);
            min-height: 100vh;
        }

        html[data-theme="light"] body {
            background: linear-gradient(180deg, #f8fbff 0%, #edf3f9 100%);
        }

        .theme-switcher {
            display: inline-flex;
            gap: 6px;
            padding: 4px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
        }

        .theme-switcher button {
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: var(--text-muted);
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
            background: var(--highlight);
            color: #fff;
        }

        html[data-theme="light"] .theme-switcher {
            background: rgba(255, 255, 255, 0.72);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        @media (max-width: 720px) {
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .header {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(280px, 380px);
            gap: 1.5rem;
            align-items: center;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            padding: 2rem;
            border-bottom: 3px solid var(--highlight);
        }

        .header-copy {
            min-width: 0;
        }

        .header-visual {
            position: relative;
            padding: 14px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(6, 10, 20, 0.26);
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.22);
            overflow: hidden;
        }

        html[data-theme="light"] .header-visual {
            border-color: rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .header-visual img {
            display: block;
            width: 100%;
            height: auto;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header h1::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 32px;
            background: var(--highlight);
            border-radius: 4px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .page-context {
            margin-top: 0.8rem;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 920px) {
            .header {
                grid-template-columns: 1fr;
            }
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 0.75rem 1rem;
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95rem;
            width: 100%;
            max-width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-group select:hover {
            border-color: var(--highlight);
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--highlight);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }

        .turno-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .turno-tab {
            padding: 0.75rem 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .turno-tab:hover {
            border-color: var(--highlight);
            color: var(--text);
        }

        .turno-tab.active {
            background: var(--highlight);
            border-color: var(--highlight);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--highlight);
            border-radius: 4px 0 0 4px;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.15);
        }

        html[data-theme="light"] .stat-card:hover {
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.12);
        }

        .stat-card .label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
        }

        .stat-card .sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .table-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--secondary);
        }

        th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(233, 69, 96, 0.05);
        }

        html[data-theme="light"] tbody tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }

        .party-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            background: var(--accent);
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .status-electo {
            color: var(--success);
            font-weight: 600;
        }

        .status-nao-electo {
            color: var(--text-muted);
        }

        .footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border);
            margin-top: 2rem;
        }

        .rank-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .rank-1 { background: gold; color: #000; }
        .rank-2 { background: silver; color: #000; }
        .rank-3 { background: #cd7f32; color: #000; }
        .rank-other { background: var(--secondary); color: var(--text-muted); }

        .municipios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.75rem;
            padding: 1.25rem;
        }

        .municipio-card {
            background: var(--secondary);
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .municipio-card:hover {
            border-color: var(--highlight);
            transform: scale(1.02);
        }

        .municipio-card .nome {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .municipio-card .votos {
            font-size: 1rem;
            font-weight: 700;
            color: var(--highlight);
            margin-top: 0.5rem;
        }

        .governor-race {
            background: linear-gradient(135deg, var(--accent) 0%, var(--secondary) 100%);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .governor-race h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .governor-race h3::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--warning);
            border-radius: 50%;
        }

        .detalhe-candidato-card {
            background: var(--card-bg);
            border: 2px solid var(--highlight);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detalhe-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .detalhe-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--highlight);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        .detalhe-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .detalhe-info p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }

        .detalhe-votos-municipios h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .voto-municipio-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .voto-mun-nome {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .voto-mun-votos {
            font-weight: 600;
            color: var(--highlight);
            font-size: 0.9rem;
            min-width: 120px;
            text-align: right;
        }

        .voto-mun-bar {
            width: 100px;
            height: 8px;
            background: var(--secondary);
            border-radius: 4px;
            overflow: hidden;
        }

        .voto-mun-bar-fill {
            height: 100%;
            background: var(--highlight);
            border-radius: 4px;
        }

        .regioes-titulo {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }

        .regioes-titulo h2 {
            font-size: 1.2rem;
            margin: 0;
        }

        .regioes-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1rem;
        }

        .regiao-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
        }

        .regiao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .regiao-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--highlight);
        }

        .regiao-total {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .regiao-candidatos {
            margin-bottom: 1rem;
        }

        .regiao-cand {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .regiao-cand-name {
            font-size: 0.85rem;
        }

        .regiao-cand-votos {
            font-weight: 600;
            color: var(--highlight);
            font-size: 0.85rem;
        }

        .regiao-cidades h4 {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .regiao-cidades-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .cidade-badge {
            background: var(--secondary);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .candidates-race {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .candidate-race {
            background: var(--secondary);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .candidate-race .position {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .candidate-race .info {
            flex: 1;
        }

        .candidate-race .name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .candidate-race .party {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .candidate-race .votes {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--highlight);
        }

        .candidate-race.winner {
            border: 2px solid var(--success);
        }

        .candidate-race.winner .position {
            background: var(--success);
            color: #000;
        }

        .mode-panel,
        .insights-panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .mode-panel h2,
        .insights-panel h2 {
            font-size: 1.15rem;
            margin-bottom: 0.75rem;
        }

        .mode-panel p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .highlights-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.75rem;
        }

        .highlight-item {
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.9rem 1rem;
            color: var(--text);
            font-size: 0.9rem;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }

        .insight-card {
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
        }

        .insight-card.runoff {
            border-color: var(--warning);
            box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.2);
        }

        .insight-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .insight-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .insight-meta {
            color: var(--text-muted);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .insight-total {
            color: var(--highlight);
            font-size: 1.2rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .insight-strong {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }

        .insight-strong-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 0.75rem;
        }

        .insight-strong-item .label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.35rem;
        }

        .insight-strong-item .value {
            font-size: 0.92rem;
            font-weight: 600;
        }

        .insight-list-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin: 0.85rem 0 0.4rem;
        }

        .insight-list {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .insight-list-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.35rem;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .loading::after {
            content: '';
            width: 24px;
            height: 24px;
            border: 3px solid var(--border);
            border-top-color: var(--highlight);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .filter-summary-cards {
            display: flex;
            gap: 0.75rem;
            flex-direction: row; /* Force horizontal */
            align-items: center;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.4rem 0.75rem; /* Slightly more compact */
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 110px; /* More compact */
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-2px);
        }

        .summary-card .label {
            font-size: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }

        .summary-card .value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }

        .summary-card.negative { border-left: 3px solid var(--danger); }
        .summary-card.accent { border-left: 3px solid var(--highlight); }

        html[data-theme="light"] .insight-strong-item {
            background: rgba(15, 23, 42, 0.03);
        }

        html[data-theme="light"] .summary-card {
            background: rgba(255, 255, 255, 0.82);
        }

        html[data-theme="light"] .summary-card:hover {
            background: rgba(255, 255, 255, 0.96);
        }

        html[data-theme="light"] .insight-list-row {
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] #loadingOverlay {
            background: linear-gradient(135deg, #f7fbff 0%, #edf3f9 50%, #f4f7fb 100%);
        }

        html[data-theme="light"] .loading-title {
            color: var(--text);
        }

        /* ========== MODERN LOADING OVERLAY ========== */
        #loadingOverlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #0a0a14 0%, #1a1a2e 50%, #0f1219 100%);
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
            border-top-color: var(--highlight);
            border-right-color: var(--highlight);
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
            border-top-color: var(--success);
            animation: spin 0.8s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        .loader-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: var(--highlight);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--highlight), 0 0 40px var(--highlight);
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
            color: #fff;
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
            background: var(--highlight);
            border-radius: 50%;
            animation: dotPulse 1.4s ease-in-out infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }

        /* ========== RESPONSIVE / MOBILE OPTIMIZATION ========== */
        @media (max-width: 1024px) {
            .container {
                padding: 1.5rem;
            }
            .regioes-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1.5rem 1rem;
            }
            .header h1 {
                font-size: 1.5rem;
            }
            .container {
                padding: 1rem;
            }
            .filters {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .insights-grid {
                grid-template-columns: 1fr;
            }
            .detalhe-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .detalhe-avatar {
                margin: 0 auto;
            }
            .voto-municipio-item {
                grid-template-columns: 1fr auto;
                gap: 0.5rem;
            }
            .voto-mun-bar {
                display: none; /* Hide bars on very small screens to save space */
            }
            .filter-summary-cards {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            .summary-card {
                flex: 1 1 calc(50% - 0.75rem);
                min-width: 140px;
            }
            
            /* Comparative Table Adjustments */
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .table-header h3 {
                font-size: 1rem;
            }
            td {
                font-size: 0.75rem; /* Smaller font for tables on mobile */
                padding: 0.5rem 0.75rem;
            }
            th {
                font-size: 0.65rem;
                padding: 0.5rem 0.75rem;
            }
            .insight-name { font-size: 0.9rem; }
            .insight-total { font-size: 1.1rem; }
            .highlight-item { font-size: 0.8rem; }
            .detalhe-candidato-card h2 { font-size: 1.2rem; }
            .detalhe-candidato-card h3 { font-size: 1rem; }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.25rem;
                gap: 0.5rem;
            }
            .header h1::before {
                height: 24px;
                width: 6px;
            }
            .stat-card .value {
                font-size: 1.5rem;
            }
            .summary-card {
                flex: 1 1 100%;
            }
            .turno-tab {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
                flex: 1;
                text-align: center;
            }
            .turno-tabs {
                width: 100%;
            }
        }

        /* Touch Optimizations */
        select, button, .turno-tab, .municipio-card {
            min-height: 44px; /* Standard touch target height */
        }

        /* Dynamic Grid and Flex Utilities */
        .insight-grid-dynamic {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }

        .comparison-filters {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1.5rem;
            flex-wrap: wrap; /* Changed from nowrap to wrap for mobile */
        }

        @media (max-width: 768px) {
            .insight-grid-dynamic {
                grid-template-columns: 1fr;
            }
            .comparison-filters {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .comparison-filters > div {
                width: 100%;
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

    <header class="header">
        <div class="header-copy">
        <h1>Painel Estatístico - Eleições Sergipe 2022</h1>
        <p>Resultados detalhados por candidato, partido e município | Dados TSE</p>
        <p class="page-context">Base oficial TSE • Sergipe 2022</p>
        <div class="header-actions">
            <div class="theme-switcher" role="group" aria-label="Selecionar tema">
                <button type="button" data-theme-choice="dark">Modo escuro</button>
                <button type="button" data-theme-choice="light">Modo claro</button>
            </div>
            <a href="eleicoes_municipais_se.php" style="display:inline-flex;align-items:center;gap:.6rem;padding:.85rem 1.2rem;background:linear-gradient(135deg,#d9f0ff 0%,#9fd7ff 100%);border:1px solid rgba(79,161,234,.35);border-radius:999px;color:#0f2d4d;text-decoration:none;font-weight:600;backdrop-filter:blur(8px);box-shadow:0 8px 22px rgba(79,161,234,.18);">
                Ir para Eleições municipais 2024 - SE
            </a>
        </div>
        </div>
        <div class="header-visual" aria-hidden="true">
            <img src="assets/urna-eletronica.png" alt="">
        </div>
    </header>

    <div class="container">
        <!-- Removed turno tabs: control now via Cargo select -->

        <div class="filters">
            <div class="filter-group">
                <label>Cargo</label>
                <select id="cargoFilter">
                    <option value="">Todos os Cargos</option>
                    <option value="Governador">Governador</option>
                    <option value="Senador">Senador</option>
                    <option value="Deputado Federal">Deputado Federal</option>
                    <option value="Deputado Estadual">Deputado Estadual</option>
                </select>
            </div>
            <div class="filter-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
                    <label style="margin-bottom:0">Candidato</label>
                    <label style="margin-bottom:0; display:flex; align-items:center; gap:4px; font-size:0.85rem; font-weight:normal; cursor:pointer;" title="Mostrar apenas candidatos eleitos">
                        <input type="checkbox" id="eleitosFilter" style="margin:0; width:auto;" onchange="onEleitosChange()"> Apenas Eleitos
                    </label>
                </div>
                <select id="candidatoFilter" onchange="loadData()">
                    <option value="">Selecione</option>
                    <option value="Todos">Todos</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Partido</label>
                <select id="partidoFilter" onchange="loadData()">
                    <option value="">Todos os Partidos</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Município</label>
                <select id="municipioFilter" onchange="loadData()">
                    <option value="">Todos os Municípios</option>
                    <option value="ARACAJU">ARACAJU</option><option value="NOSSA SENHORA DO SOCORRO">NOSSA SENHORA DO SOCORRO</option><option value="LAGARTO">LAGARTO</option><option value="ITABAIANA">ITABAIANA</option><option value="SÃO CRISTÓVÃO">SÃO CRISTÓVÃO</option><option value="ESTÂNCIA">ESTÂNCIA</option><option value="TOBIAS BARRETO">TOBIAS BARRETO</option><option value="SIMÃO DIAS">SIMÃO DIAS</option><option value="ITABAIANINHA">ITABAIANINHA</option><option value="NOSSA SENHORA DA GLÓRIA">NOSSA SENHORA DA GLÓRIA</option><option value="ITAPORANGA D AJUDA">ITAPORANGA D AJUDA</option><option value="PORTO DA FOLHA">PORTO DA FOLHA</option><option value="CAPELA">CAPELA</option><option value="BARRA DOS COQUEIROS">BARRA DOS COQUEIROS</option><option value="CANINDÉ DE SÃO FRANCISCO">CANINDÉ DE SÃO FRANCISCO</option><option value="POÇO VERDE">POÇO VERDE</option><option value="BOQUIM">BOQUIM</option><option value="NOSSA SENHORA DAS DORES">NOSSA SENHORA DAS DORES</option><option value="CARIRA">CARIRA</option><option value="MONTE ALEGRE DE SERGIPE">MONTE ALEGRE DE SERGIPE</option><option value="PROPRIÁ">PROPRIÁ</option><option value="PEDRINHAS">PEDRINHAS</option><option value="RIACHÃO DO DANTAS">RIACHÃO DO DANTAS</option><option value="SÃO DOMINGOS">SÃO DOMINGOS</option><option value="NOSSA SENHORA APARECIDA">NOSSA SENHORA APARECIDA</option><option value="PINHÃO">PINHÃO</option><option value="FREI PAULO">FREI PAULO</option><option value="TOMAR DO GERU">TOMAR DO GERU</option><option value="UMBAÚBA">UMBAÚBA</option><option value="CEDRO DE SÃO JOÃO">CEDRO DE SÃO JOÃO</option><option value="MARUIM">MARUIM</option><option value="RIBEIRÓPOLIS">RIBEIRÓPOLIS</option><option value="SANTO AMARO DAS BROTAS">SANTO AMARO DAS BROTAS</option><option value="GRACCHO CARDOSO">GRACCHO CARDOSO</option><option value="CARMÓPOLIS">CARMÓPOLIS</option><option value="SÃO MIGUEL DO ALEIXO">SÃO MIGUEL DO ALEIXO</option><option value="MOITA BONITA">MOITA BONITA</option><option value="MALHADOR">MALHADOR</option><option value="SANTA LUZIA DO ITANHY">SANTA LUZIA DO ITANHY</option><option value="SANTANA DO SÃO FRANCISCO">SANTANA DO SÃO FRANCISCO</option><option value="ILHA DAS FLORES">ILHA DAS FLORES</option><option value="MURIBECA">MURIBECA</option><option value="QUENGE">QUENGE</option><option value="SIRIRI">SIRIRI</option><option value="TELHA">TELHA</option><option value="PIRAMBU">PIRAMBU</option><option value="ROSIÁRIO DO CATETE">ROSÁRIO DO CATETE</option><option value="SANTA ROSA DE LIMA">SANTA ROSA DE LIMA</option><option value="LACEN">LACEN</option><option value="GENERAL MAYNARD">GENERAL MAYNARD</option><option value="ARAUÁ">ARAUÁ</option><option value="CUMBE">CUMBE</option><option value="SÃO FRANCISCO">SÃO FRANCISCO</option><option value="POÇO REDONDO">POÇO REDONDO</option><option value="MANGABEIRA">MANGABEIRA</option><option value="JAPARATUBA">JAPARATUBA</option><option value="AMPARO DE SÃO FRANCISCO">AMPARO DE SÃO FRANCISCO</option><option value="JAPOATÃ">JAPOATÃ</option><option value="SALGADO">SALGADO</option><option value="AQUIDABÃ">AQUIDABÃ</option><option value="MALHADA DOS BOIS">MALHADA DOS BOIS</option><option value="MACAMBIRA">MACAMBIRA</option><option value="PEDRA MOLE">PEDRA MOLE</option><option value="NEÓPOLIS">NEÓPOLIS</option><option value="DIVINA PASTORA">DIVINA PASTORA</option><option value="RIACHUELO">RIACHUELO</option><option value="ITABI">ITABI</option><option value="BREJO GRANDE">BREJO GRANDE</option><option value="INDIAROBA">INDIAROBA</option><option value="NOSSA SENHORA DE LOURDES">NOSSA SENHORA DE LOURDES</option><option value="GARARU">GARARU</option><option value="CANHOBA">CANHOBA</option><option value="AREIA BRANCA">AREIA BRANCA</option><option value="CAMPO DO BRITO">CAMPO DO BRITO</option><option value="CRISTINÁPOLIS">CRISTINÁPOLIS</option><option value="FEIRA NOVA">FEIRA NOVA</option>
                </select>
            </div>
            <!-- Removed Partido, Situação, and old Eleitos filters per request -->
        </div>

        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="label">Total de Votos</div>
                <div class="value" id="totalVotos">-</div>
                <div class="sub">Votos Nominais</div>
            </div>
            <div class="stat-card">
                <div class="label">Candidatos</div>
                <div class="value" id="totalCandidatos">-</div>
                <div class="sub">Candidatos Registrados</div>
            </div>
            <div class="stat-card">
                <div class="label">Municípios</div>
                <div class="value" id="totalMunicipios">-</div>
                <div class="sub">Municípios de Sergipe</div>
            </div>
            <div class="stat-card">
                <div class="label">Partidos</div>
                <div class="value" id="totalPartidos">-</div>
                <div class="sub">Partidos Participantes</div>
            </div>
        </div>

        <div id="electedGovernorBanner"></div>

        <div class="mode-panel">
            <h2 id="modeTitle">Geral</h2>
            <p id="modeDescription">Resumo geral com todos os candidatos eleitos e seus totais de votos.</p>
            <div class="highlights-list" id="modeHighlights"></div>
        </div>

        <div class="insights-panel">
            <h2>Análise por Candidato</h2>
            <div class="insights-grid" id="candidateInsightsGrid">
                <div class="loading">Carregando...</div>
            </div>
        </div>

        <div id="governorRaceSection"></div>

        <div id="detalheCandidatoSection" style="display: none;">
            <div class="detalhe-candidato-card">
                <div class="detalhe-header">
                    <div class="detalhe-avatar" id="detalheAvatar">-</div>
                    <div class="detalhe-info">
                        <h2 id="detalheNome">-</h2>
                        <p>Partido: <strong id="detalhePartido">-</strong> | Cargo: <strong id="detalheCargo">-</strong></p>
                        <p>Total de Votos: <strong id="detalheVotos" style="color: var(--highlight);">-</strong> | Situação: <strong id="detalheSituacao">-</strong></p>
                        <p id="detalheDestaques">-</p>
                    </div>
                </div>
                <div class="detalhe-votos-municipios">
                    <h3>Votos por Município</h3>
                    <div id="detalheVotosMunicipios"></div>
                </div>
            </div>
        </div>

        <div id="votosPorRegiaoSection" style="display: none;">
            <div class="regioes-titulo">
                <h2>Votos por Região - 2º Turno Governador</h2>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 id="tableTitle">Top 20 Candidatos por Votos</h3>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Candidato</th>
                            <th>Partido</th>
                            <th>Cargo</th>
                            <th>Turno</th>
                            <th>Votos</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody id="candidatosTable">
                        <tr><td colspan="7" class="loading">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container" id="municipiosStatsSection">
            <div class="table-header">
                <h3>Votos por Município</h3>
            </div>
            <div class="municipios-grid" id="municipiosGrid">
                <div class="loading">Carregando...</div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>Dados originários do Tribunal Superior Eleitoral (TSE) | Arquivo: votacao_candidato_munzona_2022_SE.csv</p>
    </footer>

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

        // Removed explicit turno control; cargo selection now defines the view

        // Carregar candidatos quando selecionar cargo
        document.getElementById('cargoFilter').addEventListener('change', function() {
            loadCandidatesForCargo();
        });

        function onEleitosChange() {
            // When eleitos filter changes, reload candidate list for current cargo
            loadCandidatesForCargo();
            loadData();
        }

        function loadCandidatesForCargo() {
            const cargo = document.getElementById('cargoFilter').value;
            const candidatoSelect = document.getElementById('candidatoFilter');
            candidatoSelect.innerHTML = '<option value="">Selecione</option><option value="Todos">Todos</option>';

            if (!cargo) {
                return;
            }

            const eleitosEl = document.getElementById('eleitosFilter');
            const eleitos = (eleitosEl && eleitosEl.checked) ? 'ELEITO' : '';
            const parts = ['api.php?cargo=' + encodeURIComponent(cargo)];
            if (eleitos) {
                parts.push('situacao=' + encodeURIComponent(eleitos));
            } else {
                // LIGHTWEIGHT: Fetch all candidates for dropdown without heavy data aggregates
                parts.push('only_candidatos=1');
                parts.push('any=1');
            }

            const url = parts.join('&');
            showLoading('Buscando candidatos...');
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (!data.candidatos) return;
                    data.candidatos.sort((a, b) => b.total_votos - a.total_votos);
                    data.candidatos.forEach(c => {
                        const option = document.createElement('option');
                        option.value = c.nm_candidato; // keep value as real name for API compatibility
                        const urna = c.nm_urna_candidato || c.nm_candidato;
                        option.textContent = urna + ' (' + (c.sg_partido || '-') + ') - ' + formatNumber(c.total_votos) + ' votos';
                        candidatoSelect.appendChild(option);
                    });
                })
                .catch(() => {})
                .finally(() => {
                    hideLoading();
                    // After loading candidates for the cargo, refresh the main view
                    loadData();
                });
        }

        function updateStats(stats) {
            document.getElementById('totalVotos').textContent = formatNumber(stats.total_votos || 0);
            document.getElementById('totalCandidatos').textContent = formatNumber(stats.total_candidatos || 0);
            document.getElementById('totalMunicipios').textContent = formatNumber(stats.total_municipios || 0);
            document.getElementById('totalPartidos').textContent = formatNumber(stats.total_partidos || 0);
        }

        function updateCandidatosTable(candidatos, insights = []) {
            const tbody = document.getElementById('candidatosTable');
            if (!candidatos || candidatos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nenhum resultado encontrado</td></tr>';
                return;
            }

            const cargoOrder = { 'Governador': 1, 'Senador': 2, 'Deputado Federal': 3, 'Deputado Estadual': 4 };
            const cargoLabels = { 'Governador': 'GOVERNADOR', 'Senador': 'SENADOR', 'Deputado Federal': 'DEPUTADO FEDERAL', 'Deputado Estadual': 'DEPUTADO ESTADUAL' };
            
            candidatos.sort((a, b) => {
                const orderA = cargoOrder[a.cargo] || 5;
                const orderB = cargoOrder[b.cargo] || 5;
                if (orderA !== orderB) return orderA - orderB;
                return b.total_votos - a.total_votos;
            });

            const grouped = {};
            candidatos.forEach(c => {
                if (!grouped[c.cargo]) grouped[c.cargo] = [];
                grouped[c.cargo].push(c);
            });

            let html = '';
            const cargoSequence = ['Governador', 'Senador', 'Deputado Federal', 'Deputado Estadual'];
            
            cargoSequence.forEach(cargo => {
                if (!grouped[cargo] || grouped[cargo].length === 0) return;
                
                const label = cargoLabels[cargo] || cargo;
                html += '<tr><td colspan="7" style="background: var(--accent); color: white; font-weight: 600; padding: 0.75rem 1rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">' + label + '</td></tr>';
                
                grouped[cargo].forEach((c, idx) => {
                    const rankClass = idx + 1 <= 3 ? 'rank-' + (idx + 1) : 'rank-other';
                    const statusClass = c.situacao && c.situacao.includes('ELEITO') ? 'status-electo' : 'status-nao-electo';
                    
                    const insight = insights.find(i => i.nm_candidato === c.nm_candidato && i.cargo === c.cargo);
                    const strongestCity = insight && insight.strongest_city ? insight.strongest_city.municipio : '-';
                    
                    html += '<tr>';
                    html += '<td><span class="rank-number ' + rankClass + '">' + (idx + 1) + '</span> • ' + strongestCity + '</td>';
                    html += '<td>' + (c.nm_urna_candidato || c.nm_candidato) + '</td>';
                    html += '<td><span class="party-badge">' + (c.sg_partido || '-') + '</span></td>';
                    html += '<td style="font-size: 0.75rem; color: var(--text-muted);">' + c.cargo + '</td>';
                    html += '<td>' + c.nr_turno + 'º</td>';
                    html += '<td>' + formatNumber(c.total_votos) + '</td>';
                    html += '<td class="' + statusClass + '">' + (c.situacao || '-') + '</td>';
                    html += '</tr>';
                });
            });
            tbody.innerHTML = html;
        }

        function updateMunicipios(municipios) {
            const grid = document.getElementById('municipiosGrid');
            if (!municipios || municipios.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: var(--text-muted);">Nenhum resultado encontrado</div>';
                return;
            }

            let html = '';
            grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(180px, 1fr))';

            municipios.forEach(m => {
                html += '<div class="municipio-card">';
                html += '<div class="nome" style="font-size: 0.8rem;">' + m.municipio + '</div>';
                html += '<div class="votos" style="font-size: 1rem;">' + formatNumber(m.total_votos) + '</div>';
                html += '</div>';
            });
            grid.innerHTML = html;
        }

        function updateModePanel(ui, highlights) {
            document.getElementById('modeTitle').textContent = ui?.modeTitle || 'Geral';
            document.getElementById('modeDescription').textContent = ui?.modeDescription || '';
            document.getElementById('tableTitle').textContent = ui?.tableTitle || 'Top Candidatos';

            const sectionTitles = document.querySelectorAll('.table-header h3');
            if (sectionTitles[1]) {
                sectionTitles[1].textContent = ui?.municipiosTitle || 'Votos por Município';
                // ensure some spacing above the second table header so it doesn't stick to previous table
                if (sectionTitles[1].parentElement) sectionTitles[1].parentElement.style.marginTop = '1.25rem';
            }

            const container = document.getElementById('modeHighlights');
            if (!highlights || highlights.length === 0) {
                container.innerHTML = '<div class="highlight-item">Nenhum destaque disponível para este recorte.</div>';
                return;
            }

            container.innerHTML = highlights
                .map(item => '<div class="highlight-item">' + item + '</div>')
                .join('');
        }

        function updateCandidateInsights(insights, governors) {
            const grid = document.getElementById('candidateInsightsGrid');
            if (!insights || insights.length === 0) {
                grid.innerHTML = '<div class="highlight-item">Nenhum candidato encontrado para o recorte selecionado.</div>';
                return;
            }

            const runoffNames = new Set((governors || []).slice(0, 2).map(item => item.nm_candidato));
            let html = '';

            insights.forEach(candidate => {
                const isRunoff = candidate.cargo === 'Governador' && runoffNames.has(candidate.nm_candidato);
                const topCities = (candidate.topCities || []).map(city =>
                    '<div class="insight-list-row"><span>' + city.municipio + '</span><strong>' + formatNumber(city.total_votos) + '</strong></div>'
                ).join('');
                const topRegions = (candidate.topRegions || []).map(region =>
                    '<div class="insight-list-row"><span>' + region.nome + '</span><strong>' + formatNumber(region.total_votos) + '</strong></div>'
                ).join('');

                let borderColor = '';
                if (candidate.nm_candidato.includes('FABIO') || candidate.nm_candidato.includes('FÁBIO')) borderColor = 'var(--success)';
                else if (candidate.nm_candidato.includes('ROGERIO') || candidate.nm_candidato.includes('ROGÉRIO')) borderColor = 'var(--highlight)';

                html += '<div class="insight-card ' + (isRunoff ? 'runoff' : '') + '"' + (borderColor ? ' style="border-color: ' + borderColor + ' !important;"' : '') + '>';
                html += '<div class="insight-header">';
                html += '<div><div class="insight-name">' + (candidate.nm_urna_candidato || candidate.nm_candidato) + '</div>';
                html += '<div class="insight-meta">' + candidate.cargo + ' | ' + (candidate.sg_partido || '-') + ' | ' + (candidate.situacao || '-') + '</div></div>';
                html += '<div class="insight-total">' + formatNumber(candidate.total_votos) + '</div>';
                html += '</div>';
                html += '<div class="insight-strong">';
                html += '<div class="insight-strong-item"><div class="label">Cidade Mais Forte</div><div class="value">' +
                    (candidate.strongest_city ? candidate.strongest_city.municipio + ' (' + formatNumber(candidate.strongest_city.total_votos) + ')' : 'Sem dados') +
                    '</div></div>';
                html += '<div class="insight-strong-item"><div class="label">Região Mais Forte</div><div class="value">' +
                    (candidate.strongest_region ? candidate.strongest_region.nome + ' (' + formatNumber(candidate.strongest_region.total_votos) + ')' : 'Sem dados') +
                    '</div></div>';
                html += '</div>';
                if (isRunoff) {
                    html += '<div class="highlight-item">Classificado para o 2º turno.</div>';
                }
                html += '<div class="insight-list-title">Cidades mais fortes</div><div class="insight-list">' + (topCities || '<div class="insight-list-row"><span>Sem dados</span><strong>-</strong></div>') + '</div>';
                html += '<div class="insight-list-title">Regiões mais fortes</div><div class="insight-list">' + (topRegions || '<div class="insight-list-row"><span>Sem dados</span><strong>-</strong></div>') + '</div>';
                html += '</div>';
            });

            grid.innerHTML = html;
        }

        function updateGovernorRace(candidatos) {
            const section = document.getElementById('governorRaceSection');
            
            if (!candidatos || candidatos.length === 0) {
                section.innerHTML = '';
                return;
            }

            const governors = [...candidatos];
            
            if (governors.length === 0) {
                section.innerHTML = '';
                return;
            }

            governors.sort((a, b) => b.total_votos - a.total_votos);

            let html = '<div class="governor-race">';
            html += '<h3>Resultado 2º Turno - Governador</h3>';
            html += '<div class="candidates-race">';
            
            governors.forEach((g, index) => {
                const isWinner = index === 0;
                html += '<div class="candidate-race ' + (isWinner ? 'winner' : '') + '">';
                html += '<div class="position">' + (index + 1) + 'º</div>';
                html += '<div class="info">';
                html += '<div class="name">' + (g.nm_urna_candidato || g.nm_candidato) + '</div>';
                html += '<div class="party">Partido: ' + (g.sg_partido || '-') + '</div>';
                html += '</div>';
                html += '<div class="votes">' + formatNumber(g.total_votos) + '</div>';
                html += '</div>';
            });
            
            html += '</div></div>';
            section.innerHTML = html;


        }

        // Render comparative view for Governor: 1º turno (top 5) and 2º turno (top 2)
        function renderGovernorComparative(data1, data2) {
            const section = document.getElementById('governorRaceSection');
            let html = '';

            const g1 = data1.candidatosGovernador || data1.candidatos || [];
            const g2 = data2.candidatosGovernador || data2.candidatos || [];
            // choose top two: prefer 2nd-turn winners if available
            const top2 = g2.length >= 2 ? g2.slice(0,2) : (g1.slice(0,2));
            const winner = top2[0] || null;
            const runner = top2[1] || null;

            function findCandidateTotal(name, list) {
                if (!list) return 0;
                const found = list.find(x => x.nm_candidato === name);
                return found ? (found.total_votos || 0) : 0;
            }

            function candidateRegions(name, votosPorRegiao) {
                if (!votosPorRegiao) return [];
                const arr = [];
                votosPorRegiao.forEach(r => {
                    const c = (r.candidatos || []).find(x => x.nm_candidato === name);
                    if (c) arr.push({ nome: r.nome, total_votos: c.total_votos, regionTotal: r.total_votos });
                });
                arr.sort((a,b)=> b.total_votos - a.total_votos);
                return arr;
            }

            function candidateTopCitiesFromInsights(name, insights) {
                if (!insights) return [];
                const cand = (insights || []).find(i => i.nm_candidato === name);
                return cand ? (cand.topCities || []) : [];
            }

            // Render elected governor banner into the top banner container
            (function(){
                const bannerEl = document.getElementById('electedGovernorBanner');
                if (!bannerEl) return;
                if (g2 && g2.length) {
                    const elected = g2[0];
                    let bannerHtml = '';
                    bannerHtml += '<div class="detalhe-candidato-card" style="border-color: var(--success); margin:0 0 1rem 0">';
                    bannerHtml += '<div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0">';
                    bannerHtml += '<div><h2 style="margin:0">Governador Eleito: ' + (elected.nm_urna_candidato || elected.nm_candidato) + '</h2><div style="color:var(--text-muted)">' + (elected.sg_partido||'-') + '</div></div>';
                    bannerHtml += '<div style="text-align:right"><div style="font-weight:800;font-size:1.4rem;color:var(--success)">' + formatNumber(elected.total_votos) + '</div><div style="font-size:0.9rem;color:var(--text-muted)">Votos no 2º turno</div></div>';
                    bannerHtml += '</div></div>';
                    bannerEl.innerHTML = bannerHtml;
                } else {
                    bannerEl.innerHTML = '';
                }
            })();

            // 2 cards: eleito e 2º colocado
            html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-top:1rem">';
            [winner, runner].forEach((c, idx) => {
                if (!c) return;
                const name = c.nm_candidato;
                const total1 = findCandidateTotal(name, g1);
                const total2 = findCandidateTotal(name, g2);
                const regions1 = candidateRegions(name, data1.votosPorRegiao || []);
                const regions2 = candidateRegions(name, data2.votosPorRegiao || []);

                let borderColor = '';
                if (name.includes('FABIO') || name.includes('FÁBIO')) borderColor = 'var(--success)';
                else if (name.includes('ROGERIO') || name.includes('ROGÉRIO')) borderColor = 'var(--highlight)';

                html += '<div class="detalhe-candidato-card"' + (borderColor ? ' style="border-color: ' + borderColor + ' !important;"' : '') + '>';
                html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">';
                const displayName = c.nm_urna_candidato || name;
                html += '<div><h3 style="margin:0">' + displayName + '</h3><div style="color:var(--text-muted)">' + (c.sg_partido||'-') + '</div></div>';
                html += '<div style="text-align:right"><div style="font-weight:700;font-size:1.25rem">' + formatNumber(total2 || total1) + '</div><div style="font-size:0.85rem;color:var(--text-muted)">Total (2º/1º)</div></div>';
                html += '</div>';

                html += '<div style="display:flex;gap:0.5rem;flex-wrap:wrap">';
                html += '<div style="flex:1;min-width:180px"><div class="label">Regiões mais votado - 1º Turno</div>';
                if (regions1.length) {
                    regions1.slice(0,4).forEach(r=>{
                        html += '<div style="margin-top:0.4rem">' + r.nome + ': <strong>' + formatNumber(r.total_votos) + '</strong></div>';
                    });
                } else {
                    html += '<div style="color:var(--text-muted);margin-top:0.4rem">Sem dados</div>';
                }
                html += '</div>';

                html += '<div style="flex:1;min-width:180px"><div class="label">Regiões mais votado - 2º Turno</div>';
                if (regions2.length) {
                    regions2.slice(0,4).forEach(r=>{
                        html += '<div style="margin-top:0.4rem">' + r.nome + ': <strong>' + formatNumber(r.total_votos) + '</strong></div>';
                    });
                } else {
                    html += '<div style="color:var(--text-muted);margin-top:0.4rem">Sem dados</div>';
                }
                html += '</div>';

                html += '</div>'; // end flex
                html += '</div>'; // end card
            });
            html += '</div>'; // end grid

            // Massificação: por região, calcular diferença de votos (2º - 1º) por candidato
            html += '<div style="margin-top:1rem"><h3>Análise Detalhada por Candidato</h3>'; 
            html += '<div class="insight-grid-dynamic">';
            const insights1 = data1.candidateInsights || [];
            const insights2 = data2.candidateInsights || [];
            [winner, runner].forEach(c => {
                if (!c) return;
                const name = c.nm_candidato;
                const i1 = (insights1 || []).find(x=>x.nm_candidato===name) || {};
                const i2 = (insights2 || []).find(x=>x.nm_candidato===name) || {};
                let insightBorder = '';
                if (name.includes('FABIO') || name.includes('FÁBIO')) insightBorder = 'var(--success)';
                else if (name.includes('ROGERIO') || name.includes('ROGÉRIO')) insightBorder = 'var(--highlight)';

                html += '<div class="insight-card"' + (insightBorder ? ' style="border-color: ' + insightBorder + ' !important;"' : '') + '>';
                const displayName = c.nm_urna_candidato || name;
                html += '<div class="insight-header"><div><div class="insight-name">' + displayName + '</div><div class="insight-meta">' + (c.sg_partido||'-') + '</div></div>';
                const totalT1 = i1.total_votos || findCandidateTotal(name, g1) || 0;
                const totalT2 = i2.total_votos || findCandidateTotal(name, g2) || 0;
                // Destacar 2º turno: número maior, cor de sucesso e posicionado acima do 1º turno
                html += '<div style="text-align:right">';
                html += '<div style="font-size:0.85rem;color:var(--text-muted)">2º Turno</div>';
                html += '<div style="font-weight:900;font-size:1.35rem;color:var(--success);margin-bottom:6px">' + formatNumber(totalT2) + '</div>';
                html += '<div style="font-size:0.75rem;color:var(--text-muted)">1º Turno</div>';
                html += '<div style="font-size:1rem;color:var(--text-muted)">' + formatNumber(totalT1) + '</div>';
                html += '</div>';
                html += '</div>';
                html += '<div class="insight-list-title">Cidades mais votado (1º Turno)</div>';
                const topC1 = (i1.topCities||[]).slice(0,4);
                if (topC1.length) topC1.forEach(ci=> html += '<div class="insight-list-row"><span>' + ci.municipio + '</span><strong>' + formatNumber(ci.total_votos) + '</strong></div>');
                else html += '<div style="color:var(--text-muted)">Sem dados</div>';
                html += '<div class="insight-list-title" style="margin-top:0.6rem">Cidades mais votado (2º Turno)</div>';
                const topC2 = (i2.topCities||[]).slice(0,4);
                if (topC2.length) topC2.forEach(ci=> html += '<div class="insight-list-row"><span>' + ci.municipio + '</span><strong>' + formatNumber(ci.total_votos) + '</strong></div>');
                else html += '<div style="color:var(--text-muted)">Sem dados</div>';
                html += '</div>';
            });
            html += '</div></div>';

            // Massificação: por região, calcular diferença de votos (2º - 1º) por candidato
            html += '<div style="margin-top:1rem"><h3>Comparativo por Região (diferença de votos por candidato)</h3>';
            const regionsMap = {};
            (data1.votosPorRegiao||[]).forEach(r => { regionsMap[r.nome] = regionsMap[r.nome] || {}; regionsMap[r.nome].t1 = r; });
            (data2.votosPorRegiao||[]).forEach(r => { regionsMap[r.nome] = regionsMap[r.nome] || {}; regionsMap[r.nome].t2 = r; });
            const regionNames = Object.keys(regionsMap);
            regionNames.forEach(rname => {
                const r = regionsMap[rname];
                html += '<div class="regiao-card" style="margin-top:0.75rem">';
                html += '<div class="regiao-header"><h3>' + rname + '</h3>';
                html += '</div>';

                // two columns for winner and runner with vote differences
                html += '<div class="insight-grid-dynamic" style="grid-template-columns:repeat(2,1fr);margin-top:0.75rem">'; // Uses utility but forces 2 col on large, stacks on mobile via media query override if needed
                [winner, runner].forEach(cand => {
                    if (!cand) return;
                    const name = cand.nm_candidato;
                    let v1 = 0, v2 = 0;
                    if (r.t1 && r.t1.candidatos) {
                        const ent = (r.t1.candidatos||[]).find(x=>x.nm_candidato===name);
                        v1 = ent ? ent.total_votos : 0;
                    }
                    if (r.t2 && r.t2.candidatos) {
                        const ent = (r.t2.candidatos||[]).find(x=>x.nm_candidato===name);
                        v2 = ent ? ent.total_votos : 0;
                    }
                    const diff = v2 - v1;
                    let boxColor = '';
                    if (name.includes('FABIO') || name.includes('FÁBIO')) boxColor = 'var(--success)';
                    else if (name.includes('ROGERIO') || name.includes('ROGÉRIO')) boxColor = 'var(--highlight)';
                    html += '<div class="highlight-item"' + (boxColor ? ' style="border-color: ' + boxColor + ' !important;"' : '') + '>';
                    const displayName = cand.nm_urna_candidato || name;
                    html += '<div style="font-weight:700">' + displayName + '</div>';
                    html += '<div style="margin-top:0.4rem">1º turno: <strong>' + formatNumber(v1) + '</strong></div>';
                    html += '<div>2º turno: <strong>' + formatNumber(v2) + '</strong></div>';
                    html += '<div style="margin-top:0.4rem">Dif (2º-1º): <strong>' + (diff >= 0 ? '+' : '') + formatNumber(diff) + '</strong></div>';
                    html += '</div>';
                });
                html += '</div>';

                html += '</div>';
            });
            html += '</div>';

            // Tabela com todas as cidades: votos por candidato no 1º e 2º turno
            const cityRows1 = data1.cityCandidateVotes || [];
            const cityRows2 = data2.cityCandidateVotes || [];
            const cityMap = {};
            function addRowToMap(row, campo) {
                const city = row.municipio || row.municipio_nome || row.municipio;
                cityMap[city] = cityMap[city] || {};
                cityMap[city][row.nm_candidato] = cityMap[city][row.nm_candidato] || {t1:0,t2:0};
                cityMap[city][row.nm_candidato][campo] = row.total_votos || 0;
            }
            cityRows1.forEach(r => addRowToMap(r,'t1'));
            cityRows2.forEach(r => addRowToMap(r,'t2'));
            const cities = Object.keys(cityMap).sort();
            if (cities.length) {
                const candA = winner ? winner.nm_candidato : null;
                const candB = runner ? runner.nm_candidato : null;
                html += '<div style="margin-top:1rem; margin-bottom: 100px;"><h3>Tabela — Votos por Município (1º vs 2º turno)</h3>';
                // filtros para a tabela (usam .filter-group para herdar estilos)
                html += '<div class="comparison-filters" style="margin-top:0.75rem;">';
                html += '<div style="display:flex; gap:0.75rem; align-items:flex-end;">';
                html += '<div class="filter-group" style="margin:0">';
                html += '<label>Candidato</label>';
                html += '<select id="cityTableCandidateFilter">';
                html += '<option value="Todos">Todos</option>';
                if (candA) html += '<option value="' + candA + '">' + candA + '</option>';
                if (candB) html += '<option value="' + candB + '">' + candB + '</option>';
                html += '</select>';
                html += '</div>';
                html += '<div class="filter-group" style="margin:0">';
                html += '<label>Tipo</label>';
                html += '<select id="cityTableFilter">';
                html += '<option value="todos">Todos</option>';
                html += '<option value="venceu1">Venceu no 1º turno</option>';
                html += '<option value="venceu2">Venceu no 2º turno</option>';
                html += '<option value="ambos">Venceu em ambos</option>';
                html += '<option value="venceu1perdeu2">Venceu 1º e perdeu 2º</option>';
                html += '<option value="perdeu1venceu2">Perdeu 1º e venceu 2º</option>';
                html += '<option value="perdeu_ambos">Perdeu em ambos</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>'; // end left filters
                
                html += '<div style="display:flex; align-items:center; gap:1.25rem;">';
                html += '<div id="cityVisibleCount" style="font-size:0.85rem; color:var(--text-muted); font-weight:500; white-space:nowrap; text-align:right;"></div>';
                html += '<div id="cityFilterSummary" class="filter-summary-cards"></div>';
                html += '</div>'; // end right summary
                html += '</div>'; // end main container flex
                html += '<div class="table-wrapper" style="margin-top:0.75rem"><table id="cityComparisonTable" style="width:100%;border-collapse:collapse"><thead><tr><th>Município</th>';
                if (candA && candB) {
                    html += '<th>' + candA + ' (1º)</th><th>' + candB + ' (1º)</th><th>Dif. (1º)</th><th>Vencedor (1º)</th>';
                    html += '<th>' + candA + ' (2º)</th><th>' + candB + ' (2º)</th><th>Dif. (2º)</th><th>Vencedor (2º)</th>';
                }
                html += '</tr></thead><tbody>';
                cities.forEach(city => {
                    const row = cityMap[city];
                    const a1 = Number((row[candA] && row[candA].t1) || 0);
                    const b1 = Number((row[candB] && row[candB].t1) || 0);
                    const a2 = Number((row[candA] && row[candA].t2) || 0);
                    const b2 = Number((row[candB] && row[candB].t2) || 0);
                    
                    // Default differences used for initial render
                    const dif1 = a1 - b1;
                    const dif2 = a2 - b2;
                    const pre1 = dif1 > 0 ? '+' : (dif1 < 0 ? '-' : '');
                    const pre2 = dif2 > 0 ? '+' : (dif2 < 0 ? '-' : '');
                    
                    const win1 = (a1 === b1) ? 'Empate' : (a1 > b1 ? candA : candB);
                    const win2 = (a2 === b2) ? 'Empate' : (a2 > b2 ? candA : candB);
                    html += '<tr data-a1="' + a1 + '" data-b1="' + b1 + '" data-a2="' + a2 + '" data-b2="' + b2 + '"><td>' + city + '</td>';
                    html += '<td>' + formatNumber(a1) + '</td><td>' + formatNumber(b1) + '</td><td class="col-dif1">' + pre1 + formatNumber(Math.abs(dif1)) + '</td><td>' + win1 + '</td>';
                    html += '<td>' + formatNumber(a2) + '</td><td>' + formatNumber(b2) + '</td><td class="col-dif2">' + pre2 + formatNumber(Math.abs(dif2)) + '</td><td>' + win2 + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
                // handlers for the table filters will be bound after inserting HTML
            }

            section.innerHTML = html;

            // bind handlers for the city comparison table created by this render
            (function(){
                const f1 = document.getElementById('cityTableCandidateFilter');
                const f2 = document.getElementById('cityTableFilter');
                const btn = document.getElementById('cityTableFilterBtn');
                function applyCityFilter(){
                    const tbl = document.getElementById('cityComparisonTable');
                    if(!tbl) return;
                    const selCand = f1 ? f1.value : 'Todos';
                    const selType = f2 ? f2.value : 'todos';
                    const tbody = tbl.tBodies[0];
                    if(!tbody) return;
                    function normalize(s){ return (s||'').toString().normalize('NFD').replace(/\p{Diacritic}/gu,'').replace(/[\u0300-\u036f]/g,'').toUpperCase().trim(); }
                    const selCandNorm = normalize(selCand);
                    
                    console.log("Executando applyCityFilter. selCand:", selCand, "| selType:", selType);
                    
                    const nameB = runner ? runner.nm_candidato : null;
                    const nameBNorm = normalize(nameB);
                    const isB = (selCandNorm === nameBNorm);
                    console.log("isB (candidato B selecionado/Rogério?):", isB);

                    function parseFmt(val) {
                        return Number((val || '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                    }

                    let tA1 = 0, tB1 = 0, tA2 = 0, tB2 = 0;
                    let visibleCount = 0;

                    Array.from(tbody.rows).forEach(function(r){
                        const win1 = r.cells[4] ? r.cells[4].textContent.trim() : '';
                        const win2 = r.cells[8] ? r.cells[8].textContent.trim() : '';
                        const win1Norm = normalize(win1);
                        const win2Norm = normalize(win2);
                        let show = true;
                        if(selType !== 'todos' && selCand !== 'Todos'){
                            switch(selType){
                                case 'venceu1': show = (win1Norm === selCandNorm); break;
                                case 'venceu2': show = (win2Norm === selCandNorm); break;
                                case 'ambos': show = (win1Norm === selCandNorm && win2Norm === selCandNorm); break;
                                case 'venceu1perdeu2': show = (win1Norm === selCandNorm && win2Norm !== selCandNorm); break;
                                case 'perdeu1venceu2': show = (win1Norm !== selCandNorm && win2Norm === selCandNorm); break;
                                case 'perdeu_ambos': show = (win1Norm !== selCandNorm && win2Norm !== selCandNorm); break;
                                default: show = true;
                            }
                        } else if (selType !== 'todos' && selCand === 'Todos') {
                            switch(selType){
                                case 'venceu1': show = (win1Norm !== 'EMPATE'); break;
                                case 'venceu2': show = (win2Norm !== 'EMPATE'); break;
                                case 'ambos': show = (win1Norm !== 'EMPATE' && win2Norm !== 'EMPATE'); break;
                                case 'venceu1perdeu2': show = (win1Norm !== 'EMPATE' && win2Norm === 'EMPATE'); break;
                                case 'perdeu1venceu2': show = (win1Norm === 'EMPATE' && win2Norm !== 'EMPATE'); break;
                                case 'perdeu_ambos': show = (win1Norm === 'EMPATE' && win2Norm === 'EMPATE'); break;
                                default: show = true;
                            }
                        }
                        
                        if(r.cells[0].textContent.trim() === 'AQUIDABÃ') {
                            console.log('AQUIDABÃ -> selType:', selType, 'win2Norm:', win2Norm, 'selCandNorm:', selCandNorm, 'show:', show);
                        }
                        r.style.display = show ? '' : 'none';
                        if (show) {
                            visibleCount++;
                            const vA1 = Number(r.getAttribute('data-a1') || 0);
                            const vB1 = Number(r.getAttribute('data-b1') || 0);
                            const vA2 = Number(r.getAttribute('data-a2') || 0);
                            const vB2 = Number(r.getAttribute('data-b2') || 0);
                            
                            tA1 += vA1; tB1 += vB1; tA2 += vA2; tB2 += vB2;

                            // Update the "Dif" cells in the table based on selected candidate
                            const d1 = isB ? (vB1 - vA1) : (vA1 - vB1);
                            const d2 = isB ? (vB2 - vA2) : (vA2 - vB2);
                            
                            const p1 = d1 > 0 ? '+' : (d1 < 0 ? '-' : '');
                            const p2 = d2 > 0 ? '+' : (d2 < 0 ? '-' : '');
                            
                            const c3 = r.querySelector('.col-dif1');
                            const c7 = r.querySelector('.col-dif2');
                            if(c3) c3.textContent = p1 + formatNumber(Math.abs(d1));
                            if(c7) c7.textContent = p2 + formatNumber(Math.abs(d2));
                        }
                    });

                    const counter = document.getElementById('cityVisibleCount');
                    if (counter) counter.textContent = visibleCount + ' municípios visíveis';

                    const summary = document.getElementById('cityFilterSummary');
                    if (summary) {
                        // Decide which turn totals to show: if filtering by a specific turn or both
                        const show1 = (selType === 'todos' || selType === 'venceu1' || selType === 'ambos' || selType === 'venceu1perdeu2' || selType === 'perdeu1venceu2' || selType === 'perdeu_ambos');
                        const show2 = (selType === 'todos' || selType === 'venceu2' || selType === 'ambos' || selType === 'venceu1perdeu2' || selType === 'perdeu1venceu2' || selType === 'perdeu_ambos');
                        
                        // For simplicity, sum both if it's "todos" or "ambos", otherwise show the relevant one
                        let finalA = 0, finalB = 0, turnLabel = '';
                        if (selType.includes('1') && !selType.includes('2')) {
                            finalA = tA1; finalB = tB1; turnLabel = '1º Turno';
                        } else if (selType.includes('2') && !selType.includes('1')) {
                            finalA = tA2; finalB = tB2; turnLabel = '2º Turno';
                        } else {
                            // default to combined or 2nd turn as it's more definitive
                            finalA = tA1 + tA2; finalB = tB1 + tB2; turnLabel = 'Total (1º+2º)';
                        }

                        // Determine who is the primary candidate for the summary cards
                        const nameA = winner ? winner.nm_candidato : null;
                        const nameB = runner ? runner.nm_candidato : null;
                        const nameANorm = normalize(nameA);
                        const nameBNorm = normalize(nameB);

                        let mainVotes = 0, oppVotes = 0, mainName = '', oppName = '';
                        
                        if (selCandNorm === nameBNorm) {
                            mainVotes = finalB;
                            oppVotes = finalA;
                            mainName = nameB;
                            oppName = nameA;
                        } else {
                            mainVotes = finalA;
                            oppVotes = finalB;
                            mainName = (selCand !== 'Todos') ? selCand : nameA;
                            oppName = (selCand !== 'Todos' && selCandNorm === nameANorm) ? nameB : (selCand === 'Todos' ? nameB : 'Adversário');
                        }

                        const actualDiff = mainVotes - oppVotes;
                        const actualDiffFmt = (actualDiff > 0 ? '+' : (actualDiff < 0 ? '-' : '')) + formatNumber(Math.abs(actualDiff));
                        const actualDiffClass = actualDiff >= 0 ? 'positive' : 'negative';

                        let cardsHtml = '';
                        cardsHtml += '<div class="summary-card accent"><div class="label">' + mainName + '</div><div class="value">' + formatNumber(mainVotes) + '</div></div>';
                        cardsHtml += '<div class="summary-card"><div class="label">' + oppName + '</div><div class="value">' + formatNumber(oppVotes) + '</div></div>';
                        cardsHtml += '<div class="summary-card ' + actualDiffClass + '"><div class="label">Saldo (' + turnLabel + ')</div><div class="value">' + actualDiffFmt + '</div></div>';
                        summary.innerHTML = cardsHtml;
                        summary.style.display = 'flex'; // Ensure it's visible
                    }
                }
                if (f1) f1.addEventListener('change', applyCityFilter);
                if (f2) f2.addEventListener('change', applyCityFilter);
                if (btn) btn.addEventListener('click', applyCityFilter);
                setTimeout(applyCityFilter, 100);
            })();
        }


        function updateDetalheCandidato(candidato, votosPorMunicipio) {
            const container = document.getElementById('detalheCandidatoSection');
            
            if (!candidato) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            
            // Info do candidato
            const dispName = candidato.nm_urna_candidato || candidato.nm_candidato;
            document.getElementById('detalheAvatar').textContent = dispName.charAt(0);
            document.getElementById('detalheNome').textContent = dispName;
            document.getElementById('detalhePartido').textContent = candidato.sg_partido;
            document.getElementById('detalheCargo').textContent = candidato.cargo;
            document.getElementById('detalheVotos').textContent = formatNumber(candidato.total_votos);
            document.getElementById('detalheSituacao').textContent = candidato.situacao;
            document.getElementById('detalheDestaques').textContent =
                'Cidade mais forte: ' + (candidato.strongest_city ? candidato.strongest_city.municipio + ' (' + formatNumber(candidato.strongest_city.total_votos) + ')' : 'Sem dados') +
                ' | Região mais forte: ' + (candidato.strongest_region ? candidato.strongest_region.nome + ' (' + formatNumber(candidato.strongest_region.total_votos) + ')' : 'Sem dados');
            
            // Votos por município
            const votosContainer = document.getElementById('detalheVotosMunicipios');
            let html = '';
            if (votosPorMunicipio && votosPorMunicipio.length > 0) {
                votosPorMunicipio.forEach(v => {
                    const pct = ((v.total_votos / candidato.total_votos) * 100).toFixed(1);
                    html += '<div class="voto-municipio-item">';
                    html += '<div class="voto-mun-nome">' + v.municipio + '</div>';
                    html += '<div class="voto-mun-votos">' + formatNumber(v.total_votos) + ' (' + pct + '%)</div>';
                    html += '<div class="voto-mun-bar"><div class="voto-mun-bar-fill" style="width: ' + pct + '%"></div></div>';
                    html += '</div>';
                });
            } else {
                html = '<p style="color: var(--text-muted);">Nenhum dado disponível</p>';
            }
            votosContainer.innerHTML = html;
        }

        function updateVotosPorRegiao(votosPorRegiao, candidatoSelecionado = null) {
            const container = document.getElementById('votosPorRegiaoSection');
            
            if (!votosPorRegiao || votosPorRegiao.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            
            let html = '<div class="regioes-container">';
            
            votosPorRegiao.forEach(regiao => {
                html += '<div class="regiao-card">';
                html += '<div class="regiao-header">';
                html += '<h3>' + regiao.nome + '</h3>';
                html += '<div class="regiao-total">Total: ' + formatNumber(regiao.total_votos) + ' votos</div>';
                html += '</div>';
                if (candidatoSelecionado && regiao.strongest_city) {
                    html += '<div class="highlight-item" style="margin-bottom: 0.75rem;">Cidade mais forte: ' + regiao.strongest_city.municipio + ' (' + formatNumber(regiao.strongest_city.total_votos) + ' votos)</div>';
                }
                
                // Candidatos na região
                if (!candidatoSelecionado && regiao.candidatos && regiao.candidatos.length > 0) {
                    html += '<div class="regiao-candidatos">';
                    regiao.candidatos.forEach(c => {
                        html += '<div class="regiao-cand">';
                        const dispCName = c.nm_urna_candidato || c.nm_candidato;
                        html += '<span class="regiao-cand-name">' + dispCName + ' (' + c.sg_partido + ')</span>';
                        html += '<span class="regiao-cand-votos">' + formatNumber(c.total_votos) + '</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                
                // Lista de cidades
                if (regiao.cidades && regiao.cidades.length > 0) {
                    html += '<div class="regiao-cidades">';
                    html += '<h4>Cidades:</h4>';
                    html += '<div class="regiao-cidades-list">';
                    regiao.cidades.forEach(c => {
                        html += '<span class="cidade-badge">' + c.municipio + ': ' + formatNumber(c.total_votos) + '</span>';
                    });
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('pt-BR').format(num);
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

        async function loadPartidos() {
            const partidoSelect = document.getElementById('partidoFilter');
            if (!partidoSelect) return;
            
            try {
                const response = await fetch('api.php?only_partidos=1');
                const data = await response.json();
                
                partidoSelect.innerHTML = '<option value="">Todos os Partidos</option>';
                
                if (data.partidos) {
                    data.partidos.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.sg_partido;
                        option.textContent = p.sg_partido + ' (' + formatNumber(p.votos) + ')';
                        partidoSelect.appendChild(option);
                    });
                }
            } catch (e) {
                console.error('Erro ao carregar partidos:', e);
            }
        }

        async function loadData() {
            const paramsObj = {
                cargo: document.getElementById('cargoFilter').value,
                candidato: document.getElementById('candidatoFilter').value,
                municipio: document.getElementById('municipioFilter').value,
                partido: document.getElementById('partidoFilter').value
            };
            const eleitosEl = document.getElementById('eleitosFilter');
            const isEleitos = (eleitosEl && eleitosEl.checked);
            if (isEleitos) {
                paramsObj.situacao = 'ELEITO';
            }
            const params = new URLSearchParams(paramsObj);

            const cargoVal = paramsObj.cargo;
            const candidatoVal = paramsObj.candidato;

            showLoading(cargoVal ? 'Carregando ' + cargoVal + '...' : 'Carregando dados...');

            if (!isEleitos && candidatoVal && candidatoVal !== 'Todos') {
                params.append('any', '1');
            }

            try {
                if (cargoVal === 'Governador' && (!candidatoVal || candidatoVal === 'Todos')) {
                    const situacaoParam = isEleitos ? '&situacao=ELEITO' : '';
                    const p1 = fetch('api.php?turno=1&cargo=' + encodeURIComponent(cargoVal) + situacaoParam).then(r => r.json());
                    const p2 = fetch('api.php?turno=2&cargo=' + encodeURIComponent(cargoVal) + situacaoParam).then(r => r.json());
                    const [data1, data2] = await Promise.all([p1, p2]);

                    updateStats(data1.stats || {});
                    updateModePanel(data1.ui || {}, data1.modeHighlights || []);
                    updateCandidatosTable(data1.candidatos || [], data1.candidateInsights || []);
                    updateCandidateInsights(data1.candidateInsights || [], data1.candidatosGovernador || []);
                    updateGovernorRace(data1.candidatosGovernador || []);

                    renderGovernorComparative(data1, data2);
                    updateDetalheCandidato(null, []);
                    updateVotosPorRegiao([]);
                    
                    if (document.getElementById('municipiosStatsSection')) {
                        document.getElementById('municipiosStatsSection').style.display = 'none';
                    }
                    hideLoading();
                    return;
                }

                if (document.getElementById('municipiosStatsSection')) {
                    document.getElementById('municipiosStatsSection').style.display = 'block';
                }

                const response = await fetch('api.php?' + params.toString());
                if (!response.ok) {
                    throw new Error('Falha HTTP ' + response.status);
                }

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.message || 'Erro ao consultar API');
                }

                updateStats(data.stats || {});
                updateModePanel(data.ui || {}, data.modeHighlights || []);
                updateCandidatosTable(data.candidatos || [], data.candidateInsights || []);
                updateMunicipios(data.municipios || []);
                updateCandidateInsights(data.candidateInsights || [], data.candidatosGovernador || []);
                updateGovernorRace(data.detalheCandidato ? [] : (data.candidatosGovernador || []));
                updateVotosPorRegiao(
                    data.detalheCandidato ? (data.votosPorRegiaoCandidato || []) : (data.votosPorRegiao || []),
                    data.detalheCandidato
                );
                updateDetalheCandidato(data.detalheCandidato, data.votosPorMunicipio || []);

                hideLoading();
            } catch (error) {
                hideLoading();
                console.error(error);
                updateStats({});
                updateModePanel({}, []);
                updateCandidatosTable([]);
                updateMunicipios([]);
                updateCandidateInsights([], []);
                updateGovernorRace([]);
                updateDetalheCandidato(null, []);
                updateVotosPorRegiao([]);
            }
        }

        // Inicializar - carregar dados do Geral ao abrir a página
        loadPartidos();
        loadData();
    </script>
</body>
</html>
