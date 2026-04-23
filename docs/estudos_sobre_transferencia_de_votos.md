Com base nos estudos fornecidos, elaborei a documentação técnica abaixo em formato Markdown. Este arquivo servirá como a base teórica e empírica para o seu sistema "Apoia Candidato", documentando as variáveis de transferência de votos, capital político e geografia eleitoral.

***

# Documentação de Estudos Eleitorais: Base Teórica para o Módulo Premium

Este documento consolida os achados científicos que fundamentam os algoritmos de previsão de votos e análise de lideranças do sistema **Apoia Candidato**.

---

## 1. Capital Político e Financiamento (Araújo, Silotto & Cunha)
*   **Conceito Chave:** O capital político individual é o principal preditor de sucesso eleitoral e atração de recursos.
*   **Achados Principais:**
    *   Candidatos com **alto capital político** (experiência prévia em cargos relevantes) têm 193% mais chances de vitória do que a média da amostra.
    *   Eles possuem, em média, **6,4 vezes mais chances** de obter vitória no processo eleitoral.
    *   Doadores privados concentram recursos em atores com maior capacidade de influenciar as arenas decisórias (Executivo e Legislativo).
*   **Aplicação no Sistema:** Líderes com mandatos anteriores de destaque devem ter um peso maior no cálculo de transferência de votos.

## 2. Geografia do Voto e Alinhamento com o Executivo (Borges, Paula & Silva)
*   **Conceito Chave:** A capacidade de dominar um território depende da posição do candidato frente às coalizões do Governador e do Presidente.
*   **Achados Principais:**
    *   **Efeito Cumulativo:** Candidatos cujos partidos participam simultaneamente das coalizões do governador e do presidente têm maior probabilidade de dispersar votos e dominar municípios.
    *   **Vantagem sobre a Oposição:** Candidatos estreantes que contam com o apoio da máquina estadual e federal têm vantagem sobre incumbentes (candidatos à reeleição) que estão na oposição.
    *   **Dominação Territorial:** O acesso a recursos do Executivo permite que parlamentares realizem *casework* (intermediação de demandas) e *pork barrel* (obras locais), fidelizando clientelas.
*   **Aplicação no Sistema:** O algoritmo deve aplicar um multiplicador de **1.2x ou superior** se a liderança local (prefeito/vereador) estiver alinhada ao partido do governador.

## 3. Impacto do Investimento Público na Reeleição (Dias, Nossa & Monte-Mor)
*   **Conceito Chave:** Eleitores premiam gestores que realizam investimentos visíveis no período pré-eleitoral ("Miopia Política").
*   **Achados Principais:**
    *   O incremento relativo de investimentos públicos (obras, pavimentação, hospitais) perto das eleições aumenta significativamente as chances de reeleição e recondução de aliados.
    *   **Accountability e Porte:** Em cidades pequenas, a "distância" menor entre eleitor e representante aumenta a sensibilidade do eleitor ao desempenho do governante.
    *   **Teoria da Gordura:** A "margem de vitória" na eleição anterior funciona como patrimônio político e fidelidade do eleitor.
*   **Aplicação no Sistema:** Líderes em municípios menores ou prefeitos que entregaram grandes obras em 2024 devem ter sua capacidade de transferência de votos sobreponderada.

## 4. Eficácia dos Gastos de Campanha (Souza; Speck & Mancuso)
*   **Conceito Chave:** A forma como o dinheiro é gasto (Tradicional vs. Moderno) altera a eficiência do voto.
*   **Achados Principais:**
    *   **Gastos Tradicionais:** Mobilização de rua e pessoal são mais eficazes para "votos paroquiais" e fidelização de bases em cidades médias.
    *   **Gastos Modernos (Digitais):** Têm maior impacto em municípios populosos (Grandes Centros).
    *   **Eficiência do Desafiante:** Candidatos desafiantes (*challengers*) costumam ser mais eficientes em transformar dinheiro em votos do que os incumbentes.
*   **Aplicação no Sistema:** O sistema deve diferenciar o potencial de transferência de um líder com base no perfil de sua campanha (se baseada em "sola de sapato" ou puramente digital).

## 5. Carreiras e Derrotas Eleitorais (Louault)
*   **Conceito Chave:** A derrota não encerra carreiras, mas provoca "bifurcações" no percurso político.
*   **Achados Principais:**
    *   **Bifurcação Menor:** Reclassificação dentro do campo político (assumir cargos de confiança ou assessorias após perder eleição).
    *   **Bifurcação Radical:** Saída do espaço político profissional para a iniciativa privada, geralmente custosa devido à "desaprendizagem profissional".
*   **Aplicação no Sistema:** Líderes que perderam em 2024 mas mantêm cargos de confiança (Secretários, etc.) ainda possuem capital político relevante para transferência.


# Modelo de Dominância Eleitoral: Base Teórica para o Módulo Premium

O **"Modelo de Dominância"** é uma ferramenta de geografia eleitoral que permite medir e prever a capacidade de um candidato (especialmente ao Legislativo) de controlar eleitoralmente um território específico, como os municípios de Sergipe. No contexto do seu sistema, esse modelo não se baseia apenas na soma bruta de votos, mas na força relativa do candidato em seus municípios-chave.

Abaixo, detalho os fundamentos do modelo aplicados ao cenário das cidades sergipanas:

### 1. O Índice de Dominância
A dominância é definida como o **percentual de votos válidos** que um candidato obtém em cada município onde atua. 
*   **Cálculo:** O índice mede se os municípios mais importantes para o candidato (aqueles que mais contribuem para sua votação total) são também aqueles onde ele obtém votações médias elevadas.
*   **Barreiras à Entrada:** Um alto índice de dominância sinaliza que o candidato conseguiu erguer **barreiras à entrada** para adversários naquele município. Em Sergipe, isso é comum em cidades do interior (como as dos territórios do Sertão ou Agreste Central), onde lideranças locais sólidas conseguem "blindar" o voto para seus aliados.

### 2. Estratégias de Dominância (Tipologia de Ames)
De acordo com os estudos de geografia do voto, o candidato pode construir sua dominância de duas formas principais:
*   **Concentrado-Dominante (Candidato de Reduto):** É o parlamentar paroquial, focado em um município-chave ou região contígua. Geralmente, possui trajetória prévia como ex-prefeito ou ex-vereador ou pertence a uma família política tradicional da cidade. 
*   **Fragmentado-Dominante (Candidato de Máquina):** Apresenta votação dispersa em muitos municípios, mas com **alta dominância média** em vários deles. Esse perfil é típico de candidatos apoiados diretamente pelo **Governo do Estado**, que utilizam a capilaridade da máquina administrativa e secretarias estaduais para estabelecer redes de apoio difusas em todo o estado.

### 3. Fatores que Impulsionam a Dominância em Sergipe
Para o seu sistema calcular a votação esperada, o modelo de dominância deve considerar:
*   **O Peso do Município Pequeno:** A dominância tende a ser muito mais eficaz e previsível em **municípios pequenos** (ex: Pedra Mole ou Pedrinhas), onde a economia é dependente de recursos públicos e a "distância" entre eleitor e representante é menor. Nesses locais, intermediários locais (o vereador de 740 votos que você citou) possuem mais ferramentas para influenciar o voto através do controle de bens e serviços.
*   **Alinhamento com o Governador:** Este é o fator de maior peso estatístico para garantir dominância municipal. Candidatos estreantes que contam com o apoio da máquina estadual apresentam capacidade de dominar municípios superior à de deputados já em mandato (incumbentes) que pertencem à oposição.
*   **Investimentos Visíveis:** O modelo indica que a dominância é reforçada quando a liderança local (prefeito ou aliado) promove o aumento de **investimentos públicos (obras)** no período pré-eleitoral, como pavimentação e construção de escolas, o que gera crédito político para o candidato apoiado.

### 4. Vantagem Competitiva e "Gordura" Eleitoral
O modelo sustenta que a votação obtida por uma liderança na eleição anterior (o patrimônio político) funciona como um **"acúmulo de gordura"** ou fidelidade do eleitor. Independentemente da performance atual, essa base prévia serve como um ponto de partida sólido que dificulta a incursão de candidatos "de fora" nos territórios dominados por lideranças sergipanas consolidadas.

**Em resumo:** Para o seu módulo premium, a dominância em uma cidade de Sergipe deve ser calculada como um **multiplicador**: o apoio de um vereador influente em um município pequeno alinhado ao governador tem um potencial de transferência de votos significativamente maior do que o mesmo apoio em um grande centro urbano ou em um cenário de oposição.


### Índice de Dominância e Controle de Votos

O **Índice de Dominância** funciona como um preditor de vitórias em cidades pequenas ao mensurar a capacidade de um candidato de controlar eleitoralmente um território, transformando-o em um "reduto" protegido. Esse índice é calculado com base no percentual de votos válidos que o político obtém em cada município.

Abaixo, detalha-se como esse modelo se aplica especificamente ao contexto de municípios de pequeno porte:

### 1. Barreiras à Entrada e Controle de Votos
Em cidades pequenas, com economias frequentemente frágeis e dependentes de recursos públicos, a dominância é mais eficaz porque as lideranças locais (intermediários) possuem mais instrumentos para influenciar o voto. O Índice de Dominância reflete a criação de **barreiras à entrada**, o que significa que, uma vez que um candidato estabelece uma votação expressiva em um município pequeno, torna-se muito custoso e difícil para adversários "invadirem" esse reduto.

### 2. Proximidade e Accountability (Prestação de Contas)
O peso desse índice é maior em cidades menores devido à **menor "distância" entre eleitores e representantes**. Essa proximidade aumenta a sensibilidade do eleitor ao desempenho do político e às indicações das lideranças locais, tornando a transferência de votos mais previsível do que em grandes centros urbanos, onde o voto é mais fragmentado e focado em setores de opinião.

### 3. A "Teoria da Gordura" e o Capital Político
O índice utiliza a votação anterior como uma *proxy* de **fidelidade do eleitor**, funcionando como um "acúmulo de gordura" ou patrimônio político inicial. Se um líder teve uma alta margem de vitória (como um vereador muito votado), esse capital político acumulado sinaliza uma base sólida que tende a se manter estável, facilitando a previsão de sucesso para os candidatos apoiados por essa liderança.

### 4. O Efeito Multiplicador do Alinhamento com o Executivo
A dominância em cidades pequenas é fortemente impulsionada pelo **alinhamento com o Governador**. Candidatos que pertencem à coalizão governista têm mais facilidade de dominar municípios chave porque conseguem direcionar recursos "visíveis" (como obras, escolas e hospitais) para essas localidades, o que o eleitor tende a premiar nas urnas. 

### 5. Estreantes vs. Incumbentes
Uma descoberta central dos estudos é que candidatos estreantes apoiados pela máquina do governador podem apresentar uma **capacidade de dominar municípios** superior à de deputados já em mandato (incumbentes) que estejam na oposição. Isso ocorre porque, no Brasil, o acesso aos recursos controlados pelo Executivo é um fator mais determinante para a dominância territorial do que a simples experiência legislativa prévia.

Em resumo, para o seu sistema, o Índice de Dominância prevê vitórias ao identificar onde o candidato possui um **"distrito informal"** consolidado, onde a rede de clientela local e o aporte de recursos estaduais criam uma base de votos altamente resiliente e previsível.

---

### Bibliografia de Referência para Citações no Sistema:

1. **ARAÚJO, Victor; SILOTTO, Graziele; CUNHA, Lucas R.** *Capital político e financiamento eleitoral no Brasil.* Revista de Teoria e Sociedade, 2016.
2. **BORGES, André; PAULA, Carolina; SILVA, Adriano N.** *Eleições legislativas e geografia do voto em contexto de preponderância do Executivo.* Revista de Sociologia e Política, 2014.
3. **DIAS, Bruno P.; NOSSA, Valcemiro; MONTE-MOR, Danilo S.** *O investimento público influencia na reeleição? Um estudo empírico no ES.* RAP, 2018.
4. **SOUZA, Erikson Calheiros de.** *A fórmula do sucesso: como candidatos e incumbents gastam recursos de campanha no Brasil.* Dissertação (Mestrado), UFPB, 2023.
5. **LOUAULT, Frédéric.** *Derrotas eleitorais e carreiras políticas: o caso do PT no Rio Grande do Sul.* Revista de Sociologia e Política, 2011.
6. **SOUZA, Renato B.; LEAL, João Gabriel R. P.** *Reeleição de prefeitos no Brasil: um balanço bibliográfico.* Revista Terceiro Milênio, 2023.