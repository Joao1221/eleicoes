<?php
declare(strict_types=1);

require_once __DIR__ . '/premium_advisor_helpers.php';

$user = premium_require_user($conn);
$isAdmin = premium_is_admin_user($user);
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
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'], $isAdmin)) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);

$campaignTitle = $campaign
    ? trim((string) ($campaign['campaign_name'] ?? 'Campanha') . ' - ' . (string) ($campaign['candidate_name'] ?? ''))
    : 'Nenhuma campanha ativa';
$premiumSupportWhatsappUrl = premium_vip_support_whatsapp_url($user, $campaign);

$cargo = strtolower(trim((string)($campaign['candidate_cargo'] ?? '')));
$isDeputadoEstadual = str_contains($cargo, 'estadual');
$isDeputadoFederal = str_contains($cargo, 'federal');
$isSenador = str_contains($cargo, 'senador');
$isGovernador = str_contains($cargo, 'governador');
$hasStrategyContent = $isDeputadoEstadual || $isDeputadoFederal || $isSenador || $isGovernador;

$cargoProfile = [
    'cargo_label' => 'Campanha Geral',
    'tagline' => 'Organização, presença territorial e narrativa simples para transformar informação em voto.',
    'influencia' => [
        'Escolha uma causa reconhecível e transforme o candidato na resposta mais concreta para aquela dor.',
        'Crie mensagens por território: o bairro, a rua e a comunidade precisam se ver no material da campanha.',
        'Use WhatsApp segmentado, depoimentos reais e anúncios geolocalizados para reduzir desperdício de energia.'
    ],
    'equipe' => [
        'Crie núcleos por território com metas semanais de visitas, cadastros e conversas qualificadas.',
        'Mostre o impacto de cada tarefa para que a equipe entenda que não está apenas panfletando, está construindo força política.',
        'Reconheça publicamente quem entrega resultado e treine a linha de frente para responder com segurança.'
    ],
    'liderancas' => [
        'Dê protagonismo real às lideranças locais: escuta, material personalizado, metas conjuntas e retorno sobre demandas.',
        'Use dados eleitorais de 2022 e 2024 para mostrar onde existe voto disponível, rejeição, crescimento e oportunidade.',
        'Conecte o apoio de agora ao projeto político de 2028 e 2030, sempre com compromissos responsáveis.'
    ],
    'metricas' => [
        'Apoiadores cadastrados por território',
        'Lideranças ativas e inativas',
        'Reuniões realizadas por semana',
        'Crescimento digital por região'
    ]
];

if ($isDeputadoEstadual) {
    $cargoProfile = [
        'cargo_label' => 'Deputado Estadual',
        'tagline' => 'A campanha precisa vender proximidade, defesa da cidade e representação regional na Assembleia.',
        'influencia' => [
            'Trate o número como marca registrada: repetição em jingle, camisa, card, fala de rua e vídeo curto.',
            'Domine um microterritório antes de tentar falar com todo o estado. A meta é ser lembrado como "o deputado daqui".',
            'Transforme a pauta local em pauta estadual: estrada, escola, segurança, abastecimento, saúde regional e fiscalização do governo.',
            'Use prova social local com líderes de bairro, associações e moradores gravando vídeos simples de apoio.'
        ],
        'equipe' => [
            'Crie núcleos por município, bairro ou povoado, cada um com coordenador, meta e liberdade para executar.',
            'Dê contexto: "Cadastrar 50 apoiadores nesse bairro fortalece nossa zona eleitoral". Quem entende o impacto trabalha com mais convicção.',
            'Use ranking saudável de visitas, novos apoiadores e reuniões feitas, com reconhecimento simbólico em encontros internos.',
            'Treine a equipe para explicar, em 30 segundos, o que um deputado estadual pode fazer sem prometer o que não depende dele.'
        ],
        'liderancas' => [
            'Apresente dados eleitorais como moeda de troca: onde estão os votos, quais bairros têm espaço e onde o adversário está forte.',
            'Crie compromissos locais coassinados com a liderança, como defesa de uma obra, serviço ou prioridade da comunidade.',
            'Dê à liderança o papel de embaixadora daquela região, com material personalizado e reconhecimento público.',
            'Mostre que apoiar agora constrói base para 2028, fortalecendo vereadores, prefeitos e grupos locais.'
        ],
        'metricas' => [
            'Meta de votos por município',
            'Bairros e povoados trabalhados',
            'Número de apoiadores cadastrados',
            'Lideranças com meta definida'
        ]
    ];
} elseif ($isDeputadoFederal) {
    $cargoProfile = [
        'cargo_label' => 'Deputado Federal',
        'tagline' => 'A campanha deve provar que o candidato é embaixador de resultados: recursos, leis, causas e presença real em Brasília.',
        'influencia' => [
            'Posicione o candidato como Embaixador de Resultados: quem transforma Brasília em emendas, leis, fiscalização e recursos que chegam ao estado.',
            'Use IA para comparar quanto a região recebeu de recursos federais e quanto poderia ter recebido com representação mais ativa.',
            'Escolha uma bandeira temática clara, como agro, tecnologia, educação, saúde, segurança ou empreendedorismo, e fale com nichos específicos.',
            'Mostre bastidores do poder: votação de leis, cobrança a ministros, articulação de emendas e explicações simples sobre o Congresso.'
        ],
        'equipe' => [
            'Dê à equipe propósito de mudança nacional: ela está ajudando a eleger alguém que muda leis do Brasil, não apenas mais um nome na urna.',
            'Ofereça capacitação profissionalizante em marketing digital, dados, WhatsApp, legislação e linguagem de emendas.',
            'Use o CampanhaInteligente para monitorar metas de contatos, cadastros, reuniões e conversas qualificadas por município.',
            'Reconheça mobilizadores de alta performance nas redes e em eventos, ativando status público e orgulho de pertencimento.'
        ],
        'liderancas' => [
            'Mostre a Chave do Cofre: um deputado federal aliado ajuda a garantir emendas, projetos e recursos que viram legado municipal.',
            'Ofereça suporte jurídico e técnico para ajudar prefeituras e lideranças a formatar projetos capazes de captar verba federal.',
            'Convide lideranças para coautoria temática de propostas de lei, indicações ou projetos regionais que elas possam defender como suas.',
            'Mostre como a força em Brasília vira trampolim político para 2028 e disputas futuras contra adversários locais.'
        ],
        'metricas' => [
            'Recursos e demandas federais mapeados',
            'Nichos temáticos ativados',
            'Prefeitos e vereadores com projeto',
            'Performance por mobilizador'
        ]
    ];
} elseif ($isSenador) {
    $cargoProfile = [
        'cargo_label' => 'Senador',
        'tagline' => 'A campanha precisa vender autoridade, proteção do estado em Brasília, voto casado e legado político de oito anos.',
        'influencia' => [
            'Posicione o candidato como o Escudo do Estado em Brasília: quem protege interesses estaduais, destrava recursos federais e puxa grandes obras.',
            'Comunique autoridade de estadista: serenidade, firmeza e domínio de temas complexos como pacto federativo, reforma tributária e orçamento, sempre em linguagem popular.',
            'Trabalhe a Segunda Opção Estratégica: como há duas vagas para o Senado, busque o voto casado em nichos de outros palanques sem depender de um único grupo.',
            'Crie um Canal da Verdade no WhatsApp e use IA para monitorar deepfakes, ataques reputacionais e narrativas contra a biografia do candidato.'
        ],
        'equipe' => [
            'Faça a equipe sentir orgulho da representatividade: ela não está entregando santinho, está elegendo uma das vozes máximas da honra do estado.',
            'Use rituais de posse simbólica para líderes regionais, com título, missão, metas e reconhecimento público.',
            'Compartilhe a visão de futuro: um mandato de oito anos pode produzir mudanças estruturais que filhos e netos verão.',
            'Promova encontros regionais de Escuta Estratégica para que a ponta da campanha seja ouvida pelo candidato e não se sinta apenas um número.'
        ],
        'liderancas' => [
            'Mostre o senador como padrinho das emendas impositivas: prefeitos e vereadores precisam enxergar um guichê aberto para demandas municipais.',
            'Use a candidatura majoritária para mediar conflitos locais e formar coalizões interpartidárias que lideranças menores não conseguiriam construir sozinhas.',
            'Ofereça suporte VIP de gente grande: jurídico, dados, leitura de cenário e estrutura do CampanhaInteligente para lideranças aliadas.',
            'Apresente o mandato de oito anos como guarda-chuva político para 2028 e 2032, fortalecendo a carreira local de quem abraçar a campanha.'
        ],
        'metricas' => [
            'Conhecimento e confiança por região',
            'Voto casado por palanque',
            'Prefeitos e vereadores engajados',
            'Ataques reputacionais respondidos'
        ]
    ];
} elseif ($isGovernador) {
    $cargoProfile = [
        'cargo_label' => 'Governador',
        'tagline' => 'A campanha precisa provar liderança executiva: resolver problemas, cuidar das regiões e comandar crises com humanidade e firmeza.',
        'influencia' => [
            'Posicione o candidato como Resolvedor de Problemas: saúde, segurança, infraestrutura, emprego e serviços públicos com soluções técnicas e viáveis.',
            'Use IA para gerar visualizações de Antes e Depois das propostas, tornando a esperança algo tangível e fácil de compartilhar.',
            'Equilibre humanidade e comando: candidato da gente, mas com pulso firme para decidir em crises e liderar especialistas.',
            'Segmente por vocação regional: turismo no litoral, agro no interior, indústria na capital, logística em polos e serviços onde houver demanda.'
        ],
        'equipe' => [
            'Transforme a meta de votos em Exército da Mudança: um movimento para fazer hospital funcionar, escola entregar e segurança chegar.',
            'Promova Cafés com o Governador para mobilizadores que batem metas, usando acesso direto como gatilho de status e lealdade.',
            'Divida o estado em territórios e use gamificação das macro-metas para manter energia alta até a votação.',
            'Destaque coordenadores regionais no comitê central com base nos dados de mobilização e crescimento.'
        ],
        'liderancas' => [
            'Pratique o Municipalismo Real: prefeitos precisam sentir que o futuro governo será parceiro com convênios, obras e presença.',
            'Convide lideranças locais para assinar propostas regionais, criando coautoria sobre pontes, hospitais, estradas, escolas e programas.',
            'Use o CampanhaInteligente para mostrar onde o grupo político local perde espaço e quais territórios precisam de reação.',
            'Deixe claro que lideranças que suarem a camisa serão ouvidas na construção da administração estadual.'
        ],
        'metricas' => [
            'Intenção e rejeição por região',
            'Vocação econômica por território',
            'Macro-metas de mobilização',
            'Convênios e demandas municipais'
        ]
    ];
}

$axes = [
    [
        'slug' => 'influencia',
        'number' => '01',
        'eyebrow' => 'Voto do eleitor',
        'title' => 'Estratégias para Influenciar o Voto do Eleitor',
        'intro' => 'O eleitor de 2026 não quer discurso genérico. Ele precisa sentir que a campanha viu a rua dele, entendeu a dor dele e tem uma resposta concreta antes do adversário ocupar esse espaço.',
        'color_class' => 'strategy-axis--vote',
        'cards' => [
            [
                'title' => 'Segmentação Cirúrgica (Micro-targeting)',
                'body' => 'Utilize ferramentas de análise de dados, como o próprio CRM político, para não falar "com todo mundo". A estratégia é entregar a solução do problema da rua "X" especificamente para os moradores daquela rua via anúncios geolocalizados, WhatsApp territorializado e agenda presencial.'
            ],
            [
                'title' => 'Humanização Sob Pressão',
                'body' => 'O eleitor está saturado de vídeos perfeitos. Mostre o candidato resolvendo problemas reais, em tempo real, sem filtros excessivos. A vulnerabilidade controlada gera mais conexão do que a imagem de super-herói.'
            ],
            [
                'title' => 'Prova Social e Conteúdo Gerado pelo Usuário',
                'body' => 'O voto é influenciado pela vizinhança. Incentive eleitores comuns a gravarem depoimentos curtos. O vídeo de um morador de Capela/SE falando sobre uma melhoria local pode pesar mais que o comercial de TV do candidato.'
            ],
            [
                'title' => 'A Doutrina da Resposta Rápida',
                'body' => 'Com IA gerando desinformação em segundos, a campanha precisa de uma unidade de pronto atendimento digital para desmentir ataques e reafirmar narrativas antes que o boato vire verdade no WhatsApp.'
            ],
            [
                'title' => 'Número, Território e Memória',
                'body' => 'Para cargos proporcionais, o número precisa virar marca. Para cargos majoritários, a imagem precisa virar confiança. Em ambos os casos, repetição inteligente e território claro valem mais que volume sem direção.'
            ],
        ],
        'cargo_items' => $cargoProfile['influencia'],
    ],
    [
        'slug' => 'equipe',
        'number' => '02',
        'eyebrow' => 'Força interna',
        'title' => 'Motivação da Equipe: Transformando Trabalhadores em Defensores',
        'intro' => 'Dados da psicologia organizacional, incluindo Maslow e McClelland, indicam que o amor pela causa nasce da sensação de pertencimento, autonomia, status e propósito.',
        'color_class' => 'strategy-axis--team',
        'cards' => [
            [
                'title' => 'O Efeito "Dono do Pedaço"',
                'body' => 'Pessoas se motivam mais quando sentem autonomia. Dê metas específicas para cada grupo de trabalhadores e permita que eles criem as táticas para alcançá-las. Quando eles desenham a ação, defendem a ação com mais paixão.'
            ],
            [
                'title' => 'Gamificação e Reconhecimento Público',
                'body' => 'Use dados de desempenho, como visitas, adesivos colados, cadastros e conversas qualificadas, para premiar com acesso e status. O destaque pode ganhar um café exclusivo com o candidato ou um papel de liderança.'
            ],
            [
                'title' => 'Treinamento como Empoderamento',
                'body' => 'Transforme sua equipe em especialistas. Quando você ensina um cabo eleitoral a usar ferramentas de IA, dados, WhatsApp segmentado ou técnicas de oratória, ele se sente valorizado e intelectualmente armado para defender a campanha.'
            ],
            [
                'title' => 'Missão Antes da Tarefa',
                'body' => 'Não entregue só uma lista de atividades. Explique o impacto: cada cadastro, visita e reunião precisa ser conectado a um objetivo de território, voto e presença política.'
            ],
        ],
        'cargo_items' => $cargoProfile['equipe'],
    ],
    [
        'slug' => 'liderancas',
        'number' => '03',
        'eyebrow' => 'Base política',
        'title' => 'Engajamento de Lideranças: Fazendo-os "Abraçar" a Campanha',
        'intro' => 'Prefeitos, vereadores, ex-líderes e chefes comunitários se engajam quando percebem legado, reciprocidade, protagonismo e futuro político concreto.',
        'color_class' => 'strategy-axis--leaders',
        'cards' => [
            [
                'title' => 'Coautoria do Plano de Governo',
                'body' => 'Não entregue o plano pronto. Convide a liderança local para redigir ou revisar o capítulo da região dela. Se ela sente paternidade sobre a proposta, luta por ela como se fosse sua própria eleição.'
            ],
            [
                'title' => 'O Conselho de Notáveis',
                'body' => 'Crie um grupo de consulta estratégica para lideranças experientes. Quando um ex-prefeito sente que sua opinião molda os passos do candidato, ele deixa de ser entregador de votos e passa a se sentir parte do núcleo de poder.'
            ],
            [
                'title' => 'Suporte Digital Personalizado',
                'body' => 'Ofereça a estrutura da campanha, como designers, editores de vídeo e estrategistas de dados, para ajudar nas demandas dessas lideranças. Se a campanha facilita a vida política delas, elas se tornam entusiastas do seu sucesso.'
            ],
            [
                'title' => 'Alinhamento de Futuro',
                'body' => 'Mostre com dados como a vitória do candidato principal fortalece a base para as próximas eleições daquela liderança. O engajamento cresce quando ela entende que seu sucesso é o seguro de vida da própria carreira em 2028 ou 2030.'
            ],
            [
                'title' => 'Dados como Moeda de Confiança',
                'body' => 'Mapas de calor, histórico de 2022 e 2024, metas por seção e análise de crescimento dão à liderança algo raro: informação útil. Quem recebe inteligência política tende a retribuir com compromisso.'
            ],
        ],
        'cargo_items' => $cargoProfile['liderancas'],
    ],
];

if ($isSenador) {
    $axes = [
        [
            'slug' => 'influencia',
            'number' => '01',
            'eyebrow' => 'Voto do eleitor',
            'title' => 'Estratégias para Influenciar o Voto do Eleitor',
            'intro' => 'Senador é voto de confiança estadual. O eleitor precisa sentir que está escolhendo alguém capaz de defender o estado inteiro, negociar em Brasília e resistir a crises sem perder serenidade.',
            'color_class' => 'strategy-axis--vote',
            'cards' => [
                [
                    'title' => 'O "Escudo do Estado" em Brasília',
                    'body' => 'Diferente do deputado, o Senador deve focar em grandes entregas e na proteção dos interesses estaduais. Mostre que você é a peça-chave para destravar recursos federais, defender o estado no pacto federativo e viabilizar grandes obras que impactam todas as cidades.'
                ],
                [
                    'title' => 'Autoridade de Estadista',
                    'body' => 'O eleitor de 2026 está exausto de amadorismo. Use comunicação serena e firme. Mostre domínio sobre reforma tributária, pacto federativo, segurança, infraestrutura e orçamento, sempre traduzindo o assunto para a vida do povo.'
                ],
                [
                    'title' => 'A "Segunda Opção" Estratégica',
                    'body' => 'Como em 2026 teremos duas vagas para o Senado, trabalhe o conceito do voto casado. Identifique eleitores de outros candidatos a governador ou presidente que podem ver em você a melhor opção para a segunda vaga.'
                ],
                [
                    'title' => 'Combate ao Deepfake e Narrativas',
                    'body' => 'Por ser campanha majoritária, o Senador é alvo de ataques sofisticados. Use IA para monitorar menções em tempo real e crie um Canal da Verdade no WhatsApp para blindar a biografia antes que ataques reputacionais ganhem escala.'
                ],
                [
                    'title' => 'Prova Social Estadualizada',
                    'body' => 'Depoimentos precisam cobrir o mapa do estado. Prefeitos, ex-prefeitos, empresários, lideranças religiosas, sindicatos e entidades setoriais devem aparecer como fiadores regionais da candidatura.'
                ],
                [
                    'title' => 'Educação do Eleitor',
                    'body' => 'Muita gente não sabe exatamente o que faz um senador. Transforme isso em vantagem: explique que o Senado aprova, barra, fiscaliza e negocia decisões que afetam o futuro do estado por anos.'
                ],
            ],
            'cargo_items' => $cargoProfile['influencia'],
        ],
        [
            'slug' => 'equipe',
            'number' => '02',
            'eyebrow' => 'Força interna',
            'title' => 'Motivação da Equipe: Transformando Trabalhadores em Defensores',
            'intro' => 'Campanha de Senado precisa dar à equipe uma sensação de grandeza. A pessoa de campo deve sentir que participa de uma missão histórica, não de uma operação mecânica de distribuição de material.',
            'color_class' => 'strategy-axis--team',
            'cards' => [
                [
                    'title' => 'Orgulho da Representatividade',
                    'body' => 'O amor pela causa nasce do prestígio. Faça sua equipe sentir que não está apenas entregando santinhos, mas elegendo o representante máximo da honra do estado no Congresso Nacional.'
                ],
                [
                    'title' => 'Rituais de Posse Simbólica',
                    'body' => 'Nomeie líderes regionais com missão, território e responsabilidade. Um coordenador que recebe status público passa a defender a campanha como parte da própria identidade política.'
                ],
                [
                    'title' => 'Visão de Futuro e Legado',
                    'body' => 'Trabalhadores de campanhas majoritárias buscam sentido histórico. Mostre como oito anos de mandato podem destravar obras, leis e recursos que os filhos deles verão.'
                ],
                [
                    'title' => 'Acesso e Escuta Ativa',
                    'body' => 'Como a campanha é grande, o trabalhador de ponta pode se sentir apenas um número. Promova encontros regionais de Escuta Estratégica, onde o candidato ouve as dores da equipe.'
                ],
                [
                    'title' => 'Profissionalização com Propósito',
                    'body' => 'Treine porta-vozes para explicar o Senado, defender a biografia do candidato e responder ataques sem improviso. Equipe que domina argumento trabalha com mais confiança.'
                ],
            ],
            'cargo_items' => $cargoProfile['equipe'],
        ],
        [
            'slug' => 'liderancas',
            'number' => '03',
            'eyebrow' => 'Base política',
            'title' => 'Engajamento de Lideranças: Fazendo-os "Abraçar" a Campanha',
            'intro' => 'Para lideranças, o apoio ao Senado precisa significar acesso, proteção, legado e futuro. O senador deve ser percebido como uma ponte estável entre município, Brasília e carreira política local.',
            'color_class' => 'strategy-axis--leaders',
            'cards' => [
                [
                    'title' => 'O "Padrinho" das Emendas Impositivas',
                    'body' => 'Para prefeitos e vereadores, o Senador é fonte de recursos estáveis. Mostre, com números claros, como o mandato será um guichê aberto para demandas municipais dentro das regras do orçamento.'
                ],
                [
                    'title' => 'Coalizão Interpartidária',
                    'body' => 'Use o papel majoritário para mediar conflitos entre lideranças locais. Se você ajuda a resolver uma disputa política em uma cidade do interior, a liderança tende a abraçar a campanha por gratidão e dependência política.'
                ],
                [
                    'title' => 'Suporte Estratégico de "Gente Grande"',
                    'body' => 'Coloque equipe jurídica, dados e estrutura do CampanhaInteligente à disposição das lideranças aliadas para questões burocráticas, análise de cenário e leitura de risco político.'
                ],
                [
                    'title' => 'Garantia de Espaço Político',
                    'body' => 'Deixe claro como o mandato de oito anos servirá de guarda-chuva para o crescimento das lideranças em 2028 e 2032. A vitória do Senador precisa parecer seguro de vida político por quase uma década.'
                ],
                [
                    'title' => 'Conselho de Notáveis',
                    'body' => 'Crie um grupo consultivo com ex-prefeitos, lideranças setoriais, empresários, acadêmicos e representantes regionais. Quem ajuda a moldar o projeto sente que também é dono da vitória.'
                ],
            ],
            'cargo_items' => $cargoProfile['liderancas'],
        ],
    ];
}

if ($isDeputadoFederal) {
    $axes = [
        [
            'slug' => 'influencia',
            'number' => '01',
            'eyebrow' => 'Voto do eleitor',
            'title' => 'Estratégias para Influenciar o Voto do Eleitor',
            'intro' => 'Deputado Federal precisa transformar Brasília em algo visível para o eleitor. A campanha vence quando o cidadão entende que emenda, lei e fiscalização mudam o hospital, a escola, o emprego e o custo de vida local.',
            'color_class' => 'strategy-axis--vote',
            'cards' => [
                [
                    'title' => 'O "Embaixador de Resultados"',
                    'body' => 'O foco deve ser a capacidade de trazer recursos federais para o estado. Mostre como o trabalho em Brasília impacta diretamente o preço da comida, a saúde e a segurança local.'
                ],
                [
                    'title' => 'Comparativos com IA',
                    'body' => 'Use IA e dados públicos para criar comparativos simples: quanto sua região recebeu de recursos federais vs. quanto poderia ter recebido com um representante mais ativo e articulado.'
                ],
                [
                    'title' => 'Segmentação por Causas (Nichos)',
                    'body' => 'Ao contrário do estadual, que é fortemente geográfico, o federal muitas vezes é temático. Escolha uma bandeira clara, como agronegócio, tecnologia, educação, saúde, segurança ou empreendedorismo.'
                ],
                [
                    'title' => 'Anúncios por Interesse e Território',
                    'body' => 'Use anúncios geolocalizados para falar com grupos específicos: produtores rurais em uma região, profissionais de TI em outra, servidores da saúde em outra, mães de alunos onde educação é a dor principal.'
                ],
                [
                    'title' => 'Conteúdo de "Bastidores do Poder"',
                    'body' => 'Mostre o dia a dia da política nacional de forma desmistificada. Vídeos curtos explicando como uma lei é votada ou como você cobra um ministro geram percepção de trabalho e transparência.'
                ],
                [
                    'title' => 'Unidade de Resposta Digital',
                    'body' => 'Campanhas federais sofrem com polarização nacional. Tenha uma estratégia de WhatsApp para regionalizar grandes temas, mostrando como cada pauta afeta o cidadão local e evitando brigas ideológicas inúteis.'
                ],
            ],
            'cargo_items' => $cargoProfile['influencia'],
        ],
        [
            'slug' => 'equipe',
            'number' => '02',
            'eyebrow' => 'Força interna',
            'title' => 'Motivação da Equipe: Transformando Trabalhadores em Defensores',
            'intro' => 'A equipe de um federal precisa se sentir parte de uma causa nacional com consequência local. Quando entende emenda, lei, fiscalização e bandeira temática, ela deixa de repetir slogan e passa a defender projeto.',
            'color_class' => 'strategy-axis--team',
            'cards' => [
                [
                    'title' => 'Propósito de Mudança Nacional',
                    'body' => 'Faça sua equipe sentir que faz parte de algo maior que o estado. A motivação vem da ideia de que "estamos elegendo quem vai mudar as leis do Brasil".'
                ],
                [
                    'title' => 'Causa que Vira Militância',
                    'body' => 'Conecte cada núcleo a uma bandeira: agro, saúde, educação, segurança, tecnologia, mulheres, juventude ou empreendedorismo. Quem defende uma causa aguenta melhor o desgaste da campanha.'
                ],
                [
                    'title' => 'Capacitação Profissionalizante',
                    'body' => 'Ofereça treinamentos de marketing digital, análise de dados, legislação, orçamento e linguagem de emendas. A equipe amará a campanha porque sairá dela mais qualificada para o mercado.'
                ],
                [
                    'title' => 'Reconhecimento por Performance',
                    'body' => 'Use o CampanhaInteligente para monitorar metas e crie momentos de destaque para os melhores mobilizadores nas redes da campanha nacional. Status público é motor poderoso para quem está na rua.'
                ],
                [
                    'title' => 'Argumentário de 30 Segundos',
                    'body' => 'Treine cada cabo eleitoral para explicar rapidamente o que faz um deputado federal: emendas, leis nacionais, fiscalização, defesa de setores e articulação com ministérios.'
                ],
            ],
            'cargo_items' => $cargoProfile['equipe'],
        ],
        [
            'slug' => 'liderancas',
            'number' => '03',
            'eyebrow' => 'Base política',
            'title' => 'Engajamento de Lideranças: Fazendo-os "Abraçar" a Campanha',
            'intro' => 'Para prefeitos, vereadores e lideranças regionais, Deputado Federal bom é aquele que resolve projeto, abre porta em Brasília e fortalece o legado local. O apoio cresce quando a parceria vira benefício concreto.',
            'color_class' => 'strategy-axis--leaders',
            'cards' => [
                [
                    'title' => 'A "Chave do Cofre" (Recursos Federais)',
                    'body' => 'Prefeitos e vereadores abraçam o Federal que ajuda na sobrevivência financeira da prefeitura. Mostre o plano de emendas de bancada e como a parceria garantirá obras que serão legado em 2028.'
                ],
                [
                    'title' => 'Suporte Jurídico e Técnico',
                    'body' => 'Muitas lideranças sofrem para elaborar projetos e captar verba federal. Ofereça estrutura de advogados, engenheiros, arquitetos ou consultores para formatar demandas com mais chance de aprovação.'
                ],
                [
                    'title' => 'Coautoria Temática',
                    'body' => 'Convide a liderança local para ajudar a redigir uma proposta de lei, indicação ou projeto que beneficie a região dela. Se ela se sente pai da ideia, defenderá seu número como se fosse o dela.'
                ],
                [
                    'title' => 'Garantia de Ascensão Política',
                    'body' => 'Mostre como sua força em Brasília será trampolim para a liderança local. Ter um Deputado Federal "seu" é ter aliado de peso para disputas futuras contra adversários locais.'
                ],
                [
                    'title' => 'Planilha de Demandas Prioritárias',
                    'body' => 'Organize com cada prefeitura e liderança uma lista de obras, equipamentos, convênios, ministérios responsáveis, valor estimado e estágio do projeto. Isso transforma apoio em rotina de trabalho.'
                ],
            ],
            'cargo_items' => $cargoProfile['liderancas'],
        ],
    ];
}

if ($isGovernador) {
    $axes = [
        [
            'slug' => 'influencia',
            'number' => '01',
            'eyebrow' => 'Voto do eleitor',
            'title' => 'Estratégias para Influenciar o Voto do Eleitor',
            'intro' => 'Governador é voto de comando. O eleitor precisa enxergar alguém humano o bastante para ouvir, técnico o bastante para resolver e firme o bastante para decidir quando o estado entra em crise.',
            'color_class' => 'strategy-axis--vote',
            'cards' => [
                [
                    'title' => 'O "Resolvedor de Problemas" (Pragmatismo)',
                    'body' => 'O eleitor de 2026 está saturado de ideologias extremas. A influência virá da capacidade de apresentar soluções técnicas e viáveis para saúde, segurança, infraestrutura, emprego e serviços estaduais.'
                ],
                [
                    'title' => 'Antes e Depois com IA',
                    'body' => 'Use IA para gerar visualizações claras das propostas: fila da saúde antes e depois, estrada recuperada, escola técnica implantada, policiamento reforçado. A esperança precisa ficar tangível.'
                ],
                [
                    'title' => 'Humanidade e Comando',
                    'body' => 'Diferente de cargos legislativos, o Governador precisa parecer alguém da gente e, ao mesmo tempo, ter pulso firme. Mostre o candidato ouvindo especialistas, visitando problemas reais e decidindo sobre crises.'
                ],
                [
                    'title' => 'Segmentação por Vocação Regional',
                    'body' => 'O estado não é um bloco único. Fale de turismo no litoral, agronegócio no interior, indústria na capital, logística nos polos e economia local onde cada cidade reconhece seu motor de desenvolvimento.'
                ],
                [
                    'title' => 'Blindagem e Resposta em Tempo Real',
                    'body' => 'Como figura central da eleição, o candidato ao governo é alvo principal de fake news. Monitore picos de menções negativas e responda com fatos rápidos em vídeos de 15 segundos para WhatsApp.'
                ],
                [
                    'title' => 'Plano Regional Vivo',
                    'body' => 'Não apresente um plano genérico de gabinete. Transforme escutas regionais, dados e demandas locais em compromissos por região, com linguagem simples e metas que o eleitor consiga cobrar.'
                ],
            ],
            'cargo_items' => $cargoProfile['influencia'],
        ],
        [
            'slug' => 'equipe',
            'number' => '02',
            'eyebrow' => 'Força interna',
            'title' => 'Motivação da Equipe: Transformando Trabalhadores em Defensores',
            'intro' => 'Campanha de governo precisa parecer movimento, não apenas estrutura. A base se entrega quando sente que está ajudando a mudar hospital, escola, segurança, estrada e futuro do estado.',
            'color_class' => 'strategy-axis--team',
            'cards' => [
                [
                    'title' => 'O Sentimento de "Exército da Mudança"',
                    'body' => 'A equipe de uma campanha majoritária precisa sentir que está do lado certo da história. Transforme a meta de votos em movimento social: hospital funcionando, segurança chegando, escola melhorando.'
                ],
                [
                    'title' => 'Propósito Acima do Dinheiro',
                    'body' => 'Quando o trabalhador sente que luta para resolver uma dor real do estado, ele deixa de trabalhar só pelo pagamento. Ele passa a defender a campanha como causa.'
                ],
                [
                    'title' => 'Liderança por Exemplo e Acesso',
                    'body' => 'Em campanhas gigantescas, a base pode se sentir invisível. Promova Cafés com o Governador para mobilizadores que baterem metas. Acesso direto ao futuro chefe do Executivo gera status e lealdade.'
                ],
                [
                    'title' => 'Gamificação das Macro-Metas',
                    'body' => 'Divida o estado em territórios e crie ranking de mobilização. Use os dados do sistema para premiar coordenadores regionais com destaque no comitê central.'
                ],
                [
                    'title' => 'Treino de Crise e Redirecionamento',
                    'body' => 'Equipe de governador apanha mais. Treine todos para responder ataques sem descontrole: reconhecer a dúvida, apresentar fato rápido e redirecionar para proposta concreta.'
                ],
            ],
            'cargo_items' => $cargoProfile['equipe'],
        ],
        [
            'slug' => 'liderancas',
            'number' => '03',
            'eyebrow' => 'Base política',
            'title' => 'Engajamento de Lideranças: Fazendo-os "Abraçar" a Campanha',
            'intro' => 'Prefeitos, ex-prefeitos, vereadores e chefes regionais abraçam uma campanha de governo quando enxergam parceria, obra, escuta, inteligência e espaço real na futura administração.',
            'color_class' => 'strategy-axis--leaders',
            'cards' => [
                [
                    'title' => 'O "Municipalismo Real"',
                    'body' => 'Prefeitos e ex-prefeitos abraçam o candidato que garante que a prefeitura não ficará sozinha. Mostre um Plano de Parceria com os Municípios personalizado para cada líder.'
                ],
                [
                    'title' => 'Coautoria das Soluções Regionais',
                    'body' => 'Convide lideranças locais para assinar com você as propostas da região. Se o prefeito ajudou a desenhar a nova ponte ou hospital, defenderá seu nome como garantia do legado dele em 2028.'
                ],
                [
                    'title' => 'Suporte Estratégico de Inteligência',
                    'body' => 'Use o CampanhaInteligente para mostrar onde o grupo político local está perdendo espaço. Quando você entrega inteligência de dados, torna o líder mais forte e mais preparado.'
                ],
                [
                    'title' => 'Garantia de Espaço na Gestão',
                    'body' => 'O engajamento de grandes lideranças passa pela perspectiva de participação no governo. Deixe claro que quem suar a camisa será ouvido na construção da nova administração estadual.'
                ],
                [
                    'title' => 'Conselhos Regionais de Governo',
                    'body' => 'Crie grupos consultivos por região e por setor. A liderança que participa da formulação se sente sócia do projeto e passa a defender a campanha com mais intensidade.'
                ],
            ],
            'cargo_items' => $cargoProfile['liderancas'],
        ],
    ];
}

if (!$hasStrategyContent) {
    $pendingCargoLabel = 'Campanha Geral';
    if (str_contains($cargo, 'federal')) {
        $pendingCargoLabel = 'Deputado Federal';
    } elseif (str_contains($cargo, 'senador')) {
        $pendingCargoLabel = 'Senador';
    } elseif (str_contains($cargo, 'governador')) {
        $pendingCargoLabel = 'Governador';
    }

    $cargoProfile = [
        'cargo_label' => $pendingCargoLabel,
        'tagline' => 'As orientações estratégicas deste cargo serão adicionadas em uma próxima etapa para manter cada plano preciso e sem mistura de conteúdo.',
        'metricas' => [],
    ];
    $axes = [];
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
    <?= premium_render_pwa_tags() ?>
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
            <p class="muted">Um plano dividido em três eixos para a candidatura de <?= premium_escape_html($cargoProfile['cargo_label']) ?>: eleitor, equipe e lideranças.</p>
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
            <?php if ($premiumSupportWhatsappUrl !== ''): ?>
                <div class="vip-support">
                    <a class="btn vip-support__btn" href="<?= premium_escape_html($premiumSupportWhatsappUrl) ?>" target="_blank" rel="noopener">
                        Pedir ajuda no WhatsApp
                    </a>
                </div>
            <?php endif; ?>
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
        <section class="panel hero hero--single advisor-hero strategy-hero">
            <div class="copy">
                <div class="eyebrow">Plano estratégico 2026</div>
                <h2>Cargo: <?= premium_escape_html($cargoProfile['cargo_label']) ?></h2>
                <p><?= premium_escape_html($cargoProfile['tagline']) ?></p>
                <?php if ($hasStrategyContent): ?>
                    <div class="strategy-hero__chips" aria-label="Eixos da página">
                        <span>Eleitor</span>
                        <span>Equipe</span>
                        <span>Lideranças</span>
                    </div>
                <?php endif; ?>
            </div>
            <figure class="strategy-hero__media">
                <img src="assets/agente-estrategica.png" alt="Agente de IA analisando dados e estratégias de campanha">
            </figure>
        </section>

        <?php if (!$hasStrategyContent): ?>
            <section class="panel strategy-coming-soon">
                <div class="eyebrow">Conteúdo em preparação</div>
                <h2>As estratégias específicas para <?= premium_escape_html($cargoProfile['cargo_label']) ?> serão inseridas na sequência.</h2>
                <p class="panel-note">As dicas detalhadas desta página estão disponíveis para Deputado Estadual, Deputado Federal, Senador e Governador. Isso evita misturar orientações de cargos diferentes e mantém cada plano estratégico preciso.</p>
                <div class="action-row">
                    <a class="btn primary" href="premium?tab=agenda<?= $campaign ? '&campaign_id=' . (int)$campaign['id'] : '' ?>">Ir para a Agenda</a>
                    <a class="btn ghost" href="premium_conselheiro.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Verificar Conselheiro</a>
                </div>
            </section>
        <?php else: ?>
            <section class="strategy-index" aria-label="Resumo dos eixos">
                <?php foreach ($axes as $axis): ?>
                    <a class="strategy-index__item <?= premium_escape_html($axis['color_class']) ?>" href="#<?= premium_escape_html($axis['slug']) ?>">
                        <span><?= premium_escape_html($axis['number']) ?></span>
                        <strong><?= premium_escape_html($axis['eyebrow']) ?></strong>
                        <em><?= premium_escape_html($axis['title']) ?></em>
                    </a>
                <?php endforeach; ?>
            </section>

            <?php foreach ($axes as $axis): ?>
                <section id="<?= premium_escape_html($axis['slug']) ?>" class="panel strategy-axis <?= premium_escape_html($axis['color_class']) ?>">
                    <div class="strategy-axis__header">
                        <span class="strategy-axis__number"><?= premium_escape_html($axis['number']) ?></span>
                        <div>
                            <div class="eyebrow"><?= premium_escape_html($axis['eyebrow']) ?></div>
                            <h2><?= premium_escape_html($axis['title']) ?></h2>
                            <p><?= premium_escape_html($axis['intro']) ?></p>
                        </div>
                    </div>

                    <div class="strategy-card-grid">
                        <?php foreach ($axis['cards'] as $card): ?>
                            <article class="strategy-card">
                                <h3><?= premium_escape_html($card['title']) ?></h3>
                                <p><?= premium_escape_html($card['body']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="strategy-axis__cargo">
                        <div>
                            <div class="eyebrow">Aplicação para <?= premium_escape_html($cargoProfile['cargo_label']) ?></div>
                            <h3>Como colocar este eixo em campo</h3>
                        </div>
                        <div class="strategy-action-list">
                            <?php foreach ($axis['cargo_items'] as $item): ?>
                                <article>
                                    <span></span>
                                    <p><?= premium_escape_html($item) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="panel strategy-playbook">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Próximos passos</div>
                        <h2>Ritual semanal de execução</h2>
                    </div>
                </div>
                <div class="strategy-playbook__grid">
                    <article>
                        <strong>Segunda</strong>
                        <p>Defina o território prioritário, a narrativa da semana e as lideranças que precisam de retorno.</p>
                    </article>
                    <article>
                        <strong>Quarta</strong>
                        <p>Revise dados de campo, cadastros, engajamento e boatos circulando nos grupos.</p>
                    </article>
                    <article>
                        <strong>Sexta</strong>
                        <p>Reconheça a equipe, publique prova social e ajuste a agenda presencial do candidato.</p>
                    </article>
                </div>
                <div class="strategy-metrics">
                    <div>
                        <span>Indicadores para acompanhar</span>
                        <ul>
                            <?php foreach ($cargoProfile['metricas'] as $metric): ?>
                                <li><?= premium_escape_html($metric) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p>Campanha vencedora não depende apenas de entusiasmo. Depende de organização, dados, liderança, presença e trabalho contínuo.</p>
                </div>
                <div class="action-row">
                    <a class="btn primary" href="premium?tab=agenda<?= $campaign ? '&campaign_id=' . (int)$campaign['id'] : '' ?>">Ir para a Agenda</a>
                    <a class="btn ghost" href="premium_conselheiro.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Verificar Conselheiro</a>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="assets/js/premium.js"></script>
</body>
</html>
