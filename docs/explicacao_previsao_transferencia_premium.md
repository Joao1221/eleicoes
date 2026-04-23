# Como o Premium estima transferência de votos de lideranças

Este material explica, de forma simples, como o sistema transforma a votação de uma liderança em uma previsão de votos para a campanha.

O objetivo não é prometer um número mágico. O objetivo é organizar a decisão política com critérios claros: quanto a liderança teve de voto, qual percentual ela tende a transferir, qual é a força real daquela liderança e em que tipo de município ela atua.

## 1. Ideia central

Quando uma liderança teve votos em 2024, o sistema parte de uma pergunta direta:

> Dos votos que essa liderança recebeu, quantos podem ser transferidos para o candidato apoiado em 2026?

Para responder, o Premium usa uma taxa inicial de transferência e depois ajusta essa taxa com fatores políticos.

No exemplo abaixo, usamos estes pesos:

| Peso | Valor usado |
|---|---:|
| Transferência padrão | 30% |
| Bônus alinhamento | 0,10 |
| Peso visibilidade | 0,10 |
| Peso investimento | 0,10 |
| Peso margem | 0,10 |
| Bônus cidade pequena | 0,12 |
| Bônus cidade média | 0,08 |
| Bônus cidade grande | 0 |
| Cenário conservador | 0,90 |
| Cenário base | 1,00 |
| Cenário otimista | 1,12 |

O fallback de 2022 fica fora deste exemplo porque ele só entra quando não existe liderança cadastrada para o município ou região.

## 2. Fórmula usada

O sistema calcula primeiro a transferência inicial:

```text
votos da liderança x transferência padrão
```

Depois aplica os multiplicadores:

```text
transferência inicial
x alinhamento
x visibilidade
x investimento
x margem
x porte do município
x cenário
```

## 3. Premissas usadas nos exemplos

Para os exemplos de apresentação, usamos uma liderança em condição intermediária:

| Fator da liderança | Premissa |
|---|---|
| Alinhamento político | Não alinhada ao executivo ou grupo dominante |
| Visibilidade | 50% |
| Investimento/entregas | 50% |
| Margem eleitoral | 0% |
| Porte do município | Cidade média |

Com isso, os multiplicadores ficam:

| Multiplicador | Cálculo | Resultado |
|---|---:|---:|
| Alinhamento | sem bônus | 1,00 |
| Visibilidade | 1 + 0,50 x 0,10 | 1,05 |
| Investimento | 1 + 0,50 x 0,10 | 1,05 |
| Margem | 1 + 0 x 0,10 | 1,00 |
| Cidade média | 1 + 0,08 | 1,08 |

Multiplicador político total:

```text
1,00 x 1,05 x 1,05 x 1,00 x 1,08 = 1,1907
```

Isso significa que, depois da transferência inicial, o sistema reconhece que uma liderança com visibilidade, algum nível de investimento e atuação em cidade média tende a entregar mais do que uma conversão totalmente seca.

## 4. Exemplo com liderança de 1.000 votos

Primeiro, aplica-se a transferência padrão:

```text
1.000 x 30% = 300 votos
```

Depois, aplica-se o multiplicador político:

```text
300 x 1,1907 = 357,21
```

Resultado no cenário base:

```text
357 votos previstos
35,7% dos 1.000 votos originais
```

### Resultado por cenário

| Cenário | Cálculo | Votos previstos | Percentual sobre 1.000 |
|---|---:|---:|---:|
| Conservador | 357,21 x 0,90 | 321 votos | 32,1% |
| Base | 357,21 x 1,00 | 357 votos | 35,7% |
| Otimista | 357,21 x 1,12 | 400 votos | 40,0% |

## 5. Exemplo com liderança de 100 votos

O mesmo raciocínio vale para uma liderança menor.

Primeiro, aplica-se a transferência padrão:

```text
100 x 30% = 30 votos
```

Depois, aplica-se o multiplicador político:

```text
30 x 1,1907 = 35,72
```

Resultado no cenário base:

```text
36 votos previstos
35,7% dos 100 votos originais
```

### Resultado por cenário

| Cenário | Cálculo | Votos previstos | Percentual sobre 100 |
|---|---:|---:|---:|
| Conservador | 35,72 x 0,90 | 32 votos | 32,1% |
| Base | 35,72 x 1,00 | 36 votos | 35,7% |
| Otimista | 35,72 x 1,12 | 40 votos | 40,0% |

## 6. Por que isso é útil para uma campanha

O Premium não trata todas as lideranças como iguais.

Duas lideranças podem ter a mesma votação em 2024, mas entregar resultados diferentes em 2026. A diferença pode estar em:

- nível de alinhamento político;
- presença pública e reconhecimento;
- obras, ações e entregas no território;
- margem de vitória ou folga política;
- porte do município;
- cenário conservador, base ou otimista da campanha.

Com isso, o deputado deixa de olhar apenas para uma lista bruta de nomes e passa a enxergar potencial real de transferência.

## 7. Leitura política do exemplo

Uma liderança com 1.000 votos não deve ser interpretada automaticamente como 1.000 votos transferíveis.

No exemplo, o sistema estima:

```text
1.000 votos em 2024 -> 357 votos previstos no cenário base
```

Isso é uma leitura mais realista. O modelo reconhece que transferência eleitoral tem perda, depende da força da liderança e varia conforme o contexto local.

Da mesma forma:

```text
100 votos em 2024 -> 36 votos previstos no cenário base
```

O sistema permite somar dezenas, centenas ou milhares de lideranças com o mesmo critério, criando uma projeção consolidada por município, região e campanha.

## 8. Mensagem para o comprador

O Premium ajuda o deputado a responder três perguntas essenciais:

1. Onde estão meus votos prováveis?
2. Quais lideranças realmente podem entregar transferência?
3. Qual é o tamanho do meu cenário conservador, base e otimista?

Em vez de depender apenas de intuição, memória política ou promessas de apoio, o sistema transforma a rede de lideranças em uma previsão organizada, comparável e ajustável.

Isso torna a campanha mais profissional, mais territorial e mais fácil de defender em reuniões estratégicas.
