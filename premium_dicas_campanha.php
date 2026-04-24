<?php
declare(strict_types=1);

require_once __DIR__ . '/premium_advisor_helpers.php';

$user = premium_require_user($conn);
$csrf = premium_csrf_token();
$flash = premium_pull_flash();

$trialDaysRemaining = premium_trial_days_remaining($user);
if ($trialDaysRemaining !== null) {
    premium_push_flash('Esta página é exclusiva para usuários que adquiriram o acesso ao sistema completo.', 'error');
    header('Location: premium');
    exit;
}

if (isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'])) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaign = premium_active_campaign($conn, (int) $user['id']);

$campaignTitle = $campaign
    ? trim((string) ($campaign['campaign_name'] ?? 'Campanha') . ' - ' . (string) ($campaign['candidate_name'] ?? ''))
    : 'Nenhuma campanha ativa';

$cargo = strtolower(trim((string)($campaign['candidate_cargo'] ?? '')));
$tips = [];

if (str_contains($cargo, 'estadual')) {
    $tips = [
        'cargo_label' => 'Deputado Estadual',
        'mensagem' => [
            'O foco é ser a voz de um microterritório. O número deve ser tratado como marca registrada.',
            'Utilizar prova social local (UGC - User Generated Content) com depoimentos de líderes de bairros.',
            'Focar na "pauta local estadualizada", resolvendo problemas concretos da comunidade e mostrando como o estado pode ajudar.'
        ],
        'liderancas' => [
            'Utilizar dados eleitorais passados como "moeda de troca" (Inteligência de Dados) para mostrar aos vereadores onde estão os votos.',
            'Garantir que prefeitos e vereadores parceiros se sintam coautores e não apenas "cabos eleitorais".'
        ],
        'equipe' => [
            'Ciência do Pertencimento e Gamificação. Criar núcleos por município ou bairro.',
            'Estabelecer metas claras e recompensas simbólicas (ex: jantares, viagens, sorteios de brindes).'
        ],
        'digital' => [
            'Forte uso do WhatsApp territorializado (líderes de grupos).',
            'TikTok e Instagram focados no trabalho corpo-a-corpo e prestação de contas hiperlocal.'
        ]
    ];
} elseif (str_contains($cargo, 'federal')) {
    $tips = [
        'cargo_label' => 'Deputado Federal',
        'mensagem' => [
            'Posicionar-se como a "ponte de recursos em Brasília". Mostrar o efeito visível de emendas (ex: "Com minha emenda, o hospital compra o ultrassom").',
            'Conectar os problemas locais e as pautas federais, mostrando que sua atuação destrava o município.'
        ],
        'liderancas' => [
            'Pacto de emendas futuras: O candidato deve oferecer suporte técnico às lideranças municipais.',
            'Engajar prefeitos e candidatos a prefeito de 2028 mostrando que sua eleição é garantia de recursos para a cidade deles.'
        ],
        'equipe' => [
            'Microtreinamento em "linguagem de emenda" para que os cabos eleitorais saibam explicar exatamente a função do cargo e não pareçam fazer falsas promessas.'
        ],
        'digital' => [
            'YouTube e podcasts para aprofundar temas ou prestar contas de mandatos anteriores.',
            'WhatsApp segmentado por município e anúncios geolocalizados focando na demanda daquela cidade específica.'
        ]
    ];
} elseif (str_contains($cargo, 'senador')) {
    $tips = [
        'cargo_label' => 'Senador',
        'mensagem' => [
            'Imagem de "Âncora de Estabilidade", autoridade, preparo e confiança.',
            'Mostrar ser o guardião do estado perante a união. Em cenário polarizado, usar pesquisa para estimular o voto útil e ser a 3ª via pacífica ou o pilar de uma das forças.'
        ],
        'liderancas' => [
            'Moeda de troca forte com prefeitos de grandes cidades, ex-governadores e presidentes de partidos.',
            'Criação de um "Conselho de Notáveis" (ex-prefeitos, empresários de peso) para validar a candidatura.'
        ],
        'equipe' => [
            'Profissionalização máxima da equipe central e coordenações regionais autônomas.',
            'A equipe deve ter senso de missão histórica (a eleição de um senador muda a configuração do estado por 8 anos).'
        ],
        'digital' => [
            'Mídia estadual (TV/Rádio) é primária e muito importante.',
            'Redes sociais com tom de estadista, focadas em estabilidade.',
            'Unidade de resposta rápida estruturada contra desinformação, dada a alta exposição do cargo.'
        ]
    ];
} elseif (str_contains($cargo, 'governador')) {
    $tips = [
        'cargo_label' => 'Governador',
        'mensagem' => [
            'Gestão, liderança e capacidade de cuidar de todo o estado.',
            'Efeito de contraste com o adversário no 1º turno (nós x eles) e forte apelo ao voto útil e à estabilidade no 2º turno.',
            'Apresentar um plano de governo altamente regionalizado, mostrando que conhece o interior.'
        ],
        'liderancas' => [
            'Coautoria do plano de governo: cada liderança relevante (prefeitos) escreve ou opina no capítulo da sua região (gatilho de propriedade).',
            'Garantia de espaço (participação) para as lideranças na estrutura futura do estado, baseada no desempenho na eleição.'
        ],
        'equipe' => [
            'Dividir o estado em polos (ex: Marechais de Campanha).',
            'Microtreinos de resiliência política e resposta a crises para a equipe central suportar ataques sem perder o foco nas propostas.'
        ],
        'digital' => [
            'Campanhas pagas fortemente segmentadas por mesorregião e pesquisas contínuas de sentimento online.',
            'Uso de IA para escalar as respostas e monitorar as redes 24 horas por dia.'
        ]
    ];
} else {
    $tips = [
        'cargo_label' => 'Campanha Geral',
        'mensagem' => [
            'Adapte sua mensagem à realidade do seu município. Fale diretamente sobre as dores da população.',
            'Use storytelling para construir uma narrativa coerente sobre a sua trajetória e o motivo da candidatura.'
        ],
        'liderancas' => [
            'Construa relações de confiança e apoio mútuo com outras lideranças.',
            'Mostre como sua candidatura beneficia diretamente o grupo político.'
        ],
        'equipe' => [
            'Valorize sua equipe de campo e crie um senso de pertencimento e propósito.',
            'Capacite a linha de frente para que eles saibam responder perguntas básicas sobre a campanha.'
        ],
        'digital' => [
            'Use as redes sociais de forma autêntica. Evite publicações frias.',
            'Crie grupos segmentados no WhatsApp para garantir o alcance rápido da sua mensagem.'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="assets/js/premium-bootstrap.js"></script>
    <title>Estratégias de Campanha | Apoia Candidato</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/premium.css">
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Apoia Candidato Premium</div>
            <h1>Estratégias de campanha</h1>
            <p class="muted">Recomendações estratégicas para a sua candidatura de <?= premium_escape_html($tips['cargo_label']) ?> baseadas em inteligência de dados.</p>
        </div>
        <div class="topbar-right">
            <div class="topbar-actions">
                <div class="theme-switch" role="group" aria-label="Escolher tema">
                    <button type="button" class="theme-switch__btn" data-theme-toggle="light" aria-label="Modo claro" title="Modo claro">&#9728;</button>
                    <button type="button" class="theme-switch__btn" data-theme-toggle="dark" aria-label="Modo escuro" title="Modo escuro">&#9790;</button>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="pill">Olá, <?= premium_escape_html((string) ($user['name'] ?? '')) ?></div>
                <a class="btn comparison-cta" href="premium">Voltar ao painel</a>
                <a class="btn ghost" href="premium_logout.php">Sair</a>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash <?= premium_escape_html((string) ($flash['type'] ?? '')) ?>">
            <?= premium_escape_html((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!$campaign): ?>
        <section class="panel">
            <div class="eyebrow">Sem campanha</div>
            <h2 style="margin-top:12px;">Crie ou selecione uma campanha para visualizar as estratégias.</h2>
            <p class="muted" style="margin-top:12px;">As dicas são personalizadas de acordo com o cargo disputado na campanha.</p>
            <div class="action-row">
                <a class="btn primary" href="premium">Voltar ao Premium</a>
            </div>
        </section>
    <?php else: ?>
        <section class="panel hero hero--single advisor-hero">
            <div class="copy">
                <div class="eyebrow">Plano Estratégico</div>
                <h2 style="font-size:2rem; margin-top:12px;">Cargo: <?= premium_escape_html($tips['cargo_label']) ?></h2>
                <p class="muted" style="margin-top:12px;">
                    Estas estratégias foram formuladas utilizando inteligência artificial baseada nas melhores práticas das eleições proporcionais e majoritárias. Integre estas ações no seu dia a dia para aumentar o engajamento e a conversão de votos.
                </p>
            </div>
        </section>

        <section class="grid-2 advisor-split" style="margin-top: 24px;">
            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Narrativa</div>
                        <h2>Mensagem e Influência</h2>
                    </div>
                </div>
                <div class="advisor-list">
                    <?php foreach ($tips['mensagem'] as $item): ?>
                        <article class="advisor-list-item">
                            <p style="margin:0;"><?= premium_escape_html($item) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Articulação</div>
                        <h2>Engajamento de Lideranças</h2>
                    </div>
                </div>
                <div class="advisor-list">
                    <?php foreach ($tips['liderancas'] as $item): ?>
                        <article class="advisor-list-item">
                            <p style="margin:0;"><?= premium_escape_html($item) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="grid-2 advisor-split" style="margin-top: 24px;">
            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Organização</div>
                        <h2>Motivação da Equipe</h2>
                    </div>
                </div>
                <div class="advisor-list">
                    <?php foreach ($tips['equipe'] as $item): ?>
                        <article class="advisor-list-item">
                            <p style="margin:0;"><?= premium_escape_html($item) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Comunicação</div>
                        <h2>Estratégia Digital</h2>
                    </div>
                </div>
                <div class="advisor-list">
                    <?php foreach ($tips['digital'] as $item): ?>
                        <article class="advisor-list-item">
                            <p style="margin:0;"><?= premium_escape_html($item) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <section class="panel" style="margin-top: 24px;">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Próximos passos</div>
                    <h2>Aplicação Prática</h2>
                </div>
            </div>
            <p class="panel-note">Recomendamos compartilhar as estratégias acima com a sua coordenação de campanha e alinhá-las à agenda de tarefas do sistema. Use os dados de liderança para validar quais regiões precisam de reforço nessas mensagens.</p>
            <div class="action-row">
                <a class="btn primary" href="premium?tab=agenda<?= $campaign ? '&campaign_id=' . (int)$campaign['id'] : '' ?>">Ir para a Agenda</a>
                <a class="btn ghost" href="premium_conselheiro.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Verificar Conselheiro</a>
            </div>
        </section>

    <?php endif; ?>
</div>
<script src="assets/js/premium.js"></script>
</body>
</html>
