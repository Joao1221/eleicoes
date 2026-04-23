# Documentação do Módulo Conselheiro de Campanha

Este documento explica o funcionamento do módulo `premium_conselheiro.php`, responsável por transformar os dados eleitorais da campanha em recomendações práticas para tomada de decisão.

O Conselheiro não substitui a análise política humana. Ele organiza os dados de 2022, lideranças de 2024, projeção de transferência e força regional para indicar onde a campanha deve priorizar visitas, articulações, defesa de base e expansão territorial.

## 1. Objetivo do Conselheiro

O módulo responde perguntas como:

- Onde a campanha deve investir mais esforço?
- Quais cidades têm melhor retorno estimado?
- Quais cidades precisam de defesa urgente?
- Onde existem buracos eleitorais?
- Quais cidades podem ser trabalhadas como expansão territorial?
- Quais lideranças têm maior capacidade de entregar votos?

O Conselheiro usa os dados já existentes no sistema:

- votação do candidato em 2022;
- lideranças cadastradas com votos de 2024;
- projeção de transferência de votos;
- região do município;
- quantidade de lideranças por cidade;
- pesos configurados no modelo;
- cenário base da campanha.

## 2. Resumo Executivo

No topo do Conselheiro aparecem quatro cards:

| Card | O que representa |
|---|---|
| Prioridade alta | Quantidade de cidades com liderança ativa e score geral forte |
| Bases em risco | Cidades onde houve voto histórico, mas há baixa sustentação atual |
| Alta rentabilidade | Cidades com boa relação entre votos projetados e esforço estimado |
| Oportunidades | Cidades onde a campanha não tinha base histórica relevante, mas há liderança de 2024 abrindo entrada |

Esses números são calculados a partir dos scores descritos nas próximas seções.

## 3. Ranking de Prioridade Municipal

O bloco **Cidades / Ranking de prioridade municipal** mostra as cidades mais importantes para a campanha, ordenadas pelo `advisor_score`.

### Fórmula do advisor_score

O score municipal é calculado de 0 a 100:

```text
advisor_score =
  projeção normalizada x 0,35
+ força das lideranças x 0,25
+ oportunidade sobre 2022 x 0,15
+ bônus de porte
+ força regional
- penalidade sem liderança
```

### Componentes do score

**Projeção normalizada**

Compara a projeção da cidade com a maior projeção entre todas as cidades da campanha.

```text
projeção normalizada = projeção da cidade / maior projeção da campanha
```

Quanto maior a projeção de votos, maior a pontuação.

**Força das lideranças**

Considera:

- votos projetados pelas lideranças;
- quantidade de lideranças cadastradas.

Uma cidade com muitas lideranças e alta projeção tende a subir no ranking.

**Oportunidade sobre 2022**

Mede se a liderança atual abre espaço acima da base histórica.

Uma cidade onde o candidato era fraco em 2022, mas hoje tem liderança forte cadastrada, ganha peso de oportunidade.

**Bônus de porte**

O sistema considera que cidades pequenas e médias tendem a permitir transferência mais direta, especialmente quando há liderança local.

Valores usados:

```text
cidade pequena com liderança: +12
cidade média com liderança: +8
cidade grande com liderança: +2
cidade sem liderança: +0
```

**Força regional**

Usa a região como proxy de vizinhança política.

```text
região entre as 3 mais fortes da campanha: +12
demais regiões: +4
```

**Penalidade sem liderança**

Cidade sem liderança cadastrada perde força no ranking:

```text
sem liderança: -20
```

Isso evita recomendar gasto pesado em cidade sem operador político local.

## 4. Rentabilidade Política

A rentabilidade mostra onde a campanha tem melhor relação entre projeção e esforço.

### Fórmula

```text
rentability_score =
  (projeção da cidade / maior projeção entre cidades)
  x 100
  / esforço estimado
  x 1,8
```

O resultado é limitado entre 0 e 100.

### Esforço estimado

O esforço é uma aproximação do custo político-operacional da cidade.

```text
cidade pequena: 1,0
cidade média: 1,4
cidade grande: 2,2
sem liderança: +1,0
sem base em 2022: +0,35
mínimo: 0,75
```

Interpretação:

- cidades menores custam menos esforço;
- cidades grandes exigem mais estrutura;
- cidades sem liderança exigem mais articulação;
- cidades sem base histórica também exigem esforço extra.

## 5. Recomendações por Cidade

Cada cidade recebe uma recomendação textual. A ordem importa: o sistema avalia primeiro situações mais fortes e depois situações de risco ou expansão.

### Consolidar base

Condição principal:

```text
lideranças > 0
advisor_score >= 72
votos 2022 > 0
retenção projetada >= 80%
```

A retenção é:

```text
retenção = projeção atual / votos de 2022
```

Exemplo:

```text
Votos 2022: 4.403
Projeção atual: 4.183
Retenção: 95%
```

Essa cidade não é buraco eleitoral. Ela é uma base forte que precisa ser mantida, defendida e consolidada.

### Prioridade alta

Condição:

```text
lideranças > 0
advisor_score >= 72
```

Usada para cidades com liderança ativa e boa capacidade projetada.

A recomendação é:

- visita presencial;
- agenda com lideranças;
- reforço territorial;
- acompanhamento constante.

### Defender base

Pode ocorrer em duas situações.

Primeira:

```text
advisor_score >= 72
votos 2022 > 0
retenção projetada < 75%
```

Nesse caso, a cidade ainda é importante, mas a projeção caiu demais em relação à base histórica.

Segunda:

```text
defense_score >= 62
votos 2022 > 0
e uma das condições:
  lideranças <= 1
  ou retenção projetada < 75%
```

Interpretação:

- havia voto histórico;
- a sustentação atual está fraca;
- a campanha precisa proteger a base antes que adversários ocupem o território.

### Alta rentabilidade

Condição:

```text
lideranças > 0
rentability_score >= 65
```

Indica cidade com boa relação entre votos projetados e esforço estimado.

É uma cidade onde ações presenciais, material e articulação podem render bem.

### Oportunidade nova

Condição:

```text
lideranças > 0
votos 2022 <= 0
projeção atual > 0
```

Representa cidade onde a campanha não tinha base histórica relevante, mas uma liderança de 2024 abriu porta de entrada.

### Buraco eleitoral

Condição:

```text
hole_score >= 62
advisor_score < 72
e uma das condições:
  lideranças <= 1
  ou votos 2022 <= 0
  ou retenção projetada < 70%
```

Além disso, existe uma trava:

```text
se a cidade tem 3 ou mais lideranças
e retenção projetada >= 75%
ela não pode ser classificada como buraco eleitoral
```

Isso evita erro conceitual em cidades fortes. Uma cidade com muitas lideranças e projeção próxima da base histórica deve ser tratada como base a consolidar, não como buraco.

### Expandir território

Condição:

```text
expansion_score >= 62
e uma das condições:
  lideranças > 0
  ou votos 2022 <= 0
```

Trava importante:

```text
se a cidade já tem base histórica,
retenção >= 80%
e pelo menos 2 lideranças,
ela não entra como expansão
```

Nesse caso, ela é base consolidada, não expansão.

### Base em risco

Condição:

```text
lideranças <= 0
votos 2022 > 0
```

Indica cidade onde o candidato já teve voto, mas ainda não há liderança cadastrada.

Recomendação:

- não gastar pesado de imediato;
- buscar prefeito, vereador, ex-candidato ou coordenador local;
- só depois ampliar presença.

### Monitorar

Usado quando a cidade não se enquadra nos cortes anteriores.

Recomendação:

- acompanhar;
- fazer ações pontuais;
- não priorizar grandes gastos até surgir melhor sinal de retorno.

## 6. Buracos Eleitorais

O card **Buracos eleitorais** mostra cidades onde a campanha está abaixo do potencial territorial.

### Fórmula do hole_score

```text
hole_score =
  força da região x 0,35
+ falta de base histórica x 0,25
+ lacuna de liderança x 0,25
+ bônus se houver alguma projeção
```

O objetivo é encontrar cidades onde:

- a região tem força;
- a cidade ainda não acompanha essa força;
- há pouca liderança local;
- existe espaço para crescimento.

### Quando NÃO é buraco eleitoral

Uma cidade não deve ser considerada buraco se:

```text
tem 3 ou mais lideranças
e retenção projetada >= 75%
```

Exemplo:

```text
Votos 2022: 4.403
Projeção: 4.183
Lideranças: 10
Retenção: 95%
```

Essa cidade é base forte, não buraco eleitoral.

## 7. Bases em Risco

O card **Bases em risco** mostra cidades onde o candidato teve votação anterior, mas a sustentação atual exige atenção.

### Fórmula do defense_score

```text
defense_score =
  força da base 2022 x 0,45
+ lacuna de liderança x 0,30
+ força regional x 0,15
+ bônus se não houver liderança
+ bônus se retenção < 75%
```

Uma cidade entra como base em risco se:

```text
defense_score >= 62
votos 2022 > 0
e:
  lideranças <= 1
  ou retenção projetada < 75%
```

Interpretação:

- o candidato já teve voto ali;
- existe risco de perder espaço;
- a prioridade é defender, não expandir.

## 8. Cidades de Expansão

O card **Cidades de expansão** mostra cidades onde a campanha pode entrar ou crescer.

### Fórmula do expansion_score

```text
expansion_score =
  força regional x 0,40
+ rentabilidade x 0,25
+ bônus por ter liderança
+ bônus por não ter base histórica
- penalidade se a base histórica for muito grande
```

Valores:

```text
bônus por ter liderança: +18
bônus por não ter base em 2022: +10
penalidade por base histórica muito grande: -8
corte mínimo: 62
```

Uma cidade entra nessa lista quando:

- está em região forte;
- ou tem boa rentabilidade;
- ou tem liderança capaz de abrir território;
- e ainda não é uma base consolidada.

## 9. Ranking de Lideranças

O bloco **Lideranças / Quem mais entrega voto** lista as lideranças mais relevantes.

### Conversão

```text
conversão = votos projetados / votos da liderança em 2024
```

Exemplo:

```text
liderança teve 1.000 votos
projeção é 357 votos
conversão = 35,7%
```

### Score da liderança

```text
advisor_value_score =
  projeção da liderança normalizada x 80
+ bônus pela conversão, limitado a 20
```

Esse ranking ajuda a identificar:

- lideranças que entregam muitos votos;
- lideranças pequenas, mas eficientes;
- lideranças que merecem agenda prioritária;
- lideranças com baixa conversão.

## 10. Expansão Regional por Vizinhança

Como a primeira versão não usa coordenadas geográficas, o sistema usa a **região** como proxy de vizinhança.

Exemplo:

```text
Se a campanha é forte em uma região,
cidades da mesma região sem liderança local
podem aparecer como alvo de expansão ou prospecção.
```

Essa abordagem não substitui análise espacial real, mas já ajuda a indicar onde a campanha pode crescer com menor custo político.

Em fase futura, esse bloco pode ser melhorado com:

- latitude e longitude dos municípios;
- distância entre municípios;
- votação por seção;
- mapa de calor;
- índice de Moran;
- clusters territoriais.

## 11. Alertas do Conselheiro

Os alertas destacam os principais pontos de atenção:

| Alerta | O que significa |
|---|---|
| Prioridade imediata | Cidade forte que pede ação rápida |
| Base histórica sem liderança | Cidade onde houve voto em 2022, mas falta apoio local |
| Melhor retorno estimado | Cidade com boa rentabilidade |
| Oportunidade de entrada | Cidade nova aberta por liderança de 2024 |
| Buraco eleitoral relevante | Cidade abaixo do potencial regional |
| Defesa de base | Cidade onde a base histórica precisa ser protegida |

## 12. Como interpretar corretamente

O Conselheiro deve ser usado como apoio à decisão, não como sentença automática.

Leitura correta:

- cidade forte com muita liderança e alta retenção: consolidar;
- cidade com voto histórico e pouca liderança: defender;
- cidade sem base, mas com liderança: oportunidade;
- cidade em região forte, mas sem sustentação: buraco ou prospecção;
- cidade com boa projeção e baixo esforço: rentável;
- cidade sem sinal claro: monitorar.

## 13. Exemplo prático

Suponha uma cidade com:

```text
Votos 2022: 4.403
Projeção atual: 4.183
Lideranças cadastradas: 10
Retenção projetada: 95%
```

Essa cidade não deve ser classificada como buraco eleitoral.

Ela deve ser interpretada como:

```text
Consolidar base
```

Motivo:

- tem muitas lideranças;
- mantém quase toda a votação histórica;
- tem boa projeção;
- exige defesa e presença, não prospecção de entrada.

## 14. Limitações da versão atual

A versão atual ainda não usa:

- coordenadas geográficas;
- distância real entre municípios;
- votação por seção no mapa;
- emendas parlamentares;
- gasto financeiro por cidade;
- aprovação de prefeitos;
- histórico real de transferência por liderança.

Mesmo assim, ela já organiza a campanha com base em:

- votação histórica;
- força de lideranças;
- projeção de transferência;
- região;
- rentabilidade estimada;
- risco territorial.

## 15. Próximas melhorias recomendadas

Para aumentar a precisão, as próximas etapas ideais são:

1. incluir coordenadas dos municípios;
2. criar mapa de calor;
3. calcular proximidade real entre cidades;
4. usar votação por seção;
5. cruzar emendas e entregas por município;
6. registrar gastos por cidade;
7. calcular custo por voto estimado;
8. criar roteiro de visitas com base em prioridade e proximidade.
