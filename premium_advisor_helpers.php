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
                'text' => 'A cidade ainda tem forca no ranking, mas a projecao esta abaixo da base historica. Reforce liderancas, agenda presencial e defesa do territorio antes de tratar como expansao.',
            ];
        }

        if ($baselineVotes > 0 && $retention !== null && $retention >= 0.80) {
            return [
                'title' => 'Consolidar base',
                'text' => 'Cidade forte, com liderancas cadastradas e projecao proxima ou acima da base historica. Mantenha presenca, agenda com aliados e defesa ativa do territorio.',
            ];
        }

        return [
            'title' => 'Prioridade alta',
            'text' => 'Cidade com lideranca ativa e boa capacidade projetada. Recomendo visita presencial, agenda com liderancas e reforco territorial.',
        ];
    }

    if ($leaderCount > 0 && $rentability >= 65) {
        return [
            'title' => 'Alta rentabilidade',
            'text' => 'A relacao entre votos projetados e esforco estimado e favoravel. Vale a pena ampliar presenca com custo controlado.',
        ];
    }

    if ($leaderCount > 0 && $baselineVotes <= 0 && $projectedBase > 0) {
        return [
            'title' => 'Oportunidade nova',
            'text' => 'A campanha nao tinha base historica relevante, mas a lideranca de 2024 abre caminho de entrada.',
        ];
    }

    if (premium_advisor_is_defense_candidate($city)) {
        return [
            'title' => 'Defender base',
            'text' => 'O candidato tem voto historico relevante, mas falta sustentacao atual ou a projecao caiu demais. Priorize articulacao local antes que a base seja ocupada por adversarios.',
        ];
    }

    if (premium_advisor_is_hole_candidate($city)) {
        return [
            'title' => 'Buraco eleitoral',
            'text' => 'A cidade esta abaixo do potencial regional e tem baixa sustentacao local. Busque lideranca e teste entrada com agenda pequena antes de ampliar gasto.',
        ];
    }

    if (premium_advisor_is_expansion_candidate($city)) {
        return [
            'title' => 'Expandir territorio',
            'text' => 'A regiao oferece sinal de forca e a cidade pode ser trabalhada como extensao natural da base regional.',
        ];
    }

    if ($leaderCount <= 0 && $baselineVotes > 0) {
        return [
            'title' => 'Base em risco',
            'text' => 'Houve voto historico, mas nao ha lideranca cadastrada. Antes de gastar esforcos, busque prefeito, vereador ou coordenador local.',
        ];
    }

    return [
        'title' => 'Monitorar',
        'text' => 'Mantenha acompanhamento e use apenas acoes pontuais ate existir melhor sinal de retorno.',
    ];
}

function premium_build_campaign_advisor(array $campaign, array $baseline, array $leaders, array $forecast, array $settings): array
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
        $region = (string) ($city['regiao'] ?? 'Sem regiao');
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
    usort($leaderRank, static fn(array $a, array $b): int => ((float) ($b['advisor_value_score'] ?? 0)) <=> ((float) ($a['advisor_value_score'] ?? 0)));

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
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' combina lideranca ativa, projecao relevante e bom retorno politico estimado.',
        ];
    }
    if ($withoutLeaders) {
        $city = $withoutLeaders[0];
        $alerts[] = [
            'type' => 'risk',
            'title' => 'Base historica sem lideranca',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' teve votos em 2022, mas ainda nao tem lideranca cadastrada. Evite gastos pesados antes de montar apoio local.',
        ];
    }
    if ($highRentability) {
        $city = $highRentability[0];
        $alerts[] = [
            'type' => 'return',
            'title' => 'Melhor retorno estimado',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' aparece como cidade rentavel para agenda, material e articulacao.',
        ];
    }
    if ($newOpportunities) {
        $city = $newOpportunities[0];
        $alerts[] = [
            'type' => 'opportunity',
            'title' => 'Oportunidade de entrada',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' nao tinha base historica relevante, mas liderancas de 2024 criam porta de entrada.',
        ];
    }
    if ($electoralHoles) {
        $city = $electoralHoles[0];
        $alerts[] = [
            'type' => 'hole',
            'title' => 'Buraco eleitoral relevante',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' esta abaixo do potencial da regiao e deve ser tratada como alvo de prospeccao.',
        ];
    }
    if ($defenseBases) {
        $city = $defenseBases[0];
        $alerts[] = [
            'type' => 'defense',
            'title' => 'Defesa de base',
            'text' => (string) ($city['municipio'] ?? 'Cidade') . ' tem voto historico, mas precisa de reforco de lideranca para reduzir risco de perda.',
        ];
    }

    $expansion = [];
    foreach ($cityRows as $city) {
        $region = (string) ($city['regiao'] ?? '');
        if (in_array($region, $topRegionNames, true) && (int) ($city['leader_count'] ?? 0) <= 0) {
            $expansion[] = [
                'municipio' => (string) ($city['municipio'] ?? ''),
                'regiao' => $region,
                'reason' => 'Esta na mesma regiao de maior forca da campanha, mas ainda precisa de lideranca local.',
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
