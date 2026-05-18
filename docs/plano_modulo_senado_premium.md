# Plano de implementação: módulo Senado no Premium

Data da análise: 2026-05-16

## Objetivo

Criar um fluxo específico no Premium para candidatos ao Senado, considerando que na eleição de senador o eleitor pode votar em dois candidatos quando há duas vagas em disputa. O sistema deve chegar o mais próximo possível de uma projeção realista, usando:

- votação própria anterior do candidato;
- votação de familiares ou herdeiros políticos;
- votação de aliados em 2018, 2022 ou 2024;
- lideranças municipais cadastradas;
- ajustes por município, região, sobreposição de bases e força política.

## Diagnóstico da estrutura atual

O Premium já possui uma boa base para projeção eleitoral, mas hoje está mais ajustado para campanhas proporcionais e para transferência de lideranças municipais.

Arquivos principais:

- `premium.php`: interface principal do Premium, formulários, abas, tabelas e cards.
- `premium_actions.php`: ações de criação/edição de campanha, lideranças, configurações e exclusões.
- `premium_helpers.php`: funções centrais de busca, baseline, regiões, lideranças e projeção.
- `api_premium.php`: API usada pela interface para buscar lideranças.
- `assets/js/premium.js`: comportamento da busca, seleção em lote, modais e relatórios.
- `database/schema_eleicoes.sql`: estrutura das tabelas eleitorais e premium.

Pontos importantes encontrados:

- `premium_candidate_baseline()` já busca a votação histórica em `votacao_2018` ou `votacao_2022`.
- `premium_build_forecast()` já monta projeção por município, região e liderança.
- A tabela `premium_campaign_leaders` guarda lideranças, mas o campo central ainda é `leader_votes_2024`, o que limita a leitura quando a fonte de votos vem de 2018 ou 2022.
- A busca de lideranças em `api_premium.php` usa apenas `premium_search_2024_candidates()`.
- A tela de lideranças hoje permite buscar apenas `Prefeito` e `Vereador`.

## Limitação encontrada

Tentei consultar o banco configurado em `db.php`, mas a conexão com o host remoto falhou por timeout. Não há CSVs eleitorais locais no diretório do projeto. Portanto, a validação de números reais dentro do banco ainda precisa ser feita quando houver acesso ao banco ou aos arquivos carregados.

## Exemplo analisado: André Moura

Leitura política esperada para o sistema:

- André Moura foi candidato ao Senado em 2018.
- Ele não foi candidato em 2022 nem em 2024.
- Yandra de André/Yandra Moura foi candidata a deputada federal em 2022.
- Como ela é filha de André Moura, a votação dela deve aparecer como fonte migrável para ele em uma campanha ao Senado.

Dados públicos usados como referência inicial:

- André Moura teve 251.213 votos para senador em Sergipe em 2018, com 13,74% dos votos válidos.
- Yandra de André foi eleita deputada federal em Sergipe em 2022 com cerca de 131,4 mil votos.
- A Câmara dos Deputados registra Yandra como filha do ex-deputado federal André Moura.

Fontes consultadas:

- Gazeta do Povo, resultado Senado Sergipe 2018: https://especiais.gazetadopovo.com.br/eleicoes/2018/resultados/eleitos-senadores-se-quem-ganhou/
- Agência Câmara, notícia sobre Yandra de André em 2022: https://www.camara.leg.br/noticias/911314-sergipe-elege-primeira-deputada-federal-mulher-do-estado/

## Conceito do módulo Senado

O Senado precisa de um modelo próprio porque a dinâmica é majoritária estadual, mas com comportamento diferente de governador:

- o voto é mais personalista;
- o eleitor pode escolher dois nomes quando há duas vagas;
- palanque, família política e chapa importam muito;
- bases de deputado federal/estadual podem migrar parcialmente;
- prefeitos e vereadores seguem importantes, mas não explicam tudo;
- a mesma base pode aparecer em mais de uma fonte, então o sistema deve evitar soma bruta duplicada.

## Proposta de fluxo na interface

Quando o cargo da campanha for normalizado como `SENADOR`, o Premium deve exibir um formulário/painel específico chamado, por exemplo, `Projeção Senado`.

Esse painel deve permitir:

- buscar automaticamente o candidato em 2018, 2022 e 2024;
- identificar se ele foi candidato ao Senado em 2018;
- se não foi candidato ao Senado, verificar se disputou outro cargo em 2018 ou 2022;
- buscar fontes migráveis em 2018, 2022 e 2024;
- cadastrar manualmente uma relação política/familiar;
- sugerir percentuais de migração;
- deixar o usuário editar os percentuais;
- mostrar a projeção por município, região e total estadual.

Campos sugeridos no formulário:

- nome da fonte de votos;
- ano da fonte;
- cargo da fonte;
- partido;
- total de votos;
- município ou abrangência estadual;
- tipo de relação: `proprio`, `familiar`, `aliado`, `prefeito`, `vereador`, `manual`;
- percentual de migração;
- confiança da sugestão;
- observações.

## Nova estrutura de dados recomendada

Criar uma tabela própria para fontes de voto do Senado, sem forçar tudo dentro de `premium_campaign_leaders`.

Nome sugerido:

```sql
premium_senate_vote_sources
```

Campos sugeridos:

```sql
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
campaign_id INT UNSIGNED NOT NULL,
source_year SMALLINT NOT NULL,
source_cargo VARCHAR(60) NOT NULL,
source_candidate_name VARCHAR(190) NOT NULL,
source_ballot_name VARCHAR(190) DEFAULT NULL,
source_party VARCHAR(20) DEFAULT NULL,
source_number INT DEFAULT NULL,
source_sq_candidato VARCHAR(50) DEFAULT NULL,
source_total_votes INT NOT NULL DEFAULT 0,
relationship_type VARCHAR(40) NOT NULL DEFAULT 'manual',
transfer_rate DECIMAL(6,2) NOT NULL DEFAULT 40.00,
confidence_score DECIMAL(6,2) NOT NULL DEFAULT 50.00,
notes TEXT DEFAULT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
KEY idx_campaign (campaign_id),
KEY idx_source_year (source_year),
KEY idx_source_candidate (source_candidate_name)
```

Se for necessário detalhar por município, criar também:

```sql
premium_senate_vote_source_municipios
```

Campos sugeridos:

```sql
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
source_id INT UNSIGNED NOT NULL,
municipality VARCHAR(120) NOT NULL,
region_name VARCHAR(120) DEFAULT NULL,
source_votes INT NOT NULL DEFAULT 0,
projected_votes INT NOT NULL DEFAULT 0,
KEY idx_source (source_id),
KEY idx_municipality (municipality)
```

## Busca multiano

Criar funções em `premium_helpers.php` para buscar candidatos em mais de uma eleição.

Funções sugeridas:

```php
premium_search_historical_candidates(mysqli $conn, string $query, array $years = [2018, 2022, 2024]): array
premium_get_candidate_votes_by_municipality(mysqli $conn, int $year, string $cargo, string $candidateName, ?string $sqCandidato = null): array
premium_suggest_senate_vote_sources(mysqli $conn, array $campaign): array
```

Regras:

- 2018 e 2022 devem consultar `votacao_2018` e `votacao_2022`.
- 2024 deve consultar `resumo_votacao_2024_se` e `candidatos_situacao_2024`.
- A busca deve aceitar nome, nome de urna, número e `sq_candidato`, quando disponível.
- Para candidato ao Senado, a prioridade de baseline deve ser 2018.

## Regras de sugestão

Percentuais iniciais sugeridos:

| Tipo de fonte | Transferência inicial sugerida |
|---|---:|
| Próprio candidato ao Senado em 2018 | 60% |
| Familiar/herdeiro político em 2022 | 65% |
| Deputado federal aliado em 2022 | 45% |
| Deputado estadual aliado em 2022 | 35% |
| Prefeito em 2024 | 35% |
| Vereador em 2024 | 25% |
| Fonte manual sem classificação | 30% |

Esses percentuais devem ser apenas sugestões. O usuário precisa poder editar tudo.

## Cálculo específico para Senado

Não somar as fontes de forma bruta.

Modelo sugerido:

1. Carregar a base própria do candidato, preferencialmente Senado 2018.
2. Carregar fontes migráveis cadastradas ou sugeridas.
3. Calcular votos projetados de cada fonte por município:

```text
votos da fonte no município x percentual de migração x multiplicadores políticos
```

4. Aplicar redutor de sobreposição quando houver várias fontes fortes no mesmo município.
5. Aplicar teto municipal para evitar projeção absurda.
6. Agregar por município, região e total estadual.
7. Exibir cenários conservador, base e otimista.

## Sobreposição de bases

Para o Senado, a sobreposição é crítica. Exemplo:

- André Moura 2018 já contém parte da base política dele.
- Yandra 2022 pode refletir a mesma base familiar, agora reorganizada em outro cargo.
- Prefeitos e vereadores aliados podem estar nos mesmos municípios.

Sugestão de regra inicial:

- maior fonte do município entra cheia conforme o percentual configurado;
- segunda fonte entra com redutor de 25%;
- terceira fonte em diante entra com redutor de 40%;
- fontes marcadas como `familiar` têm alerta visual de possível sobreposição com base própria;
- usuário pode desativar o redutor se quiser fazer uma simulação agressiva.

## Teto municipal

Para evitar projeções irreais:

- se houver votação do próprio candidato ao Senado em 2018 no município, usar essa votação como referência principal;
- se não houver, usar o total de votos válidos de senador em 2018 no município como limite contextual;
- como 2018 tinha dois votos para senador, o teto deve considerar a natureza de voto duplo e não comparar diretamente com governador.

Sugestão inicial:

```text
teto municipal base = maior entre:
- votação própria de 2018 x 1,25
- soma migrada bruta x 0,85
```

Depois ajustar com cenário:

- conservador: 0,90
- base: 1,00
- otimista: 1,12

## Interface sugerida

Adicionar uma aba ou painel quando `candidate_cargo` for Senado:

Nome: `Projeção Senado`

Blocos:

1. Base própria encontrada
   - mostra candidatura do próprio candidato em 2018, 2022 ou 2024;
   - destaca se encontrou Senado 2018;
   - permite escolher qual base usar.

2. Fontes migráveis sugeridas
   - lista candidatos encontrados em 2018, 2022 e 2024;
   - mostra nome, cargo, ano, partido, total de votos e motivo da sugestão;
   - botão `Adicionar à projeção`.

3. Fontes cadastradas
   - lista fontes já adicionadas;
   - permite editar percentual, relação e confiança;
   - permite excluir.

4. Resultado por município
   - município;
   - base 2018;
   - fonte principal;
   - votos migrados;
   - redutor de sobreposição;
   - projeção final.

5. Resultado por região e total estadual
   - conservador;
   - base;
   - otimista.

## Arquivos a alterar

Prováveis alterações:

- `database/schema_eleicoes.sql`
  - incluir novas tabelas de fontes do Senado.

- `premium_helpers.php`
  - criar busca histórica multiano;
  - criar sugestão de fontes para Senado;
  - criar cálculo de projeção para Senado;
  - criar helpers de normalização de fonte.

- `api_premium.php`
  - nova action, por exemplo `search_senate_sources`;
  - nova action, se necessário, `suggest_senate_sources`.

- `premium_actions.php`
  - salvar fonte de votos do Senado;
  - atualizar fonte;
  - excluir fonte;
  - adicionar fonte sugerida.

- `premium.php`
  - incluir aba ou painel de Senado;
  - renderizar formulário e tabelas.

- `assets/js/premium.js`
  - busca assíncrona de fontes;
  - seleção de fontes sugeridas;
  - confirmação antes de adicionar;
  - atualização visual da projeção.

- `assets/css/premium.css`
  - estilos do painel Senado, cards e tabelas.

## Ordem recomendada de implementação

1. Criar as tabelas e funções `ensure` para manter compatibilidade com bancos já existentes.
2. Implementar busca histórica multiano.
3. Implementar API de busca/sugestão de fontes do Senado.
4. Criar actions para salvar, editar e excluir fontes.
5. Criar cálculo inicial de projeção Senado.
6. Renderizar painel Senado no Premium.
7. Ajustar JavaScript da busca e seleção.
8. Testar com André Moura/Yandra de André.
9. Ajustar pesos após validar números reais no banco.

## Pendências antes de codar

Confirmar:

1. Se posso criar novas tabelas no banco premium.
2. Se haverá acesso ao banco remoto ou aos CSVs locais para validar os números.
3. Se os percentuais iniciais acima podem ser usados como padrão.
4. Se a interface deve ser uma nova aba lateral (`Projeção Senado`) ou um painel dentro de `Lideranças`.

## Recomendação final

Implementar o Senado como módulo próprio. Adaptar apenas `premium_campaign_leaders` deixaria o sistema confuso, porque liderança municipal de 2024 e fonte estadual/familiar de 2018 ou 2022 são coisas diferentes.

O melhor caminho é manter o modelo atual para lideranças e criar uma camada nova de `fontes de voto do Senado`, com cálculo próprio e integração ao relatório final da campanha.
