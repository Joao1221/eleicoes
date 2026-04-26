<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

function premium_advisor_clamp(float $value, float $min = 0.0, float $max = 100.0): float
{
    return max($min, min($max, $value));
}

function premium_advisor_retention_ratio(int $projectedBase, int $baselineVotes): ?float
{
    if ($baselineVotes <= 0) {
        return null;
    }

    return $projectedBase / max(1, $baselineVotes);
}

function premium_advisor_city_effort(array $city): float
{
    $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
    $leaderCount = (int) ($city['leader_count'] ?? 0);
    $projectedBase = (int) ($city['projected_base'] ?? 0);
    $mode = (string) ($city['projection_mode'] ?? '');

    $effort = 1.4;
    if ($baselineVotes <= 10000 && $projectedBase <= 10000) {
        $effort = 1.0;
    } elseif ($baselineVotes > 30000 || $projectedBase > 30000) {
        $effort = 2.2;
    }

    if ($leaderCount <= 0 || $mode !== 'leaders') {
        $effort += 1.0;
    }

    if ($baselineVotes <= 0) {
        $effort += 0.35;
    }

    return max(0.75, $effort);
}

function premium_advisor_is_hole_candidate(array $city): bool
{
    $leaderCount = (int) ($city['leader_count'] ?? 0);
    $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
    $projectedBase = (int) ($city['projected_base'] ?? 0);
    $score = (float) ($city['advisor_score'] ?? 0);
    $holeScore = (float) ($city['hole_score'] ?? 0);
    $retention = premium_advisor_retention_ratio($projectedBase, $baselineVotes);

    if ($holeScore < 62 || $score >= 72) {
        return false;
    }

    if ($baselineVotes > 0 && $retention !== null && $retention >= 1.0) {
        return false;
    }

    if ($leaderCount >= 3 && ($retention === null || $retention >= 0.75)) {
        return false;
    }

    return $leaderCount <= 1 || $baselineVotes <= 0 || ($retention !== null && $retention < 0.70);
}

function premium_advisor_is_defense_candidate(array $city): bool
{
    $leaderCount = (int) ($city['leader_count'] ?? 0);
    $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
    $projectedBase = (int) ($city['projected_base'] ?? 0);
    $defenseScore = (float) ($city['defense_score'] ?? 0);
    $retention = premium_advisor_retention_ratio($projectedBase, $baselineVotes);

    if ($baselineVotes <= 0 || $defenseScore < 62) {
        return false;
    }

    return $leaderCount <= 1 || ($retention !== null && $retention < 0.75);
}

function premium_advisor_expansion_reason(array $city): string
{
    $baselineVotes  = (int)   ($city['baseline_votes']  ?? 0);
    $expansionScore = (float) ($city['expansion_score'] ?? 0);

    if ($baselineVotes <= 0) {
        if ($expansionScore >= 75) {
            return 'Sem histórico eleitoral nesta campanha, mas está em região de alta força. A janela de entrada é real — construir base do zero aqui pode render votos expressivos com articulação ativa.';
        }
        return 'Cidade sem votação histórica registrada nesta campanha. A região é forte, o que facilita a entrada, mas exige prospecção ativa de lideranças locais para construir território.';
    }

    if ($baselineVotes > 30000) {
        return 'Base histórica expressiva e zero líderes cadastrados. Alta prioridade: sem articulação local, esse volume de votos está completamente exposto a adversários da região.';
    }

    if ($baselineVotes > 10000) {
        return 'Tem histórico de votos relevante na região, mas não há nenhum líder registrado. Vulnerabilidade alta — prospectar pelo menos um articulador local é urgente.';
    }

    if ($baselineVotes > 2000) {
        return 'Cidade com base eleitoral moderada e sem liderança cadastrada. A presença regional abre espaço para articulação, mas o território ainda está descoberto.';
    }

    return 'Base histórica pequena, sem liderança cadastrada. Vale prospectar indicações locais para não deixar o espaço aberto para adversários da região.';
}

function premium_advisor_is_expansion_candidate(array $city): bool
{
    $leaderCount = (int) ($city['leader_count'] ?? 0);
    $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
    $projectedBase = (int) ($city['projected_base'] ?? 0);
    $expansionScore = (float) ($city['expansion_score'] ?? 0);
    $retention = premium_advisor_retention_ratio($projectedBase, $baselineVotes);

    if ($expansionScore < 62) {
        return false;
    }

    if ($baselineVotes > 0 && $retention !== null && $retention >= 0.80 && $leaderCount >= 2) {
        return false;
    }

    return $leaderCount > 0 || $baselineVotes <= 0;
}

function premium_advisor_recommendation(array $city): array
{
    $leaderCount = (int) ($city['leader_count'] ?? 0);
    $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
    $projectedBase = (int) ($city['projected_base'] ?? 0);
    $rentability = (float) ($city['rentability_score'] ?? 0);
    $score = (float) ($city['advisor_score'] ?? 0);
    $retention = premium_advisor_retention_ratio($projectedBase, $baselineVotes);

    if ($leaderCount > 0 && $score >= 72) {
        if ($baselineVotes > 0 && $retention !== null && $retention < 0.75) {
            return [
                'title' => 'Defender base',
                'text' => 'A cidade ainda tem força no ranking, mas a projeção está abaixo da base histórica. Reforce lideranças, agenda presencial e defesa do território antes de tratar como expansão.',
            ];
        }

        if ($baselineVotes > 0 && $retention !== null && $retention >= 0.80) {
            return [
                'title' => 'Consolidar base',
                'text' => 'Cidade forte, com lideranças cadastradas e projeção próxima ou acima da base histórica. Mantenha presença, agenda com aliados e defesa ativa do território.',
            ];
        }

        return [
            'title' => 'Prioridade alta',
            'text' => 'Cidade com liderança ativa e boa capacidade projetada. Recomendo visita presencial, agenda com lideranças e reforço territorial.',
        ];
    }

    if ($leaderCount > 0 && $rentability >= 65) {
        return [
            'title' => 'Alta rentabilidade',
            'text' => 'A relação entre votos projetados e esforço estimado é favorável. Vale a pena ampliar presença com custo controlado.',
        ];
    }

    if ($leaderCount > 0 && $baselineVotes <= 0 && $projectedBase > 0) {
        return [
            'title' => 'Oportunidade nova',
            'text' => 'A campanha não tinha base histórica relevante, mas a liderança de 2024 abre caminho de entrada.',
        ];
    }

    if (premium_advisor_is_defense_candidate($city)) {
        return [
            'title' => 'Defender base',
            'text' => 'O candidato tem voto histórico relevante, mas falta sustentação atual ou a projeção caiu demais. Priorize articulação local antes que a base seja ocupada por adversários.',
        ];
    }

    if (premium_advisor_is_hole_candidate($city)) {
        return [
            'title' => 'Buraco eleitoral',
            'text' => 'A cidade está abaixo do potencial regional e tem baixa sustentação local. Busque liderança e teste entrada com agenda pequena antes de ampliar gasto.',
        ];
    }

    if (premium_advisor_is_expansion_candidate($city)) {
        return [
            'title' => 'Expandir território',
            'text' => 'A região oferece sinal de força e a cidade pode ser trabalhada como extensão natural da base regional.',
        ];
    }

    if ($leaderCount <= 0 && $baselineVotes > 0) {
        return [
            'title' => 'Base em risco',
            'text' => 'Houve voto histórico, mas não há liderança cadastrada. Antes de gastar esforços, busque prefeito, vereador ou coordenador local.',
        ];
    }

    if ($baselineVotes > 0 && $retention !== null && $retention >= 1.0) {
        return [
            'title' => 'Monitorar',
            'text' => 'A projeção está acima da base histórica, mas o score ainda não indica prioridade alta. Mantenha acompanhamento e faça ações seletivas para confirmar o crescimento.',
        ];
    }

    return [
        'title' => 'Monitorar',
        'text' => 'Mantenha acompanhamento e use apenas ações pontuais até existir melhor sinal de retorno.',
    ];
}

function premium_build_campaign_advisor(array $campaign, array $_baseline, array $_leaders, array $forecast, array $settings): array
{
    $cities = array_values((array) ($forecast['cities'] ?? []));
    $leaderRows = array_values((array) ($forecast['leaders'] ?? []));
    $regions = array_values((array) ($forecast['regions'] ?? []));

    $maxProjected = 1;
    $maxLeaderEffect = 1;
    $maxBaseline = 1;
    foreach ($cities as $city) {
        $maxProjected = max($maxProjected, (int) ($city['projected_base'] ?? 0));
        $maxLeaderEffect = max($maxLeaderEffect, (int) ($city['leader_projection'] ?? $city['leader_effect'] ?? 0));
        $maxBaseline = max($maxBaseline, (int) ($city['baseline_votes'] ?? 0));
    }

    $strongRegions = [];
    $regionStats = [];
    foreach ($regions as $region) {
        $regionName = (string) ($region['regiao'] ?? '');
        if ((int) ($region['projected_base'] ?? 0) > 0 || (int) ($region['leader_count'] ?? 0) > 0) {
            $strongRegions[$regionName] = (int) ($region['projected_base'] ?? 0);
        }
        $regionStats[$regionName] = [
            'projected_base' => (int) ($region['projected_base'] ?? 0),
            'baseline_votes' => (int) ($region['baseline_votes'] ?? 0),
            'leader_count' => (int) ($region['leader_count'] ?? 0),
        ];
    }
    arsort($strongRegions);
    $topRegionNames = array_slice(array_keys($strongRegions), 0, 3);
    $maxRegionProjected = max(1, max($strongRegions ?: [1]));

    $cityRows = [];
    foreach ($cities as $city) {
        $projectedBase = (int) ($city['projected_base'] ?? 0);
        $leaderProjection = (int) ($city['leader_projection'] ?? $city['leader_effect'] ?? 0);
        $leaderCount = (int) ($city['leader_count'] ?? 0);
        $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
        $region = (string) ($city['regiao'] ?? 'Sem região');
        $mode = (string) ($city['projection_mode'] ?? '');

        $projectionScore = ($projectedBase / $maxProjected) * 100;
        $leaderScore = $leaderCount > 0 ? min(100, (($leaderProjection / $maxLeaderEffect) * 82) + min(18, $leaderCount * 4)) : 0;
        $opportunityScore = $leaderCount > 0
            ? max(0, (($leaderProjection / $maxLeaderEffect) * 100) - (($baselineVotes / $maxBaseline) * 35))
            : 0;
        $sizeBonus = match ((string) ($mode === 'leaders' ? 'leaders' : 'fallback')) {
            'leaders' => $baselineVotes <= 10000 ? 12 : ($baselineVotes <= 30000 ? 8 : 2),
            default => 0,
        };
        $regionalScore = in_array($region, $topRegionNames, true) ? 12 : 4;
        $noLeaderPenalty = $leaderCount <= 0 ? 20 : 0;
        $regionPower = ((int) ($regionStats[$region]['projected_base'] ?? 0) / $maxRegionProjected) * 100;

        $score = premium_advisor_clamp(
            ($projectionScore * 0.35)
            + ($leaderScore * 0.25)
            + ($opportunityScore * 0.15)
            + $sizeBonus
            + $regionalScore
            - $noLeaderPenalty
        );

        $effort = premium_advisor_city_effort($city);
        $rentability = premium_advisor_clamp(($projectedBase / max(1, $maxProjected)) * 100 / $effort * 1.8);
        $baselineScore = ($baselineVotes / $maxBaseline) * 100;
        $retention = premium_advisor_retention_ratio($projectedBase, $baselineVotes);
        $leaderGap = $leaderCount <= 0 ? 100 : max(0, 45 - min(45, $leaderCount * 12));

        $holeScore = premium_advisor_clamp(
            ($regionPower * 0.35)
            + ((100 - min(100, $baselineScore)) * 0.25)
            + ($leaderGap * 0.25)
            + ($projectedBase > 0 ? 10 : 0)
        );

        if ($leaderCount >= 3 && ($retention === null || $retention >= 0.75)) {
            $holeScore = min($holeScore, 45);
        }

        $defenseScore = premium_advisor_clamp(
            ($baselineScore * 0.45)
            + ($leaderGap * 0.30)
            + ($regionPower * 0.15)
            + ($leaderCount <= 0 && $baselineVotes > 0 ? 12 : 0)
            + ($retention !== null && $retention < 0.75 ? 12 : 0)
        );

        $expansionScore = premium_advisor_clamp(
            ($regionPower * 0.40)
            + ($rentability * 0.25)
            + ($leaderCount > 0 ? 18 : 0)
            + ($baselineVotes <= 0 ? 10 : 0)
            - ($baselineVotes > 30000 ? 8 : 0)
        );

        $city['advisor_score'] = round($score, 1);
        $city['rentability_score'] = round($rentability, 1);
        $city['hole_score'] = round($holeScore, 1);
        $city['defense_score'] = round($defenseScore, 1);
        $city['expansion_score'] = round($expansionScore, 1);
        $city['effort_score'] = round($effort, 2);
        $city['retention_ratio'] = $retention !== null ? round($retention, 4) : null;
        $city['recommendation'] = premium_advisor_recommendation($city);
        $cityRows[] = $city;
    }

    usort($cityRows, static fn(array $a, array $b): int => ((float) ($b['advisor_score'] ?? 0)) <=> ((float) ($a['advisor_score'] ?? 0)));

    $leaderRank = [];
    foreach ($leaderRows as $leader) {
        $votes = max(1, (int) ($leader['leader_votes_2024'] ?? 0));
        $projection = (int) ($leader['projected_votes'] ?? 0);
        $leader['conversion_percent'] = round(($projection / $votes) * 100, 1);
        $leader['advisor_value_score'] = round(min(100, (($projection / max(1, $maxLeaderEffect)) * 80) + min(20, $leader['conversion_percent'] / 2)), 1);
        $leaderRank[] = $leader;
    }
    usort($leaderRank, static function (array $a, array $b): int {
        return ((int) ($b['projected_votes'] ?? 0) <=> (int) ($a['projected_votes'] ?? 0))
            ?: ((int) ($b['leader_votes_2024'] ?? 0) <=> (int) ($a['leader_votes_2024'] ?? 0))
            ?: ((float) ($b['advisor_value_score'] ?? 0) <=> (float) ($a['advisor_value_score'] ?? 0));
    });

    $highPriority = array_values(array_filter($cityRows, static fn(array $city): bool => (int) ($city['leader_count'] ?? 0) > 0 && (float) ($city['advisor_score'] ?? 0) >= 72));
    $withoutLeaders = array_values(array_filter($cityRows, static fn(array $city): bool => (int) ($city['leader_count'] ?? 0) <= 0 && (int) ($city['baseline_votes'] ?? 0) > 0));
    $highRentability = array_values(array_filter($cityRows, static fn(array $city): bool => (int) ($city['leader_count'] ?? 0) > 0 && (float) ($city['rentability_score'] ?? 0) >= 65));
    $newOpportunities = array_values(array_filter($cityRows, static fn(array $city): bool => (int) ($city['leader_count'] ?? 0) > 0 && (int) ($city['baseline_votes'] ?? 0) <= 0));
    $electoralHoles = array_values(array_filter($cityRows, 'premium_advisor_is_hole_candidate'));
    $defenseBases = array_values(array_filter($cityRows, 'premium_advisor_is_defense_candidate'));
    $expansionCities = array_values(array_filter($cityRows, 'premium_advisor_is_expansion_candidate'));

    usort($electoralHoles, static fn(array $a, array $b): int => ((float) ($b['hole_score'] ?? 0)) <=> ((float) ($a['hole_score'] ?? 0)));
    usort($defenseBases, static fn(array $a, array $b): int => ((float) ($b['defense_score'] ?? 0)) <=> ((float) ($a['defense_score'] ?? 0)));
    usort($expansionCities, static fn(array $a, array $b): int => ((float) ($b['expansion_score'] ?? 0)) <=> ((float) ($a['expansion_score'] ?? 0)));

    $alerts = [];
    if ($highPriority) {
        $city = $highPriority[0];
        $alerts[] = [
            'type' => 'priority',
            'title' => 'Prioridade imediata',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' combina liderança ativa, projeção relevante e bom retorno político estimado.',
        ];
    }
    if ($withoutLeaders) {
        $city = $withoutLeaders[0];
        $alerts[] = [
            'type' => 'risk',
            'title' => 'Base histórica sem liderança',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' teve votos em ' . premium_baseline_label((int) ($campaign['baseline_year'] ?? 2022)) . ', mas ainda não tem liderança cadastrada. Evite gastos pesados antes de montar apoio local.',
        ];
    }
    if ($highRentability) {
        $city = $highRentability[0];
        $alerts[] = [
            'type' => 'return',
            'title' => 'Melhor retorno estimado',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' aparece como cidade rentável para agenda, material e articulação.',
        ];
    }
    if ($newOpportunities) {
        $city = $newOpportunities[0];
        $alerts[] = [
            'type' => 'opportunity',
            'title' => 'Oportunidade de entrada',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' não tinha base histórica relevante, mas lideranças de 2024 criam porta de entrada.',
        ];
    }
    if ($electoralHoles) {
        $city = $electoralHoles[0];
        $alerts[] = [
            'type' => 'hole',
            'title' => 'Buraco eleitoral relevante',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' está abaixo do potencial da região e deve ser tratada como alvo de prospecção.',
        ];
    }
    if ($defenseBases) {
        $city = $defenseBases[0];
        $alerts[] = [
            'type' => 'defense',
            'title' => 'Defesa de base',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' tem voto histórico, mas precisa de reforço de liderança para reduzir risco de perda.',
        ];
    }

    $expansion = [];
    foreach ($cityRows as $city) {
        $region = (string) ($city['regiao'] ?? '');
        if (in_array($region, $topRegionNames, true) && (int) ($city['leader_count'] ?? 0) <= 0) {
            $expansion[] = [
                'municipio' => (string) ($city['municipio'] ?? ''),
                'regiao' => $region,
                'reason' => premium_advisor_expansion_reason($city),
                'baseline_votes' => (int) ($city['baseline_votes'] ?? 0),
                'projected_base' => (int) ($city['projected_base'] ?? 0),
            ];
        }
    }

    return [
        'campaign_id' => (int) ($campaign['id'] ?? 0),
        'summary' => [
            'priority_cities' => count($highPriority),
            'risk_cities' => count($withoutLeaders),
            'rentable_cities' => count($highRentability),
            'new_opportunities' => count($newOpportunities),
            'electoral_holes' => count($electoralHoles),
            'defense_bases' => count($defenseBases),
            'expansion_cities' => count($expansionCities),
        ],
        'alerts' => array_slice($alerts, 0, 5),
        'cities' => $cityRows,
        'electoral_holes' => array_slice($electoralHoles, 0, 12),
        'defense_bases' => array_slice($defenseBases, 0, 12),
        'expansion_cities' => array_slice($expansionCities, 0, 12),
        'leaders' => $leaderRank,
        'expansion' => array_slice($expansion, 0, 12),
        'top_regions' => $topRegionNames,
        'settings' => $settings,
    ];
}
