<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

$pageTitle = 'Apoia Candidato | Inteligência eleitoral para campanhas';
$pageDescription = 'Transforme dados de 2022, lideranças de 2024 e força territorial em estratégia de campanha. Solicite uma apresentação do Apoia Candidato.';

$user = premium_current_user($conn);
$flash = premium_pull_flash();
$premiumWhatsappPhone = '5579999248114';
$premiumWhatsappMessage = 'Olá! Vim pelo Apoia Candidato Premium e quero agendar uma apresentação para entender como o sistema pode ajudar minha campanha com projeções, lideranças, agenda e relatórios.';
$premiumWhatsappUrl = 'https://wa.me/' . $premiumWhatsappPhone . '?text=' . rawurlencode($premiumWhatsappMessage);

$featureCards = [
    [
        'eyebrow' => 'Mapa completo',
        'title' => 'Dados em um só lugar',
        'text' => 'Veja candidatos a prefeito e vereador, votos por cidade, recortes por região e leitura por estado sem depender de planilhas soltas.',
    ],
    [
        'eyebrow' => 'Lideranças',
        'title' => 'Quem realmente entrega',
        'text' => 'Compare a força das lideranças, o potencial de transferência e o peso político de cada território para não tratar todo apoio como igual.',
    ],
    [
        'eyebrow' => 'Defesa de base',
        'title' => 'Onde sua campanha está em risco',
        'text' => 'Identifique cidades fortes que perderam sustentação e onde a oposição pode ocupar espaço se a equipe não agir cedo.',
    ],
    [
        'eyebrow' => 'Expansão',
        'title' => 'Onde existe oportunidade real',
        'text' => 'Encontre buracos eleitorais, cidades com potencial de crescimento e áreas onde a agenda pode render mais resultado.',
    ],
    [
        'eyebrow' => 'Conselheiro',
        'title' => 'Prioridade e rentabilidade',
        'text' => 'O módulo Conselheiro indica quais cidades merecem atenção primeiro, quais têm melhor retorno e quais precisam de defesa urgente.',
    ],
    [
        'eyebrow' => 'Cenários',
        'title' => 'Conservador, base e otimista',
        'text' => 'Trabalhe com leitura realista da campanha, sem prometer vitória e sem depender de palpite solto para decidir o próximo passo.',
    ],
];

$audienceCards = [
    [
        'title' => 'Deputado estadual',
        'text' => 'Mapeia redutos, lideranças municipais, cidades prioritárias e defesa de base no interior.',
    ],
    [
        'title' => 'Deputado federal',
        'text' => 'Ajuda a combinar força territorial, expansão regional e eficiência entre esforço e voto.',
    ],
    [
        'title' => 'Senador',
        'text' => 'Entrega visão estadual para comparar regiões, alianças e mobilização territorial.',
    ],
    [
        'title' => 'Governador',
        'text' => 'Mostra base em risco, apoio municipal, lideranças estratégicas e agenda prioritária por região.',
    ],
];

$faqItems = [
    [
        'question' => 'Como solicito uma apresentação?',
        'answer' => 'Clique no botão de WhatsApp, envie a mensagem pronta e combine o melhor horário para ver o sistema aplicado à rotina da sua campanha.',
    ],
    [
        'question' => 'O que vou ver na apresentação?',
        'answer' => 'Você verá como o Apoia Candidato organiza dados eleitorais, lideranças, projeções, agenda e relatórios para orientar prioridades de campanha.',
    ],
    [
        'question' => 'O sistema promete vitória?',
        'answer' => 'Não. Ele organiza dados, cenários e prioridades para a equipe decidir melhor e trabalhar com mais inteligência.',
    ],
    [
        'question' => 'Quem pode usar?',
        'answer' => 'Candidatos, coordenadores, assessores, partidos e equipes que precisam enxergar o território com mais clareza.',
    ],
];

$trialHighlights = [
    'Dados de prefeito e vereador em um só lugar',
    'Quem foi mais votado por cidade, região e estado',
    'Base em risco, buracos eleitorais e expansão',
    'Conselheiro de campanha com prioridade e rentabilidade',
];

$heroPreviewItems = [
    [
        'label' => 'Prioridade alta',
        'text' => 'Cidades com liderança ativa, projeção forte e resposta rápida da campanha.',
    ],
    [
        'label' => 'Bases em risco',
        'text' => 'Territórios que tiveram voto histórico, mas hoje precisam de defesa e articulação.',
    ],
    [
        'label' => 'Alta rentabilidade',
        'text' => 'Municípios com melhor relação entre esforço estimado e potencial de voto.',
    ],
    [
        'label' => 'Oportunidades',
        'text' => 'Cidades que podem virar entrada nova para expansão territorial da campanha.',
    ],
];

$accessTitle = 'Solicite uma apresentação';
$accessSubtitle = 'Fale pelo WhatsApp e veja, em poucos minutos, como o Apoia Candidato pode transformar dados eleitorais em decisões práticas para sua campanha.';
if ($user) {
    $accessTitle = 'Seu acesso está ativo';
    $accessSubtitle = 'Sua conta já está liberada para uso.';
}
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= premium_escape_html($pageDescription) ?>">
    <meta name="color-scheme" content="dark light">
    <title><?= premium_escape_html($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #07111d;
            --bg-2: #0c1b2f;
            --card: rgba(8, 17, 29, 0.76);
            --card-strong: rgba(13, 24, 41, 0.92);
            --line: rgba(156, 189, 255, 0.14);
            --line-strong: rgba(156, 189, 255, 0.24);
            --text: #edf3fb;
            --muted: rgba(226, 234, 247, 0.74);
            --soft: rgba(255, 255, 255, 0.06);
            --accent: #4da3ff;
            --accent-2: #7cf0d6;
            --accent-3: #ff8a5b;
            --danger: #ff6d6d;
            --shadow: 0 26px 80px rgba(0, 0, 0, 0.38);
            --radius: 24px;
            --radius-lg: 32px;
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(77, 163, 255, 0.22), transparent 30%),
                radial-gradient(circle at top right, rgba(124, 240, 214, 0.16), transparent 26%),
                radial-gradient(circle at bottom left, rgba(255, 138, 91, 0.16), transparent 24%),
                linear-gradient(180deg, var(--bg) 0%, #091321 46%, #050b12 100%);
            color: var(--text);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.65), transparent 82%);
            opacity: 0.35;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .landing-shell {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 22px 0 64px;
        }

        .landing-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 20px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(7, 14, 24, 0.72);
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
            position: sticky;
            top: 16px;
            z-index: 10;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #04111c;
            font-weight: 800;
            box-shadow: 0 12px 30px rgba(77, 163, 255, 0.28);
        }

        .brand-copy {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .brand-copy strong {
            font-size: 1rem;
        }

        .brand-copy span {
            color: var(--muted);
            font-size: 0.78rem;
            margin-top: 4px;
        }

        .header-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .header-nav a {
            transition: color 0.2s ease;
        }

        .header-nav a:hover {
            color: var(--text);
        }

        .header-actions,
        .hero-actions,
        .cta-row,
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn,
        .btn-secondary,
        .btn-ghost {
            border: 0;
            border-radius: 999px;
            padding: 13px 20px;
            font-weight: 700;
            letter-spacing: -0.01em;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, color 0.18s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn:hover,
        .btn-secondary:hover,
        .btn-ghost:hover {
            transform: translateY(-1px);
        }

        .btn {
            background: linear-gradient(135deg, var(--accent), #7a72ff);
            color: white;
            box-shadow: 0 18px 34px rgba(77, 163, 255, 0.24);
        }

        .btn-whatsapp {
            background: #25d366;
            color: #052e18;
            box-shadow: 0 18px 34px rgba(37, 211, 102, 0.22);
        }

        .btn-whatsapp:hover {
            background: #22c55e;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text);
            border: 1px solid var(--line);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--line);
        }

        .btn-access-outline {
            border: 2px solid var(--accent-2);
            box-shadow: 0 0 0 4px rgba(124, 240, 214, 0.10), 0 14px 28px rgba(124, 240, 214, 0.14);
        }

        .btn-access-outline:hover {
            border-color: #ffffff;
            box-shadow: 0 0 0 5px rgba(124, 240, 214, 0.16), 0 18px 34px rgba(124, 240, 214, 0.22);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
            gap: 28px;
            padding: 34px 0 24px;
            align-items: start;
        }

        .hero-copy {
            padding: 18px 4px 0 4px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--accent-2);
            font-size: 0.76rem;
            font-weight: 800;
        }

        .hero-title,
        h2,
        h3,
        h4 {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.04em;
        }

        .hero-title {
            font-size: clamp(2.25rem, 5.2vw, 4.4rem);
            line-height: 0.92;
            margin: 14px 0 14px;
            max-width: 15ch;
        }

        .hero-title-brand {
            display: block;
            margin-bottom: 0.2em;
            color: var(--accent-2);
            font-size: 0.42em;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .hero-title-copy {
            display: block;
        }

        .hero-lead {
            max-width: 64ch;
            color: var(--muted);
            font-size: 1.08rem;
            line-height: 1.85;
            margin: 0 0 24px;
        }

        .hero-proof {
            max-width: 64ch;
            margin: 0 0 18px;
            padding: 18px 20px;
            border: 1px solid rgba(124, 240, 214, 0.18);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(124, 240, 214, 0.12), rgba(14, 20, 35, 0.76));
            color: var(--text);
            font-size: 1.15rem;
            line-height: 1.75;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.14);
        }

        .hero-proof strong {
            color: var(--accent-2);
        }

        .hero-points {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 18px;
            margin: 24px 0 26px;
            padding: 0;
            list-style: none;
        }

        .hero-points li {
            position: relative;
            padding-left: 20px;
            color: var(--text);
            line-height: 1.45;
        }

        .hero-points li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.6em;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            box-shadow: 0 0 0 4px rgba(124, 240, 214, 0.12);
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .hero-badges span,
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.94rem;
        }

        .hero-quote {
            margin: 0;
            padding: 18px 18px 18px 20px;
            border-left: 4px solid var(--accent);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            max-width: 62ch;
            line-height: 1.7;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        .hero-panel,
        .section-card,
        .feature-card,
        .audience-card,
        .faq-card,
        .trial-form-card,
        .access-card,
        .quote-card {
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(12, 24, 40, 0.86), rgba(7, 16, 28, 0.92));
            box-shadow: var(--shadow);
        }

        .hero-panel {
            padding: 22px;
            position: sticky;
            top: 106px;
        }

        .hero-preview {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .hero-preview-item {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--line);
        }

        .hero-preview-item strong {
            display: block;
            font-size: 0.92rem;
            margin-bottom: 8px;
            color: var(--accent-2);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-preview-item span {
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.95rem;
        }

        .section {
            padding: 42px 0 10px;
        }

        .section-head {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 22px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
        }

        .section-head p {
            margin: 0;
            color: var(--muted);
            max-width: 70ch;
            line-height: 1.8;
        }

        .feature-grid,
        .audience-grid,
        .faq-grid {
            display: grid;
            gap: 16px;
        }

        .feature-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .feature-card,
        .audience-card,
        .faq-card {
            padding: 20px;
        }

        .feature-card h3,
        .audience-card h3,
        .faq-card h3,
        .trial-form-card h3,
        .access-card h3,
        .quote-card h3 {
            margin: 6px 0 10px;
            font-size: 1.3rem;
        }

        .feature-card p,
        .audience-card p,
        .faq-card p,
        .trial-form-card p,
        .access-card p,
        .quote-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
        }

        .split-grid,
        .trial-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .section-card {
            padding: 24px;
        }

        .section-card h2 {
            margin: 8px 0 12px;
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            line-height: 1.05;
        }

        .audience-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 18px;
        }

        .audience-card {
            min-height: 100%;
        }

        .quote-card {
            padding: 22px;
            display: grid;
            gap: 14px;
        }

        .quote-card p {
            color: var(--text);
            font-size: 1.02rem;
        }

        .steps {
            display: grid;
            gap: 14px;
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
            counter-reset: step;
        }

        .steps li {
            position: relative;
            padding: 18px 18px 18px 58px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--line);
            line-height: 1.7;
            color: var(--muted);
        }

        .steps li::before {
            counter-increment: step;
            content: counter(step);
            position: absolute;
            left: 16px;
            top: 16px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #04111c;
            font-weight: 800;
        }

        .trial-section {
            align-items: start;
            margin-top: 12px;
        }

        .trial-copy {
            padding: 8px 6px 0 6px;
        }

        .trial-copy h2 {
            margin: 12px 0 14px;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
        }

        .trial-list {
            display: grid;
            gap: 10px;
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
        }

        .trial-list li {
            position: relative;
            padding-left: 20px;
            color: var(--text);
            line-height: 1.5;
        }

        .trial-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.65em;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent-3);
        }

        .trial-form-card,
        .access-card {
            padding: 22px;
            background: linear-gradient(180deg, rgba(10, 22, 37, 0.92), rgba(5, 12, 20, 0.96));
        }

        .trial-form-card form {
            margin-top: 18px;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        .field label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: var(--muted);
        }

        .field input {
            width: 100%;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            padding: 14px 15px;
            font: inherit;
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .field input:focus {
            border-color: rgba(77, 163, 255, 0.72);
            box-shadow: 0 0 0 4px rgba(77, 163, 255, 0.14);
            background: rgba(255, 255, 255, 0.08);
        }

        .field input::placeholder {
            color: rgba(226, 234, 247, 0.42);
        }

        .help-text {
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .flash {
            margin: 22px 0 0;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: var(--shadow);
        }

        .flash.error {
            border-color: rgba(255, 109, 109, 0.4);
            background: rgba(255, 109, 109, 0.12);
        }

        .flash.success {
            border-color: rgba(124, 240, 214, 0.42);
            background: rgba(124, 240, 214, 0.12);
        }

        .flash.warning {
            border-color: rgba(255, 204, 102, 0.42);
            background: rgba(255, 204, 102, 0.12);
        }

        .flash p {
            margin: 0;
            line-height: 1.6;
        }

        .footer {
            padding: 42px 0 28px;
            color: var(--muted);
            text-align: center;
            line-height: 1.7;
        }

        .footer strong {
            color: var(--text);
        }

        @media (max-width: 1080px) {
            .hero,
            .split-grid,
            .trial-section {
                grid-template-columns: 1fr;
            }

            .hero-panel {
                position: static;
            }

            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .landing-header {
                border-radius: 28px;
                align-items: flex-start;
                flex-direction: column;
            }

            .header-nav {
                order: 3;
            }
        }

        @media (max-width: 760px) {
            .landing-shell {
                width: min(100% - 20px, 1180px);
                padding-top: 14px;
            }

            .feature-grid,
            .audience-grid,
            .hero-preview,
            .hero-points {
                grid-template-columns: 1fr;
            }

            .header-actions,
            .hero-actions,
            .cta-row,
            .form-actions {
                width: 100%;
            }

            .header-actions .btn,
            .header-actions .btn-secondary,
            .hero-actions .btn,
            .hero-actions .btn-secondary,
            .cta-row .btn,
            .cta-row .btn-secondary {
                flex: 1 1 100%;
            }

            .header-actions {
                flex-wrap: nowrap;
                gap: 8px;
            }

            .header-actions .btn-ghost {
                flex: 1 1 0;
                min-width: 0;
                padding-inline: 12px;
                white-space: nowrap;
            }

            .header-actions .btn {
                flex: 2.25 1 0;
                min-width: 0;
                padding-inline: 12px;
                white-space: nowrap;
            }

            .hero-title {
                max-width: 100%;
            }
        }
    </style>
</head>
<body id="top">
    <div class="landing-shell">
        <header class="landing-header">
            <a class="brand" href="#top" aria-label="Apoia Candidato">
                <div class="brand-mark">A</div>
                <div class="brand-copy">
                    <strong>Apoia Candidato</strong>
                    <span>Inteligência eleitoral premium</span>
                </div>
            </a>
            <nav class="header-nav" aria-label="Seções da página">
                <a href="#solucao">O sistema</a>
                <a href="#para-quem">Para quem</a>
                <a href="#apresentacao">Apresentação</a>
                <a href="#faq">Perguntas</a>
            </nav>
            <div class="header-actions">
                <a class="btn-ghost btn-access-outline" href="premium">Entrar</a>
                <a class="btn btn-whatsapp" href="<?= premium_escape_html($premiumWhatsappUrl) ?>" target="_blank" rel="noopener">Agendar apresentação</a>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= premium_escape_html((string) ($flash['type'] ?? '')) ?>">
                <p><?= premium_escape_html((string) ($flash['message'] ?? '')) ?></p>
            </div>
        <?php endif; ?>

        <main>
            <section class="hero">
                <div class="hero-copy">
                    <div class="eyebrow">Geografia eleitoral científica</div>
                    <h1 class="hero-title"><span class="hero-title-brand">Apoia Candidato</span><span class="hero-title-copy">Veja onde estão seus votos, quem entrega mais e onde sua campanha precisa agir primeiro.</span></h1>
                    <p class="hero-proof">
                        <strong>Ao visitar uma cidade, a campanha já leva relatórios prévios das lideranças que apoiam o candidato, estatísticas e projeções de votos.</strong>
                        Isso economiza tempo, valoriza cada reunião e coloca a campanha para o <strong>candidato</strong> e assessores na palma da mão.
                    </p>
                    <p class="hero-lead">
                        O Apoia Candidato transforma dados de 2022, lideranças de 2024 e força territorial em uma leitura clara de prioridade, risco e expansão.
                        Você não precisa decidir no escuro. Precisa de um mapa real da disputa.
                    </p>

                    <div class="hero-actions">
                        <a class="btn btn-whatsapp" href="<?= premium_escape_html($premiumWhatsappUrl) ?>" target="_blank" rel="noopener">Solicitar apresentação</a>
                        <a class="btn-secondary btn-access-outline" href="premium">Já tenho acesso</a>
                    </div>

                    <ul class="hero-points" aria-label="Destaques do sistema">
                        <?php foreach ($trialHighlights as $highlight): ?>
                            <li><?= premium_escape_html($highlight) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="hero-badges" aria-label="Resumo da proposta">
                        <span>Campanhas de deputado, senador e governador</span>
                        <span>Conselheiro com prioridade e rentabilidade</span>
                        <span>Sem achismo, sem planilha solta</span>
                    </div>

                    <blockquote class="hero-quote">
                        A campanha não se decide no escuro. Ela começa no diagnóstico correto do território.
                    </blockquote>
                </div>

                <aside class="hero-panel">
                    <div class="hero-preview" aria-label="Prévia do Conselheiro">
                        <?php foreach ($heroPreviewItems as $item): ?>
                            <div class="hero-preview-item">
                                <strong><?= premium_escape_html($item['label']) ?></strong>
                                <span><?= premium_escape_html($item['text']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($user): ?>
                        <div class="access-card">
                            <div class="eyebrow">Acesso ativo</div>
                            <h3><?= premium_escape_html((string) ($user['name'] ?? 'Sua conta')) ?></h3>
                            <p><?= premium_escape_html($accessSubtitle) ?></p>
                            <div class="cta-row" style="margin-top:18px;">
                                <a class="btn" href="premium">Abrir o sistema</a>
                                <a class="btn-secondary" href="#solucao">Ver recursos</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="trial-form-card">
                            <div class="eyebrow"><?= premium_escape_html($accessTitle) ?></div>
                            <h3><?= premium_escape_html($accessTitle) ?></h3>
                            <p><?= premium_escape_html($accessSubtitle) ?></p>
                            <div class="form-actions" style="margin-top:18px;">
                                <a class="btn btn-whatsapp" href="<?= premium_escape_html($premiumWhatsappUrl) ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a>
                                <a class="btn-secondary btn-access-outline" href="premium">Já tenho acesso</a>
                            </div>
                            <p class="help-text">Mensagem pronta para o contato: agendar uma apresentação e conhecer melhor o sistema.</p>
                        </div>
                    <?php endif; ?>
                </aside>
            </section>

            <section class="section" id="solucao">
                <div class="section-head">
                    <div class="eyebrow">O sistema</div>
                    <h2>Uma plataforma para ver o território inteiro</h2>
                    <p>
                        O Premium cruza histórico eleitoral, lideranças cadastradas, projeção de transferência e força regional para destacar onde a campanha deve defender, expandir e priorizar esforços.
                    </p>
                </div>

                <div class="feature-grid">
                    <?php foreach ($featureCards as $card): ?>
                        <article class="feature-card">
                            <div class="eyebrow"><?= premium_escape_html($card['eyebrow']) ?></div>
                            <h3><?= premium_escape_html($card['title']) ?></h3>
                            <p><?= premium_escape_html($card['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section split-grid" id="para-quem">
                <article class="section-card">
                    <div class="eyebrow">Para quem é</div>
                    <h2>Feito para campanhas que precisam priorizar</h2>
                    <p>
                        O sistema serve para coordenadores, equipes estratégicas e candidatos que precisam organizar a leitura territorial antes de gastar tempo, agenda e estrutura.
                    </p>

                    <div class="audience-grid">
                        <?php foreach ($audienceCards as $audience): ?>
                            <article class="audience-card">
                                <h3><?= premium_escape_html($audience['title']) ?></h3>
                                <p><?= premium_escape_html($audience['text']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="quote-card">
                    <div class="eyebrow">Como funciona</div>
                    <h3>Apresentação em três passos</h3>
                    <ol class="steps">
                        <li>Chame pelo WhatsApp e conte rapidamente o perfil da campanha.</li>
                        <li>Veja como dados, lideranças, cenários, agenda e Conselheiro funcionam dentro do sistema.</li>
                        <li>Entenda como usar o diagnóstico para defender base, ampliar território e tomar decisão com mais método.</li>
                    </ol>
                    <p>A conversa é direta: mostrar valor, tirar dúvidas e indicar o melhor caminho para sua equipe.</p>
                </article>
            </section>

            <section class="section">
                <div class="section-head">
                    <div class="eyebrow">Por que vende</div>
                    <h2>Porque campanha precisa de resposta, não de ruído</h2>
                    <p>
                        Você já sabe que precisa de votos. O sistema mostra onde buscar, quem priorizar e onde sua equipe está perdendo território.
                    </p>
                </div>

                <div class="quote-card">
                    <p>
                        O Apoia Candidato organiza dados de 2022 e 2024, aplica fatores de transferência e destaca cidades com alta rentabilidade política,
                        bases em risco e oportunidades de expansão.
                    </p>
                    <p>
                        Em vez de prometer vitória, a plataforma entrega prioridade, leitura territorial e um conselheiro que transforma dados em estratégia.
                    </p>
                </div>
            </section>

            <section class="section trial-section" id="apresentacao">
                <article class="trial-copy">
                    <div class="eyebrow">Apresentação do sistema</div>
                    <h2>Veja como sua campanha pode decidir com dados antes de gastar agenda e energia.</h2>
                    <p class="hero-lead" style="margin-bottom: 0;">
                        Solicite uma apresentação pelo WhatsApp e conheça o Apoia Candidato em funcionamento. A ideia é mostrar, de forma objetiva, como a leitura territorial pode orientar prioridades reais da campanha.
                    </p>
                    <ul class="trial-list">
                        <li>Dados de candidatos a prefeito e vereador.</li>
                        <li>Quem foi mais votado por cidade, região e estado.</li>
                        <li>Base em risco, buracos eleitorais e expansão territorial.</li>
                        <li>Conselheiro com prioridade, defesa e rentabilidade política.</li>
                    </ul>
                </article>

                <aside class="trial-form-card" id="trial">
                    <div class="eyebrow"><?= premium_escape_html($accessTitle) ?></div>
                    <h3><?= premium_escape_html($accessTitle) ?></h3>
                    <p><?= premium_escape_html($accessSubtitle) ?></p>

                    <?php if (!$user): ?>
                        <div class="form-actions" style="margin-top:18px;">
                            <a class="btn btn-whatsapp" href="<?= premium_escape_html($premiumWhatsappUrl) ?>" target="_blank" rel="noopener">Solicitar pelo WhatsApp</a>
                            <a class="btn-secondary btn-access-outline" href="premium">Já tenho acesso</a>
                        </div>
                        <p class="help-text">
                            Atendimento pelo WhatsApp: A mensagem já vai pronta para agendar a apresentação.
                        </p>
                    <?php else: ?>
                        <div class="access-card" style="margin-top:18px;">
                            <div class="pill" style="display:inline-flex;">Conta ativa</div>
                            <p style="margin-top:14px;">
                                <?= premium_escape_html((string) ($user['name'] ?? 'Sua conta')) ?> já está liberada.
                            </p>
                            <p style="margin-top:10px;">
                                Seu acesso está ativo.
                            </p>
                            <div class="cta-row" style="margin-top:18px;">
                                <a class="btn" href="premium">Abrir o sistema</a>
                                <a class="btn-secondary" href="#solucao">Ver recursos</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </section>

            <section class="section" id="faq">
                <div class="section-head">
                    <div class="eyebrow">Perguntas frequentes</div>
                    <h2>As dúvidas mais comuns antes da apresentação</h2>
                </div>

                <div class="faq-grid">
                    <?php foreach ($faqItems as $item): ?>
                        <article class="faq-card">
                            <h3><?= premium_escape_html($item['question']) ?></h3>
                            <p><?= premium_escape_html($item['answer']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <footer class="footer">
            <strong>Apoia Candidato</strong><br>
            Inteligência eleitoral para campanhas que querem decidir com método, território e dados.
        </footer>
    </div>
</body>
</html>
