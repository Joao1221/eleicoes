    const premiumPageDataElement = document.getElementById('premium-page-data');
    let premiumPageData = {};
    if (premiumPageDataElement) {
        try {
            premiumPageData = JSON.parse(premiumPageDataElement.textContent || '{}');
        } catch (error) {
            premiumPageData = {};
        }
    }

    const heroRotators = Array.from(document.querySelectorAll('[data-hero-rotator]'));

    const searchBtn = document.getElementById('searchLeadersBtn');
    const searchQueryInput = document.getElementById('searchQuery');
    const resultsBody = document.getElementById('leaderSearchResults');
    const leaderSelectAll = document.getElementById('leaderSelectAll');
    const leaderBatchForm = document.getElementById('leaderBatchForm');
    const leaderBatchPayload = document.getElementById('leaderBatchPayload');
    const leaderBatchCargo = document.getElementById('leaderBatchCargo');
    const leaderBatchSelectedCount = document.getElementById('leaderBatchSelectedCount');
    const leaderBatchSubmitBtn = document.getElementById('leaderBatchSubmitBtn');
    const leaderBatchSelectAllBtn = document.getElementById('leaderBatchSelectAllBtn');
    const leaderBatchClearBtn = document.getElementById('leaderBatchClearBtn');
    const leaderBatchDefaultTransfer = Number(premiumPageData.leaderBatchDefaultTransfer ?? 40);
    const premiumCampaign = Object.assign({
        campaign_name: '',
        candidate_name: '',
        candidate_cargo: '',
        candidate_number: null,
        current_municipio: '',
        current_region: '',
        baseline_year: 2022,
    }, premiumPageData.campaign || {});
    const baselineYearLabel = String(Number(premiumCampaign.baseline_year || 2022) || 2022);
    const premiumLeaders = Array.isArray(premiumPageData.leaders) ? premiumPageData.leaders : [];
    const premiumAgenda = Array.isArray(premiumPageData.agenda) ? premiumPageData.agenda : [];
    const premiumForecast = Object.assign({
        cities: [],
        regions: [],
        leaders: [],
        settings: {},
        totals: {},
    }, premiumPageData.forecast || {});
    const onboardingData = Object.assign({
        hasCampaign: false,
        steps: [],
    }, premiumPageData.onboarding || {});
    const onboardingSteps = Array.isArray(onboardingData.steps) ? onboardingData.steps : [];
    const onboardingRoot = document.querySelector('[data-onboarding-root]');
    const onboardingToggleButtons = Array.from(document.querySelectorAll('[data-onboarding-toggle]'));
    const onboardingStepCounter = onboardingRoot?.querySelector('[data-onboarding-step-counter]') || null;
    const onboardingStepStatus = onboardingRoot?.querySelector('[data-onboarding-step-status]') || null;
    const onboardingProgressFill = onboardingRoot?.querySelector('[data-onboarding-progress-fill]') || null;
    const onboardingStepNumber = onboardingRoot?.querySelector('[data-onboarding-step-number]') || null;
    const onboardingStepTitle = onboardingRoot?.querySelector('[data-onboarding-step-title]') || null;
    const onboardingStepCopy = onboardingRoot?.querySelector('[data-onboarding-step-copy]') || null;
    const onboardingStepAction = onboardingRoot?.querySelector('[data-onboarding-step-action]') || null;
    const onboardingStorageKey = 'premium-onboarding-state-v2';
    const studyModal = document.getElementById('studyModal');
    const studyModalTitle = document.getElementById('studyModalTitle');
    const studyModalSubtitle = document.getElementById('studyModalSubtitle');
    const leaderModal = document.getElementById('leaderModal');
    const externalLeaderModal = document.getElementById('externalLeaderModal');
    const leaderModalTitle = document.getElementById('leaderModalTitle');
    const leaderModalSubtitle = document.getElementById('leaderModalSubtitle');
    const leaderModalSummary = document.getElementById('leaderModalSummary');
    const leaderModeButtons = Array.from(document.querySelectorAll('[data-leader-mode-target]'));
    const leaderModePanels = Array.from(document.querySelectorAll('[data-leader-mode-panel]'));
    const optionsModeButtons = Array.from(document.querySelectorAll('[data-options-mode-target]'));
    const optionsModePanels = Array.from(document.querySelectorAll('[data-options-mode-panel]'));
    const scopeModal = document.getElementById('scopeModal');
    const scopeModalTitle = document.getElementById('scopeModalTitle');
    const scopeModalSubtitle = document.getElementById('scopeModalSubtitle');
    const scopeModalSummary = document.getElementById('scopeModalSummary');
    const scopeModalNote = document.getElementById('scopeModalNote');
    const scopeModalHead = document.getElementById('scopeModalHead');
    const scopeModalBody = document.getElementById('scopeModalBody');
    const cityComparisonModal = document.getElementById('cityComparisonModal');
    const cityComparisonFilterButtons = Array.from(document.querySelectorAll('[data-city-comparison-filter]'));
    const cityComparisonBody = document.getElementById('cityComparisonBody');
    const cityComparisonEmptyRow = document.getElementById('cityComparisonEmptyRow');
    const agendaModal = document.getElementById('agendaModal');
    const agendaListModal = document.getElementById('agendaListModal');
    const agendaModalTitle = document.getElementById('agendaModalTitle');
    const agendaModalSubtitle = document.getElementById('agendaModalSubtitle');
    const agendaModalSummary = document.getElementById('agendaModalSummary');
    const agendaPreviewArea = document.getElementById('agendaPreviewArea');
    const agendaPreviewNote = document.getElementById('agendaPreviewNote');
    const agendaFilterButtons = Array.from(document.querySelectorAll('[data-agenda-filter]'));
    const activeLeadersCityFilter = document.getElementById('activeLeadersCityFilter');
    const activeLeadersTypeFilter = document.getElementById('activeLeadersTypeFilter');
    const activeLeadersPartyFilter = document.getElementById('activeLeadersPartyFilter');
    const activeLeadersResetBtn = document.getElementById('activeLeadersResetBtn');
    const activeLeadersVisibleCount = document.getElementById('activeLeadersVisibleCount');
    const activeLeadersTotalCount = document.getElementById('activeLeadersTotalCount');
    const activeLeadersFilterEmpty = document.getElementById('activeLeadersFilterEmpty');
    const activeLeadersRowsViewport = document.getElementById('activeLeadersRowsViewport');
    const activeLeadersSelectAll = document.getElementById('activeLeadersSelectAll');
    const leaderBulkTransferForm = document.getElementById('leaderBulkTransferForm');
    const leaderBulkTransferPayload = document.getElementById('leaderBulkTransferPayload');
    const leaderBulkTransferScope = document.getElementById('leaderBulkTransferScope');
    const leaderBulkTransferValue = document.getElementById('leaderBulkTransferValue');
    const leaderBulkTransferSelectedBtn = document.getElementById('leaderBulkTransferSelectedBtn');
    const leaderBulkTransferVisibleBtn = document.getElementById('leaderBulkTransferVisibleBtn');
    const leaderBulkTransferAllBtn = document.getElementById('leaderBulkTransferAllBtn');
    const leaderBulkDeleteForm = document.getElementById('leaderBulkDeleteForm');
    const leaderBulkDeletePayload = document.getElementById('leaderBulkDeletePayload');
    const leaderBulkDeleteBtn = document.getElementById('leaderBulkDeleteBtn');
    const leaderBulkSelectVisibleBtn = document.getElementById('leaderBulkSelectVisibleBtn');
    const leaderBulkClearBtn = document.getElementById('leaderBulkClearBtn');
    const leaderBulkSelectedCount = document.getElementById('leaderBulkSelectedCount');
    let agendaFilter = 'pending';
    let scopeModalColspan = 8;
    let scopeModalState = {
        type: 'city',
        name: '',
    };
    let cityComparisonFilter = 'all';

    const themeToggleButtons = Array.from(document.querySelectorAll('[data-theme-toggle]'));
    const premiumThemeStorageKey = 'premium-theme';
    const premiumThemeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    let premiumThemePreference = null;
    let leaderSearchDebounceId = null;
    let leaderSearchAbortController = null;

    function normalizePremiumTheme(value) {
        return String(value) === 'light' ? 'light' : 'dark';
    }

    function getSystemPremiumTheme() {
        return premiumThemeMediaQuery.matches ? 'dark' : 'light';
    }

    function getInitialPremiumTheme() {
        const datasetTheme = document.documentElement.dataset.theme;
        if (datasetTheme === 'light' || datasetTheme === 'dark') {
            return datasetTheme;
        }

        return getSystemPremiumTheme();
    }

    function applyPremiumTheme(theme, persist = true) {
        const normalizedTheme = normalizePremiumTheme(theme);
        document.documentElement.dataset.theme = normalizedTheme;
        document.documentElement.style.colorScheme = normalizedTheme;

        if (persist) {
            premiumThemePreference = normalizedTheme;
            document.documentElement.dataset.themeSource = 'stored';
            try {
                localStorage.setItem(premiumThemeStorageKey, normalizedTheme);
            } catch (error) {
                // Ignore storage failures and keep the current theme in memory.
            }
        } else if (premiumThemePreference === null) {
            document.documentElement.dataset.themeSource = 'system';
        }

        themeToggleButtons.forEach((button) => {
            const isActive = normalizePremiumTheme(button.dataset.themeToggle || '') === normalizedTheme;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function initHeroRotator(rotator) {
        const messages = Array.from(rotator.querySelectorAll('[data-hero-message]'));
        const triggers = Array.from(rotator.querySelectorAll('[data-hero-message-trigger]'));
        if (messages.length <= 1) {
            return;
        }

        const interval = Math.max(1000, Number(rotator.dataset.heroInterval || 300000));
        let activeIndex = Math.max(0, messages.findIndex((message) => !message.hidden));
        if (activeIndex < 0) {
            activeIndex = 0;
        }
        let timerId = null;

        const render = (index) => {
            activeIndex = ((index % messages.length) + messages.length) % messages.length;
            messages.forEach((message, messageIndex) => {
                const isActive = messageIndex === activeIndex;
                message.hidden = !isActive;
                message.classList.toggle('is-active', isActive);
            });
            triggers.forEach((trigger, triggerIndex) => {
                const isActive = triggerIndex === activeIndex;
                trigger.classList.toggle('is-active', isActive);
                trigger.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const schedule = () => {
            if (timerId) {
                window.clearInterval(timerId);
            }
            timerId = window.setInterval(() => {
                render(activeIndex + 1);
            }, interval);
        };

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                render(Number(trigger.dataset.heroMessageIndex || 0));
                schedule();
            });
        });

        render(activeIndex);
        schedule();
    }

    const initialPremiumTheme = getInitialPremiumTheme();
    if (document.documentElement.dataset.themeSource === 'stored') {
        premiumThemePreference = initialPremiumTheme;
    }

    applyPremiumTheme(initialPremiumTheme, false);
    heroRotators.forEach(initHeroRotator);

    if (premiumThemePreference === null) {
        const syncPremiumThemeWithSystem = (event) => {
            if (premiumThemePreference !== null) {
                return;
            }

            applyPremiumTheme(event.matches ? 'dark' : 'light', false);
        };

        if (typeof premiumThemeMediaQuery.addEventListener === 'function') {
            premiumThemeMediaQuery.addEventListener('change', syncPremiumThemeWithSystem);
        } else if (typeof premiumThemeMediaQuery.addListener === 'function') {
            premiumThemeMediaQuery.addListener(syncPremiumThemeWithSystem);
        }
    }

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            premiumThemePreference = normalizePremiumTheme(button.dataset.themeToggle || 'dark');
            applyPremiumTheme(premiumThemePreference);
        });
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function normalizeText(value) {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim()
            .replace(/\s+/g, ' ');
    }

    function applyActiveLeadersFilters() {
        if (!activeLeadersRowsViewport) {
            return;
        }

        const rows = Array.from(document.querySelectorAll('[data-active-leader-row]'));
        const cityFilter = normalizeText(activeLeadersCityFilter?.value || '');
        const typeFilter = String(activeLeadersTypeFilter?.value || '').trim();
        const partyFilter = normalizeText(activeLeadersPartyFilter?.value || '');

        let visibleCount = 0;
        rows.forEach((row) => {
            const rowCity = normalizeText(row.dataset.leaderMunicipality || '');
            const rowType = String(row.dataset.leaderType || '').trim();
            const rowParty = normalizeText(row.dataset.leaderParty || '');
            const showRow = (!cityFilter || rowCity === cityFilter) && (!typeFilter || rowType === typeFilter) && (!partyFilter || rowParty === partyFilter);

            row.hidden = !showRow;
            if (showRow) {
                visibleCount += 1;
            }
        });

        if (activeLeadersVisibleCount) {
            activeLeadersVisibleCount.textContent = formatNumber(visibleCount);
        }

        if (activeLeadersTotalCount) {
            activeLeadersTotalCount.textContent = formatNumber(rows.length);
        }

        if (activeLeadersFilterEmpty) {
            activeLeadersFilterEmpty.hidden = visibleCount > 0;
        }

        activeLeadersRowsViewport.hidden = rows.length > 0 && visibleCount === 0;
        updateActiveLeaderBulkSelectionState();
    }

    function getActiveLeaderCheckboxes() {
        return Array.from(document.querySelectorAll('.leader-bulk-checkbox'));
    }

    function getVisibleActiveLeaderCheckboxes() {
        return getActiveLeaderCheckboxes().filter((checkbox) => !checkbox.closest('tr')?.hidden);
    }

    function getActiveLeaderIdsForScope(scope = 'selected') {
        const normalizedScope = String(scope || 'selected');
        let checkboxes = [];

        if (normalizedScope === 'all') {
            checkboxes = getActiveLeaderCheckboxes();
        } else if (normalizedScope === 'visible') {
            checkboxes = getVisibleActiveLeaderCheckboxes();
        } else {
            checkboxes = getActiveLeaderCheckboxes().filter((checkbox) => checkbox.checked);
        }

        const ids = [];
        const seen = new Set();
        checkboxes.forEach((checkbox) => {
            const id = Number.parseInt(checkbox.value || '0', 10);
            if (!Number.isFinite(id) || id <= 0 || seen.has(id)) {
                return;
            }

            seen.add(id);
            ids.push(id);
        });

        return ids;
    }

    function updateActiveLeaderBulkSelectionState() {
        const checkboxes = getActiveLeaderCheckboxes();
        const visibleCheckboxes = getVisibleActiveLeaderCheckboxes();
        const selectedCheckboxes = checkboxes.filter((checkbox) => checkbox.checked);
        const selectedVisibleCheckboxes = visibleCheckboxes.filter((checkbox) => checkbox.checked);
        const totalCount = checkboxes.length;

        if (leaderBulkSelectedCount) {
            leaderBulkSelectedCount.textContent = `${selectedCheckboxes.length} selecionadas`;
        }

        if (leaderBulkDeleteBtn) {
            leaderBulkDeleteBtn.disabled = selectedCheckboxes.length === 0;
        }

        if (leaderBulkTransferSelectedBtn) {
            leaderBulkTransferSelectedBtn.disabled = selectedCheckboxes.length === 0;
        }

        if (leaderBulkTransferVisibleBtn) {
            leaderBulkTransferVisibleBtn.disabled = visibleCheckboxes.length === 0;
        }

        if (leaderBulkTransferAllBtn) {
            leaderBulkTransferAllBtn.disabled = totalCount === 0;
        }

        if (activeLeadersSelectAll) {
            activeLeadersSelectAll.disabled = visibleCheckboxes.length === 0;
            activeLeadersSelectAll.checked = visibleCheckboxes.length > 0 && selectedVisibleCheckboxes.length === visibleCheckboxes.length;
            activeLeadersSelectAll.indeterminate = selectedVisibleCheckboxes.length > 0 && selectedVisibleCheckboxes.length < visibleCheckboxes.length;
        }

        checkboxes.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.toggle('is-selected', checkbox.checked);
            }
        });
    }

    function setActiveLeaderBulkSelection(checked, visibleOnly = false) {
        const targets = visibleOnly ? getVisibleActiveLeaderCheckboxes() : getActiveLeaderCheckboxes();
        targets.forEach((checkbox) => {
            checkbox.checked = checked;
        });
        updateActiveLeaderBulkSelectionState();
    }

    function buildActiveLeaderBulkDeletePayload() {
        return getActiveLeaderIdsForScope('selected');
    }

    function buildActiveLeaderBulkTransferPayload(scope = 'selected') {
        return getActiveLeaderIdsForScope(scope);
    }

    function findForecastCity(cityName) {
        const needle = normalizeText(cityName);
        return (premiumForecast.cities || []).find((item) => normalizeText(item.municipio || '') === needle) || null;
    }

    function findForecastRegion(regionName) {
        const needle = normalizeText(regionName);
        return (premiumForecast.regions || []).find((item) => normalizeText(item.regiao || '') === needle) || null;
    }

    function getScopeLeaders(scopeType, scopeName) {
        const needle = normalizeText(scopeName);
        const leaders = (premiumForecast.leaders || []).filter((leader) => {
            if (scopeType === 'region') {
                return normalizeText(leader.region_name || '') === needle;
            }
            return normalizeText(leader.municipality || '') === needle;
        });

        return leaders.sort((a, b) => {
            const projectionCompare = Number(b.projected_votes || 0) - Number(a.projected_votes || 0);
            if (projectionCompare !== 0) {
                return projectionCompare;
            }

            const votesCompare = Number(b.leader_votes_2024 || 0) - Number(a.leader_votes_2024 || 0);
            if (votesCompare !== 0) {
                return votesCompare;
            }

            return String(a.leader_display_name || a.leader_name || '').localeCompare(String(b.leader_display_name || b.leader_name || ''), 'pt-BR');
        });
    }

    function clearScopeModal() {
        if (scopeModalTitle) {
            scopeModalTitle.textContent = 'Selecione um recorte territorial';
        }
        if (scopeModalSubtitle) {
            scopeModalSubtitle.textContent = `Clique em uma cidade ou regiÃ£o para ver as lideranÃ§as, as projeÃ§Ãµes individuais e o comparativo com ${baselineYearLabel}.`;
        }
        if (scopeModalSummary) {
            scopeModalSummary.innerHTML = '';
        }
        if (scopeModalNote) {
            scopeModalNote.textContent = `O detalhe territorial mostrarÃ¡ o total de votos de ${baselineYearLabel} apenas como comparativo e destacarÃ¡ a projeÃ§Ã£o atual construÃ­da pelas lideranÃ§as cadastradas.`;
        }
        if (scopeModalHead) {
            scopeModalHead.innerHTML = `
                <tr>
                    <th>LideranÃ§a</th>
                    <th>MunicÃ­pio</th>
                    <th>Votos 2024</th>
                    <th>Base transferÃ­vel</th>
                    <th>ProjeÃ§Ã£o 2026</th>
                    <th>TransferÃªncia</th>
                    <th>AÃ§Ã£o</th>
                </tr>
            `;
        }
        if (scopeModalBody) {
            scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Selecione uma cidade ou regiÃ£o para carregar os lÃ­deres.</td></tr>`;
        }
    }

    function openScopeModal(scopeType, scopeName) {
        if (!scopeModal) {
            return;
        }

        const normalizedType = scopeType === 'region' ? 'region' : 'city';
        scopeModalState = {
            type: normalizedType,
            name: scopeName || '',
        };
        scopeModalColspan = normalizedType === 'city' ? 7 : 8;
        closeLeaderModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        const leaders = getScopeLeaders(normalizedType, scopeName);
        const cityScope = normalizedType === 'city' ? findForecastCity(scopeName) : null;
        const regionScope = normalizedType === 'region' ? findForecastRegion(scopeName) : null;
        const scopeData = cityScope || regionScope;
        const comparativeBase = Number(scopeData?.baseline_votes || 0);
        const projected = Number(scopeData?.projected_base || 0);
        const delta = projected - comparativeBase;
        const leaderEffect = leaders.reduce((sum, leader) => sum + Number(leader.projected_votes || 0), 0);
        const totalVotes2024 = leaders.reduce((sum, leader) => sum + Number(leader.leader_votes_2024 || 0), 0);
        const baseTransferable = leaders.reduce((sum, leader) => sum + Number(leader.base_effect || 0), 0);

        if (scopeModalTitle) {
            scopeModalTitle.textContent = `${scopeName || 'Recorte territorial'} - ${normalizedType === 'region' ? 'RegiÃ£o' : 'Cidade'}`;
        }

        if (scopeModalSubtitle) {
            const comparativeBase = Number(scopeData?.baseline_votes || 0);
            const projected = Number(scopeData?.projected_base || 0);
            const delta = projected - comparativeBase;
            scopeModalSubtitle.textContent = `${scopeName || 'Recorte territorial'} â€¢ Comparativo ${baselineYearLabel}: ${formatNumber(comparativeBase)} â€¢ ProjeÃ§Ã£o atual: ${formatNumber(projected)} â€¢ Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}`;
        }

        if (scopeModalSummary) {
            const comparativeBase = Number(scopeData?.baseline_votes || 0);
            const projected = Number(scopeData?.projected_base || 0);
            const delta = projected - comparativeBase;
            const leaderEffect = leaders.reduce((sum, leader) => sum + Number(leader.projected_votes || 0), 0);
            const totalVotes2024 = leaders.reduce((sum, leader) => sum + Number(leader.leader_votes_2024 || 0), 0);
            const baseTransferable = leaders.reduce((sum, leader) => sum + Number(leader.base_effect || 0), 0);
            scopeModalSummary.innerHTML = [
                `<span class="table-pill">${baselineYearLabel}: ${formatNumber(comparativeBase)}</span>`,
                `<span class="table-pill">ProjeÃ§Ã£o: ${formatNumber(projected)}</span>`,
                `<span class="table-pill">Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}</span>`,
                `<span class="table-pill">LideranÃ§as: ${formatNumber(leaders.length)}</span>`,
                `<span class="table-pill">Votos 2024: ${formatNumber(totalVotes2024)}</span>`,
                `<span class="table-pill">Base transferÃ­vel: ${formatNumber(baseTransferable)}</span>`,
                `<span class="table-pill">efeito das lideranÃ§as: ${formatNumber(leaderEffect)}</span>`,
            ].join('');
        }

        if (scopeModalNote) {
            const hasLeaders = leaders.length > 0;
            scopeModalNote.textContent = hasLeaders
                ? `As lideranÃ§as abaixo sÃ£o as cadastradas para este recorte. A projeÃ§Ã£o total da cidade ou regiÃ£o Ã© calculada a partir dos votos das lideranÃ§as; o total de ${baselineYearLabel} aparece apenas como comparativo.`
                : `Nenhuma lideranÃ§a cadastrada neste recorte. Nesse caso, a projeÃ§Ã£o do territÃ³rio pode cair no fallback de ${baselineYearLabel} para manter a leitura estratÃ©gica.`;
        }

        if (scopeModalHead) {
            if (normalizedType === 'city') {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>LideranÃ§a</th>
                        <th>Votos 2024</th>
                        <th>Base transferÃ­vel</th>
                        <th>ProjeÃ§Ã£o 2026</th>
                        <th>TransferÃªncia</th>
                        <th>AÃ§Ã£o</th>
                    </tr>
                `;
            } else {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>MunicÃ­pio</th>
                        <th>LideranÃ§a</th>
                        <th>Votos 2024</th>
                        <th>Base transferÃ­vel</th>
                        <th>ProjeÃ§Ã£o 2026</th>
                        <th>TransferÃªncia</th>
                        <th>AÃ§Ã£o</th>
                    </tr>
                `;
            }
        }

        if (scopeModalBody) {
            if (!leaders.length) {
                scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Nenhuma lideranÃ§a cadastrada neste recorte.</td></tr>`;
            } else {
                scopeModalBody.innerHTML = leaders.map((leader) => {
                    const leaderDisplayName = leader.leader_display_name || leader.leader_name || 'LideranÃ§a';
                    const municipality = leader.municipality || scopeName || '-';
                    const votes = formatNumber(leader.leader_votes_2024 || 0);
                    const baseEffect = formatNumber(leader.base_effect || 0);
                    const projectedVotes = formatNumber(leader.projected_votes || 0);
                    const transferRate = formatNumber(leader.transfer_rate || 0) + '%';
                    const actionButton = leader.id
                        ? `<button type="button" class="btn ghost btn-small" data-leader-id="${escapeHtml(leader.id)}">Abrir</button>`
                        : '<span class="muted">-</span>';

                    if (normalizedType === 'city') {
                        return `
                            <tr>
                                <td>${escapeHtml(leaderDisplayName)}</td>
                                <td>${votes}</td>
                                <td>${baseEffect}</td>
                                <td>${projectedVotes}</td>
                                <td>${transferRate}</td>
                                <td>${actionButton}</td>
                            </tr>
                        `;
                    }

                    return `
                        <tr>
                            <td>${escapeHtml(municipality)}</td>
                            <td>${escapeHtml(leaderDisplayName)}</td>
                            <td>${votes}</td>
                            <td>${baseEffect}</td>
                            <td>${projectedVotes}</td>
                            <td>${transferRate}</td>
                            <td>${actionButton}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        if (scopeModalSubtitle) {
            scopeModalSubtitle.textContent = `${scopeName || 'Recorte territorial'} â€¢ Ranking por projeÃ§Ã£o individual â€¢ Comparativo ${baselineYearLabel}: ${formatNumber(comparativeBase)} â€¢ ProjeÃ§Ã£o atual: ${formatNumber(projected)} â€¢ Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}`;
        }

        if (scopeModalSummary) {
            const topLeader = leaders[0] || null;
            const topLeaderName = topLeader ? (topLeader.leader_display_name || topLeader.leader_name || 'LideranÃ§a') : 'Sem lideranÃ§a';
            scopeModalSummary.innerHTML = `
                <div class="scope-summary-grid">
                    <div class="summary-metric summary-metric--primary">
                        <div class="summary-metric__label">ProjeÃ§Ã£o total</div>
                        <div class="summary-metric__value">${formatNumber(projected)}</div>
                        <div class="summary-metric__sub">Total projetado do recorte territorial</div>
                    </div>
                    <div class="summary-metric summary-metric--delta">
                        <div class="summary-metric__label">DiferenÃ§a para ${baselineYearLabel}</div>
                        <div class="summary-metric__value">${delta >= 0 ? '+' : ''}${formatNumber(delta)}</div>
                        <div class="summary-metric__sub">Comparativo sobre a base histÃ³rica</div>
                    </div>
                    <div class="summary-metric">
                        <div class="summary-metric__label">LideranÃ§as</div>
                        <div class="summary-metric__value">${formatNumber(leaders.length)}</div>
                        <div class="summary-metric__sub">Ordenadas pelo ranking interno do recorte</div>
                    </div>
                </div>
                <div class="scope-summary-meta">
                    <span class="table-pill">${baselineYearLabel}: ${formatNumber(comparativeBase)}</span>
                    <span class="table-pill">Votos 2024: ${formatNumber(totalVotes2024)}</span>
                    <span class="table-pill">Base transferÃ­vel: ${formatNumber(baseTransferable)}</span>
                    <span class="table-pill">efeito das lideranÃ§as: ${formatNumber(leaderEffect)}</span>
                    <span class="table-pill">Top 1: ${escapeHtml(topLeaderName)}</span>
                </div>
            `;
        }

        if (scopeModalNote) {
            scopeModalNote.textContent = leaders.length
                ? 'Ranking ordenado por projeÃ§Ã£o individual. A projeÃ§Ã£o total soma as lideranÃ§as cadastradas.'
                : `Nenhuma lideranÃ§a cadastrada neste recorte. A projeÃ§Ã£o pode usar o fallback de ${baselineYearLabel} para manter a leitura estratÃ©gica.`;
        }

        if (scopeModalHead) {
            if (normalizedType === 'city') {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>PosiÃ§Ã£o</th>
                        <th>LideranÃ§a</th>
                        <th>Votos 2024</th>
                        <th>TransferÃªncia</th>
                        <th>Base transferÃ­vel</th>
                        <th>ProjeÃ§Ã£o 2026</th>
                        <th>AÃ§Ã£o</th>
                    </tr>
                `;
            } else {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>PosiÃ§Ã£o</th>
                        <th>MunicÃ­pio</th>
                        <th>LideranÃ§a</th>
                        <th>Votos 2024</th>
                        <th>TransferÃªncia</th>
                        <th>Base transferÃ­vel</th>
                        <th>ProjeÃ§Ã£o 2026</th>
                        <th>AÃ§Ã£o</th>
                    </tr>
                `;
            }
        }

        if (scopeModalBody) {
            if (!leaders.length) {
                scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Nenhuma lideranÃ§a cadastrada neste recorte.</td></tr>`;
            } else {
                scopeModalBody.innerHTML = leaders.map((leader, index) => {
                    const rank = String(index + 1).padStart(2, '0');
                    const rankClass = index === 0 ? 'scope-rank-badge scope-rank-badge--top' : index === 1 ? 'scope-rank-badge scope-rank-badge--silver' : index === 2 ? 'scope-rank-badge scope-rank-badge--bronze' : 'scope-rank-badge';
                    const rowClass = index === 0 ? 'scope-row--top' : '';
                    const leaderDisplayName = leader.leader_display_name || leader.leader_name || 'LideranÃ§a';
                    const municipality = leader.municipality || scopeName || '-';
                    const votes = formatNumber(leader.leader_votes_2024 || 0);
                    const baseEffect = formatNumber(leader.base_effect || 0);
                    const projectedVotes = formatNumber(leader.projected_votes || 0);
                    const transferRate = formatNumber(leader.transfer_rate || 0) + '%';
                    const actionButton = leader.id
                        ? `<button type="button" class="btn ghost btn-small" data-leader-id="${escapeHtml(leader.id)}">Abrir</button>`
                        : '<span class="muted">-</span>';

                    if (normalizedType === 'city') {
                        return `
                            <tr class="${rowClass}">
                                <td><span class="${rankClass}">${rank}</span></td>
                                <td>${escapeHtml(leaderDisplayName)}</td>
                                <td>${votes}</td>
                                <td>${transferRate}</td>
                                <td>${baseEffect}</td>
                                <td>${projectedVotes}</td>
                                <td>${actionButton}</td>
                            </tr>
                        `;
                    }

                    return `
                        <tr class="${rowClass}">
                            <td><span class="${rankClass}">${rank}</span></td>
                            <td>${escapeHtml(municipality)}</td>
                            <td>${escapeHtml(leaderDisplayName)}</td>
                            <td>${votes}</td>
                            <td>${transferRate}</td>
                            <td>${baseEffect}</td>
                            <td>${projectedVotes}</td>
                            <td>${actionButton}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        scopeModal.hidden = false;
        scopeModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeScopeModal(updateBody = true) {
        if (!scopeModal) {
            return;
        }

        scopeModal.hidden = true;
        scopeModal.setAttribute('aria-hidden', 'true');
        clearScopeModal();
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function applyCityComparisonFilter(filter = 'all') {
        cityComparisonFilter = ['leaders', 'fallback'].includes(filter) ? filter : 'all';

        cityComparisonFilterButtons.forEach((button) => {
            const isActive = (button.dataset.cityComparisonFilter || 'all') === cityComparisonFilter;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        let visibleCount = 0;
        cityComparisonBody?.querySelectorAll('[data-city-comparison-row]')?.forEach((row) => {
            const rowMode = row.dataset.cityMode || 'all';
            const shouldShow = cityComparisonFilter === 'all' || rowMode === cityComparisonFilter;
            row.hidden = !shouldShow;
            if (shouldShow) {
                visibleCount += 1;
            }
        });

        if (cityComparisonEmptyRow) {
            cityComparisonEmptyRow.hidden = visibleCount > 0;
        }
    }

    function openCityComparisonModal() {
        if (!cityComparisonModal) {
            return;
        }

        closeLeaderModal(false);
        closeScopeModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        applyCityComparisonFilter(cityComparisonFilter);

        cityComparisonModal.hidden = false;
        cityComparisonModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeCityComparisonModal(updateBody = true) {
        if (!cityComparisonModal) {
            return;
        }

        cityComparisonModal.hidden = true;
        cityComparisonModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function getCityComparisonFilterLabel(filter) {
        if (filter === 'leaders') {
            return 'Com lideranÃ§as';
        }
        if (filter === 'fallback') {
            return 'Sem lideranÃ§as';
        }

        return 'Todas as cidades';
    }

    function getCityComparisonRows(filter = 'all') {
        const normalizedFilter = ['all', 'leaders', 'fallback'].includes(filter) ? filter : 'all';

        return (premiumForecast.cities || []).filter((city) => {
            const hasLeaders = Number(city.leader_count || 0) > 0;
            const rowMode = hasLeaders ? 'leaders' : 'fallback';
            return normalizedFilter === 'all' || normalizedFilter === rowMode;
        });
    }

    function buildCityComparisonReportHtml(filter = 'all') {
        const rows = getCityComparisonRows(filter);
        const generatedAt = new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date());
        const campaignLabel = [
            premiumCampaign.campaign_name || 'Campanha Premium',
            premiumCampaign.candidate_name || '',
            premiumCampaign.candidate_cargo || '',
        ].filter(Boolean).join(' â€¢ ');
        const coverageLabel = [
            premiumCampaign.current_region || '',
            premiumCampaign.current_municipio || '',
        ].filter(Boolean).join(' â€¢ ') || 'Sergipe';
        const filterLabel = getCityComparisonFilterLabel(filter);

        const baselineTotal = rows.reduce((sum, city) => sum + Number(city.baseline_votes || 0), 0);
        const systemTotal = rows.reduce((sum, city) => sum + Number(city.system_projection || city.projected_base || 0), 0);
        const leaderVotesTotal = rows.reduce((sum, city) => sum + Number(city.leader_projection || city.leader_effect || 0), 0);
        const independentTotal = rows.reduce((sum, city) => sum + Number(city.independent_votes || 0), 0);
        const withLeaders = rows.filter((city) => Number(city.leader_count || 0) > 0).length;
        const withoutLeaders = rows.length - withLeaders;
        const deltaTotal = systemTotal - baselineTotal;

        const rowsHtml = rows.length ? rows.map((city, index) => {
            const municipality = city.municipio || '';
            const region = city.regiao || 'Sem regiÃ£o';
            const baselineVotes = Number(city.baseline_votes || 0);
            const leaderCount = Number(city.leader_count || 0);
            const leaderVotes = Number(city.leader_projection || city.leader_effect || 0);
            const independentVotes = Number(city.independent_votes || 0);
            const systemProjection = Number(city.system_projection || city.projected_base || 0);
            const delta = systemProjection - baselineVotes;
            const hasLeaders = leaderCount > 0;
            const statusLabel = hasLeaders ? 'Com lideranÃ§as' : `Fallback ${baselineYearLabel}`;
            const statusClass = hasLeaders ? 'report-status report-status--leaders' : 'report-status report-status--fallback';
            const rank = String(index + 1).padStart(2, '0');

            return `
                <tr class="${hasLeaders ? 'report-row--leaders' : 'report-row--fallback'}">
                    <td><span class="report-rank">${rank}</span> ${escapeHtml(municipality)}</td>
                    <td>${escapeHtml(region)}</td>
                    <td>${formatNumber(baselineVotes)}</td>
                    <td>${formatNumber(leaderVotes)}</td>
                    <td>${formatNumber(independentVotes)}</td>
                    <td>${formatNumber(systemProjection)}</td>
                    <td>${delta >= 0 ? '+' : ''}${formatNumber(delta)}</td>
                    <td><span class="${statusClass}">${escapeHtml(statusLabel)}</span></td>
                </tr>
            `;
        }).join('') : `
            <tr>
                <td colspan="8" class="report-empty">Nenhuma cidade corresponde ao filtro selecionado.</td>
            </tr>
        `;

        return `
<!DOCTYPx html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RelatÃ³rio Comparativo | ${escapeHtml(campaignLabel)}</title>
    <style>
        :root {
            --bg: #f6f8fb;
            --paper: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: rgba(15, 23, 42, 0.10);
            --accent: #0f766e;
            --accent-2: #0284c7;
            --accent-3: #f59e0b;
            --success-bg: rgba(16, 185, 129, 0.10);
            --fallback-bg: rgba(2, 132, 199, 0.08);
            --shadow: 0 24px 80px rgba(15, 23, 42, 0.10);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.10), transparent 24%),
                radial-gradient(circle at top right, rgba(2, 132, 199, 0.08), transparent 20%),
                var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        .report-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 24px 36px;
        }

        .report-hero {
            background: linear-gradient(135deg, #ffffff, #eef7f5);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 24px 26px;
            box-shadow: var(--shadow);
            margin-bottom: 18px;
        }

        .report-hero__top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .report-brand {
            padding: 11px 18px;
            font-size: .84rem;
        }

        .report-hero h1 {
            margin: 12px 0 8px;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            line-height: 1.02;
        }

        .report-hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .report-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .report-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.72);
            font-size: .8rem;
            font-weight: 700;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .report-action {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px 18px;
            font: inherit;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .report-action--primary {
            background: linear-gradient(135deg, #0f766e, #0284c7);
            color: #f8fffb;
            box-shadow: 0 16px 28px rgba(2, 132, 199, 0.18);
        }

        .report-action--ghost {
            background: rgba(15, 23, 42, 0.04);
            color: var(--text);
            border-color: rgba(15, 23, 42, 0.12);
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0;
        }

        .report-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.05);
            min-height: 116px;
        }

        .report-card__label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .68rem;
            font-weight: 800;
        }

        .report-card__value {
            margin-top: 16px;
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1.06;
        }

        .report-card__sub {
            margin-top: 6px;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.4;
        }

        .report-notes {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0 0 18px;
        }

        .report-note {
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.6;
        }

        .report-legend {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .report-legend__item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--line);
            font-size: .78rem;
            font-weight: 700;
        }

        .report-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            flex: 0 0 10px;
        }

        .report-dot--leaders { background: #0f766e; }
        .report-dot--fallback { background: #0284c7; }

        .report-table-wrap {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: .72rem;
        }

        .report-table th,
        .report-table td {
            padding: 8px 8px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            vertical-align: top;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.15;
        }

        .report-table thead th {
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .67rem;
            color: var(--muted);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .report-table th:nth-child(1),
        .report-table td:nth-child(1) { width: 17%; }
        .report-table th:nth-child(2),
        .report-table td:nth-child(2) { width: 12%; }
        .report-table th:nth-child(3),
        .report-table td:nth-child(3) { width: 6%; }
        .report-table th:nth-child(4),
        .report-table td:nth-child(4) { width: 13%; }
        .report-table th:nth-child(5),
        .report-table td:nth-child(5) { width: 13%; }
        .report-table th:nth-child(6),
        .report-table td:nth-child(6) { width: 13%; }
        .report-table th:nth-child(7),
        .report-table td:nth-child(7) { width: 7%; }
        .report-table th:nth-child(8),
        .report-table td:nth-child(8) { width: 19%; }

        .report-table .report-status {
            white-space: normal;
            max-width: 100%;
            line-height: 1.1;
        }

        .report-table tbody tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.015);
        }

        .report-row--leaders {
            background: rgba(16, 185, 129, 0.05);
        }

        .report-row--fallback {
            background: rgba(2, 132, 199, 0.04);
        }

        .report-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 9px;
            margin-right: 8px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-size: .72rem;
            font-weight: 900;
        }

        .report-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .report-status--leaders {
            background: rgba(16, 185, 129, 0.12);
            color: #0f766e;
        }

        .report-status--fallback {
            background: rgba(2, 132, 199, 0.12);
            color: #0369a1;
        }

        .report-empty {
            padding: 22px;
            color: var(--muted);
            text-align: center;
        }

        .report-footer {
            margin-top: 14px;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.5;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body {
                background: #fff;
                font-size: 11px;
                line-height: 1.25;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-shell {
                max-width: none;
                padding: 0;
            }

            .report-hero {
                padding: 14px 16px;
                border-radius: 18px;
                margin-bottom: 12px;
            }

            .report-brand {
                padding: 5px 9px;
                font-size: .62rem;
            }

            .report-hero h1 {
                margin: 8px 0 6px;
                font-size: 1.45rem;
                line-height: 1.05;
            }

            .report-hero p {
                line-height: 1.3;
                font-size: .8rem;
            }

            .report-meta {
                margin-top: 10px;
                gap: 6px;
            }

            .report-pill {
                padding: 5px 9px;
                font-size: .68rem;
            }

            .report-actions {
                display: none !important;
            }

            .report-summary {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
                gap: 6px !important;
                margin: 10px 0 12px !important;
            }

            .report-card {
                padding: 8px 8px;
                min-height: 72px;
                border-radius: 12px;
            }

            .report-card__label {
                font-size: .55rem;
            }

            .report-card__value {
                margin-top: 4px;
                font-size: .95rem;
                line-height: 1.02;
            }

            .report-card__sub {
                margin-top: 2px;
                font-size: .56rem;
                line-height: 1.25;
            }

            .report-notes {
                gap: 8px;
                margin: 0 0 12px;
            }

            .report-note {
                padding: 9px 10px;
                font-size: .7rem;
                line-height: 1.3;
                border-radius: 12px;
            }

            .report-legend {
                gap: 6px;
                margin-bottom: 10px;
            }

            .report-legend__item {
                padding: 5px 9px;
                font-size: .68rem;
            }

            .report-table-wrap {
                border-radius: 14px;
            }

            .report-table {
                font-size: .64rem;
            }

            .report-table th,
            .report-table td {
                padding: 6px 8px;
                line-height: 1.2;
            }

            .report-table thead th {
                font-size: .58rem;
            }

            .report-rank {
                min-width: 20px;
                height: 20px;
                padding: 0 5px;
                margin-right: 5px;
                font-size: .56rem;
                line-height: 1;
            }

            .report-status {
                padding: 4px 8px;
                font-size: .6rem;
            }

            .report-empty {
                padding: 16px;
            }

            .report-footer {
                margin-top: 10px;
                font-size: .68rem;
                line-height: 1.3;
            }

            .report-hero,
            .report-card,
            .report-note,
            .report-legend__item,
            .report-table-wrap {
                box-shadow: none !important;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tr {
                page-break-inside: avoid;
            }
        }

        @media (max-width: 1120px) {
            .report-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .report-notes {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                try {
                    window.print();
                } catch (error) {}
            }, 350);
        });
    <\/script>
</head>
<body>
    <div class="report-shell">
        <section class="report-hero">
            <div class="report-hero__top">
                <div>
                    <div class="report-brand">Apoia Candidato Premium</div>
                    <h1>Comparativo municipal ${baselineYearLabel} x projeÃ§Ã£o 2026</h1>
                    <p>${escapeHtml(campaignLabel)}</p>
                    <div class="report-meta">
                        <span class="report-pill">Cobertura: ${escapeHtml(coverageLabel)}</span>
                        <span class="report-pill">Filtro: ${escapeHtml(filterLabel)}</span>
                        <span class="report-pill">Gerado em: ${escapeHtml(generatedAt)}</span>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="report-action report-action--primary" type="button" onclick="window.print()">Imprimir</button>
                    <button class="report-action report-action--ghost" type="button" onclick="window.close()">Fechar</button>
                </div>
            </div>
            <div class="report-notes" style="margin-top: 16px;">
                <div class="report-note">
                    <strong>Votos de lideranÃ§a</strong> representam a parcela da projeÃ§Ã£o atribuÃ­da Ã s lideranÃ§as cadastradas em cada municÃ­pio.
                </div>
                <div class="report-note">
                    <strong>Votos independentes</strong> representam a parcela da projeÃ§Ã£o que nÃ£o depende de lideranÃ§a cadastrada; nas cidades sem lideranÃ§a, o sistema usa o fallback de ${baselineYearLabel}.
                </div>
            </div>
        </section>

        <section class="report-summary">
            <div class="report-card">
                <div class="report-card__label">Comparativo ${baselineYearLabel}</div>
                <div class="report-card__value">${formatNumber(baselineTotal)}</div>
                <div class="report-card__sub">Base histÃ³rica do recorte exibido</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">ProjeÃ§Ã£o 2026</div>
                <div class="report-card__value">${formatNumber(systemTotal)}</div>
                <div class="report-card__sub">Total projetado pelo modelo</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Delta total</div>
                <div class="report-card__value">${deltaTotal >= 0 ? '+' : ''}${formatNumber(deltaTotal)}</div>
                <div class="report-card__sub">DiferenÃ§a entre projeÃ§Ã£o e ${baselineYearLabel}</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Com lideranÃ§as</div>
                <div class="report-card__value">${formatNumber(withLeaders)}</div>
                <div class="report-card__sub">MunicÃ­pios com apoio cadastrado</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Sem lideranÃ§as</div>
                <div class="report-card__value">${formatNumber(withoutLeaders)}</div>
                <div class="report-card__sub">MunicÃ­pios que usam fallback</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Votos de lideranÃ§a</div>
                <div class="report-card__value">${formatNumber(leaderVotesTotal)}</div>
                <div class="report-card__sub">Parcela atribuÃ­da Ã s lideranÃ§as</div>
            </div>
        </section>

        <div class="report-legend">
            <span class="report-legend__item"><span class="report-dot report-dot--leaders"></span>MunicÃ­pios com lideranÃ§as</span>
            <span class="report-legend__item"><span class="report-dot report-dot--fallback"></span>MunicÃ­pios sem lideranÃ§as</span>
            <span class="report-legend__item">Votos independentes = projeÃ§Ã£o fora das lideranÃ§as</span>
        </div>

        <section class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>MunicÃ­pio</th>
                        <th>RegiÃ£o</th>
                        <th>${baselineYearLabel}</th>
                        <th>Votos LideranÃ§a</th>
                        <th>Votos independentes</th>
                        <th>ProjeÃ§Ã£o 2026</th>
                        <th>Delta</th>
                        <th>SituaÃ§Ã£o</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
        </section>

        <div class="report-footer">
            RelatÃ³rio elaborado a partir do mÃ³dulo premium. Os votos de ${baselineYearLabel} entram como comparativo e como fallback apenas nos municÃ­pios sem lideranÃ§as cadastradas.
        </div>
    </div>
</body>
</html>`;
    }

    function openCityComparisonReport() {
        if (!cityComparisonModal) {
            return;
        }

        const reportWindow = window.open('', '_blank', 'width=1280,height=900');
        if (!reportWindow) {
            alert('NÃ£o foi possÃ­vel abrir o relatÃ³rio de impressÃ£o. Verifique se o navegador bloqueou a janela.');
            return;
        }

        reportWindow.document.open();
        reportWindow.document.write(buildCityComparisonReportHtml(cityComparisonFilter));
        reportWindow.document.close();
        reportWindow.focus();
    }

    function stripActionColumnFromTableHtml(tableSectionHtml, cellTagName) {
        const template = document.createElement('template');
        template.innerHTML = `<table>${tableSectionHtml}</table>`;

        const rows = template.content.querySelectorAll('tr');
        rows.forEach((row) => {
            const cells = row.querySelectorAll(cellTagName);
            if (!cells.length) {
                return;
            }

            const lastCell = cells[cells.length - 1];
            const label = (lastCell.textContent || '').trim().toLowerCase();
            const hasActionButton = !!lastCell.querySelector('button, .btn');

            if (label === 'aÃ§Ã£o' || hasActionButton) {
                lastCell.remove();
            }
        });

        return template.content.querySelector('table')?.innerHTML || tableSectionHtml;
    }

    function buildScopeReportHtml() {
        const campaignParts = [
            premiumCampaign.campaign_name || 'Campanha ativa',
            premiumCampaign.candidate_name || '',
            premiumCampaign.candidate_cargo || '',
        ].filter(Boolean);
        const campaignLabel = campaignParts.join(' â€¢ ');
        const generatedAt = new Date().toLocaleString('pt-BR');
        const scopeLabel = scopeModalTitle?.textContent || 'Recorte territorial';
        const subtitle = scopeModalSubtitle?.textContent || '';
        const summaryHtml = scopeModalSummary?.innerHTML || '';
        const note = scopeModalNote?.textContent || '';
        const tableHeadHtml = stripActionColumnFromTableHtml(scopeModalHead?.innerHTML || '', 'th');
        const tableBodyHtml = stripActionColumnFromTableHtml(scopeModalBody?.innerHTML || '', 'td');

        return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(scopeLabel)} | ImpressÃ£o</title>
    <style>
        :root { color-scheme: light; --bg:#f5f7fb; --panel:#fff; --line:rgba(15,23,42,.12); --text:#0f172a; --muted:#475569; --brand:#0ea5e9; --brand-soft:rgba(14,165,233,.10); }
        * { box-sizing: border-box; }
        body { margin:0; padding:28px; font-family:Inter, Arial, sans-serif; background:var(--bg); color:var(--text); }
        .report-shell { max-width:1180px; margin:0 auto; }
        .report-hero, .report-summary, .report-note, .report-table-wrap { background:var(--panel); border:1px solid var(--line); border-radius:18px; }
        .report-hero { padding:24px; }
        .report-brand { display:inline-block; padding:6px 10px; border-radius:999px; background:var(--brand-soft); color:#0369a1; font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .report-hero__top { display:flex; justify-content:space-between; gap:20px; align-items:flex-start; }
        .report-hero h1 { margin:10px 0 6px; font-size:1.8rem; line-height:1.1; }
        .report-hero p { margin:0; color:var(--muted); line-height:1.45; }
        .report-meta, .scope-summary-meta { display:flex; flex-wrap:wrap; gap:8px; }
        .report-meta { margin-top:14px; }
        .report-pill, .table-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; border:1px solid var(--line); background:#fff; font-size:.78rem; font-weight:600; }
        .report-actions { display:flex; gap:8px; }
        .report-action { border:1px solid var(--line); border-radius:12px; padding:10px 14px; background:#fff; color:var(--text); cursor:pointer; font-weight:700; }
        .report-action--primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .report-summary, .report-note, .report-table-wrap { margin-top:18px; padding:18px; }
        .scope-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:12px; }
        .summary-metric { border:1px solid var(--line); border-radius:14px; padding:14px; background:#fff; }
        .summary-metric__label { font-size:.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; }
        .summary-metric__value { margin-top:8px; font-size:1.45rem; font-weight:800; }
        .summary-metric__sub { margin-top:6px; color:var(--muted); font-size:.84rem; }
        .report-note { color:var(--muted); line-height:1.5; }
        .report-table { width:100%; border-collapse:collapse; }
        .report-table th, .report-table td { padding:10px 12px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; font-size:.84rem; }
        .report-table thead th { background:#f8fafc; font-size:.74rem; text-transform:uppercase; letter-spacing:.04em; }
        .report-table tbody tr:last-child td { border-bottom:0; }
        .report-table .btn { display:none !important; }
        .scope-rank-badge { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:28px; border-radius:999px; border:1px solid var(--line); background:#fff; font-size:.75rem; font-weight:800; }
        .scope-rank-badge--top { background:#fef3c7; border-color:#f59e0b; }
        .scope-rank-badge--silver { background:#e2e8f0; }
        .scope-rank-badge--bronze { background:#fed7aa; }
        .scope-row--top { background:rgba(14,165,233,.06); }
        @media print {
            body { padding:0; background:#fff; }
            .report-actions { display:none !important; }
            .report-shell { max-width:none; }
            .report-hero, .report-summary, .report-note, .report-table-wrap, .summary-metric { box-shadow:none !important; }
            .report-table thead { display:table-header-group; }
            .report-table tr { page-break-inside: avoid; }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                try { window.print(); } catch (error) {}
            }, 350);
        });
    <\/script>
</head>
<body>
    <div class="report-shell">
        <section class="report-hero">
            <div class="report-hero__top">
                <div>
                    <div class="report-brand">Apoia Candidato Premium</div>
                    <h1>${escapeHtml(scopeLabel)}</h1>
                    <p>${escapeHtml(campaignLabel)}</p>
                    <div class="report-meta">
                        <span class="report-pill">Tipo: ${escapeHtml(scopeModalState.type === 'region' ? 'RegiÃ£o' : 'Cidade')}</span>
                        <span class="report-pill">Recorte: ${escapeHtml(scopeModalState.name || 'TerritÃ³rio')}</span>
                        <span class="report-pill">Gerado em: ${escapeHtml(generatedAt)}</span>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="report-action report-action--primary" type="button" onclick="window.print()">Imprimir</button>
                    <button class="report-action" type="button" onclick="window.close()">Fechar</button>
                </div>
            </div>
            <div class="report-note">${escapeHtml(subtitle)}</div>
        </section>
        <section class="report-summary">${summaryHtml}</section>
        <section class="report-note">${escapeHtml(note)}</section>
        <section class="report-table-wrap">
            <table class="report-table">
                <thead>${tableHeadHtml}</thead>
                <tbody>${tableBodyHtml}</tbody>
            </table>
        </section>
    </div>
</body>
</html>`;
    }

    function openScopeReport() {
        if (!scopeModal || scopeModal.hidden) {
            return;
        }

        const reportWindow = window.open('', '_blank', 'width=1280,height=900');
        if (!reportWindow) {
            alert('NÃ£o foi possÃ­vel abrir o relatÃ³rio de impressÃ£o. Verifique se o navegador bloqueou a janela.');
            return;
        }

        reportWindow.document.open();
        reportWindow.document.write(buildScopeReportHtml());
        reportWindow.document.close();
        reportWindow.focus();
    }

    function getLeaderSearchCargo() {
        return document.getElementById('searchCargo')?.value || 'Prefeito';
    }

    function getLeaderSearchCheckboxes() {
        if (!resultsBody) {
            return [];
        }

        return Array.from(resultsBody.querySelectorAll('.leader-batch-checkbox'));
    }

    function resetLeaderBatchSelectionState() {
        if (leaderBatchSelectedCount) {
            leaderBatchSelectedCount.textContent = '0 selecionadas';
        }
        if (leaderBatchSubmitBtn) {
            leaderBatchSubmitBtn.disabled = true;
        }
        if (leaderSelectAll) {
            leaderSelectAll.checked = false;
            leaderSelectAll.indeterminate = false;
        }
        if (leaderBatchPayload) {
            leaderBatchPayload.value = '';
        }
        getLeaderSearchCheckboxes().forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.remove('is-selected');
            }
        });
    }

    function updateLeaderBatchSelectionState() {
        const checkboxes = getLeaderSearchCheckboxes();
        const selected = checkboxes.filter((checkbox) => checkbox.checked);

        if (leaderBatchSelectedCount) {
            leaderBatchSelectedCount.textContent = `${selected.length} selecionadas`;
        }

        if (leaderBatchSubmitBtn) {
            leaderBatchSubmitBtn.disabled = selected.length === 0;
        }

        if (leaderSelectAll) {
            leaderSelectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
            leaderSelectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }

        checkboxes.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.toggle('is-selected', checkbox.checked);
            }
        });
    }

    function setLeaderBatchSelection(checked) {
        getLeaderSearchCheckboxes().forEach((checkbox) => {
            checkbox.checked = checked;
        });
        updateLeaderBatchSelectionState();
    }

    function buildLeaderBatchPayload() {
        const cargo = getLeaderSearchCargo();

        return getLeaderSearchCheckboxes()
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => ({
                region_name: checkbox.dataset.regionName || '',
                municipality: checkbox.dataset.municipality || '',
                leader_name: checkbox.dataset.leaderDisplayName || checkbox.dataset.leaderName || '',
                leader_cargo: checkbox.dataset.cargo || cargo,
                leader_party: checkbox.dataset.party || '',
                source_sq_candidato: checkbox.dataset.sq || '',
                source_nr_votavel: checkbox.dataset.nrVotavel || '',
                source_turno: checkbox.dataset.turno || '1',
                leader_votes_2024: checkbox.dataset.votes || '0',
                margin_percent: checkbox.dataset.margin || '0',
                transfer_rate: leaderBatchDefaultTransfer,
                aligned_with_executive: 0,
                visibility_score: 50,
                investment_score: 50,
                size_class: checkbox.dataset.sizeClass || 'medium',
                notes: '',
            }));
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(Number(value || 0));
    }

    function formatSizeLabel(value) {
        if (value === 'small') {
            return 'Pequeno';
        }
        if (value === 'large') {
            return 'Grande';
        }

        return 'MÃ©dio';
    }

    function formatAgendaDate(value) {
        if (!value) {
            return 'Sem prazo';
        }

        const [year, month, day] = String(value).split('-').map((part) => Number(part || 0));
        if (!year || !month || !day) {
            return String(value);
        }

        const date = new Date(Date.UTC(year, month - 1, day));
        return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(date);
    }

    function agendaStatusLabel(status) {
        if (status === 'doing') {
            return 'xm andamento';
        }
        if (status === 'done') {
            return 'ConcluÃ­da';
        }
        if (status === 'archived') {
            return 'Arquivada';
        }

        return 'Aberta';
    }

    function agendaPriorityLabel(priority) {
        if (priority === 'low') {
            return 'Baixa';
        }
        if (priority === 'high') {
            return 'Alta';
        }
        if (priority === 'urgent') {
            return 'Urgente';
        }

        return 'MÃ©dia';
    }

    function agendaFilterLabel(filter) {
        if (filter === 'done') {
            return 'ConcluÃ­das';
        }
        if (filter === 'archived') {
            return 'Arquivadas';
        }

        return 'Pendentes';
    }

    function agendaMatchesFilter(item, filter) {
        const status = String(item?.status || 'open');
        if (filter === 'done') {
            return status === 'done';
        }
        if (filter === 'archived') {
            return status === 'archived';
        }

        return status === 'open' || status === 'doing';
    }

    function setAgendaFilterActive(filter) {
        agendaFilterButtons.forEach((button) => {
            const isActive = String(button.dataset.agendaFilter || '') === String(filter);
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function renderAgendaPreview(filter = 'pending') {
        if (!agendaPreviewArea) {
            return;
        }

        agendaFilter = filter;
        setAgendaFilterActive(filter);

        const filtered = premiumAgenda.filter((item) => agendaMatchesFilter(item, filter));
        const rows = filtered.slice(0, 5);

        if (!rows.length) {
            const emptyMessage = filter === 'done'
                ? 'Ainda nÃ£o hÃ¡ tarefas concluÃ­das.'
                : filter === 'archived'
                    ? 'Ainda nÃ£o hÃ¡ tarefas arquivadas.'
                    : 'NÃ£o hÃ¡ tarefas pendentes no momento.';

            agendaPreviewArea.innerHTML = `<div class="empty-state">${escapeHtml(emptyMessage)} Use outro botÃ£o para trocar a visÃ£o da agenda.</div>`;
            if (agendaPreviewNote) {
                agendaPreviewNote.textContent = 'Nenhuma tarefa para esta visÃ£o.';
            }
            return;
        }

        const html = [];
        html.push('<div class="agenda-mini-list">');

        rows.forEach((item) => {
            const statusClass = String(item.status || 'open');
            const city = escapeHtml(item.municipality || '-');
            const title = escapeHtml(item.title || 'Tarefa');
            const leader = item.leader_name ? ` â€¢ ${escapeHtml(item.leader_name)}` : '';
            html.push('<article class="agenda-mini-card agenda-mini-card--' + escapeHtml(statusClass) + '">');
            html.push('  <div class="agenda-mini-card__main">');
            html.push('    <button type="button" class="agenda-mini-title agenda-open-btn" data-agenda-id="' + escapeHtml(item.id || '') + '">' + title + '</button>');
            html.push('    <div class="agenda-mini-meta">' + city + leader + '</div>');
            html.push('  </div>');
            html.push('  <div class="agenda-mini-card__side">');
            html.push('    <span class="agenda-mini-date">' + escapeHtml(formatAgendaDate(item.due_date || '')) + '</span>');
            html.push('    <button type="button" class="btn ghost btn-small agenda-open-btn" data-agenda-id="' + escapeHtml(item.id || '') + '">Abrir</button>');
            html.push('  </div>');
            html.push('</article>');
        });

        html.push('</div>');
        agendaPreviewArea.innerHTML = html.join('');

        if (agendaPreviewNote) {
            const total = filtered.length;
            const showing = Math.min(total, 5);
            const suffix = total > 5 ? ' Use "Ver todas as tarefas" para abrir a agenda completa.' : '';
            agendaPreviewNote.textContent = `Mostrando ${showing} de ${total} tarefa${total === 1 ? '' : 's'} ${agendaFilterLabel(filter).toLowerCase()}.${suffix}`;
        }
    }

    function openAgendaModal(agendaId, closeListModal = true) {
        if (!agendaModal) {
            return;
        }

        closeScopeModal(false);
        closeCityComparisonModal(false);
        const item = premiumAgenda.find((row) => String(row.id) === String(agendaId));
        if (!item) {
            return;
        }

        if (closeListModal) {
            closeAgendaListModal(false);
        }
        closeLeaderModal(false);

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        set('modalAgendaId', item.id ?? '');
        set('modalAgendaArchiveId', item.id ?? '');
        set('modalAgendaDeleteId', item.id ?? '');
        set('modalAgendaTitleInput', item.title || '');
        set('modalAgendaDueDate', item.due_date || '');
        set('modalAgendaPriority', item.priority || 'medium');
        set('modalAgendaStatus', item.status || 'open');
        set('modalAgendaMunicipality', item.municipality || '');
        set('modalAgendaLeader', item.leader_name || '');
        set('modalAgendaDescription', item.description || '');

        if (agendaModalTitle) {
            agendaModalTitle.textContent = item.title || 'Tarefa';
        }

        if (agendaModalSubtitle) {
            const bits = [
                item.municipality || 'MunicÃ­pio',
                item.leader_name || 'Sem lideranÃ§a',
                agendaStatusLabel(item.status || 'open'),
            ].filter(Boolean);
            agendaModalSubtitle.textContent = bits.join(' â€¢ ');
        }

        if (agendaModalSummary) {
            agendaModalSummary.innerHTML = [
                `<span class="table-pill">${escapeHtml(formatAgendaDate(item.due_date || ''))}</span>`,
                `<span class="table-pill">${escapeHtml(item.municipality || 'Sem municÃ­pio')}</span>`,
                `<span class="table-pill">${escapeHtml(item.leader_name || 'Sem lideranÃ§a')}</span>`,
                `<span class="table-pill">${escapeHtml(agendaStatusLabel(item.status || 'open'))}</span>`,
                `<span class="table-pill">${escapeHtml(agendaPriorityLabel(item.priority || 'medium'))}</span>`,
            ].join('');
        }

        agendaModal.hidden = false;
        agendaModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeAgendaModal(updateBody = true) {
        if (!agendaModal) {
            return;
        }

        agendaModal.hidden = true;
        agendaModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function openAgendaListModal() {
        if (!agendaListModal) {
            return;
        }

        closeScopeModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeLeaderModal(false);

        agendaListModal.hidden = false;
        agendaListModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeAgendaListModal(updateBody = true) {
        if (!agendaListModal) {
            return;
        }

        agendaListModal.hidden = true;
        agendaListModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function openStudyModal() {
        if (!studyModal) {
            return;
        }

        closeLeaderModal(false);
        closeScopeModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);

        studyModal.hidden = false;
        studyModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeStudyModal(updateBody = true) {
        if (!studyModal) {
            return;
        }

        studyModal.hidden = true;
        studyModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function closeAllModals() {
        closeLeaderModal(false);
        closeExternalLeaderModal(false);
        closeScopeModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        closeStudyModal(false);
        document.body.classList.remove('modal-open');
    }

    function syncLeaderRegionFromMunicipality(selectxl, targetId = 'leaderRegion') {
        const regionInput = document.getElementById(targetId);
        if (!regionInput || !selectxl) {
            return;
        }

        const option = selectxl.options?.[selectxl.selectedIndex];
        const regionName = option?.dataset?.region || '';
        regionInput.value = regionName || regionInput.value || '';
    }

    function setSelectValue(selectId, value, extra = {}) {
        const select = document.getElementById(selectId);
        if (!select) {
            return;
        }

        const normalizedValue = String(value ?? '').trim();
        if (normalizedValue === '') {
            select.value = '';
            return;
        }

        let option = Array.from(select.options || []).find((item) => String(item.value).trim() === normalizedValue);
        if (!option) {
            option = document.createElement('option');
            option.value = normalizedValue;
            option.textContent = normalizedValue;

            if (extra.region) {
                option.dataset.region = extra.region;
            }

            select.appendChild(option);
        } else if (extra.region && !option.dataset.region) {
            option.dataset.region = extra.region;
        }

        select.value = normalizedValue;
    }

    function fillLeaderFormFromResult(dataset) {
        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        if (dataset.regionName) {
            set('leaderRegion', dataset.regionName || '');
        }
        setSelectValue('leaderMunicipality', dataset.municipality || '', {
            region: dataset.regionName || '',
        });
        syncLeaderRegionFromMunicipality(document.getElementById('leaderMunicipality'), 'leaderRegion');
        set('leaderName', dataset.leaderDisplayName || dataset.leaderName || '');
        set('leaderCargo', dataset.cargo || '');
        set('leaderParty', dataset.party || '');
        set('leaderVotes', dataset.votes || '0');
        set('leaderMargin', dataset.margin || '0');
        set('leaderTransfer', document.getElementById('leaderTransfer')?.value || '40');
        set('leaderSizeClass', dataset.sizeClass || 'medium');
        set('sourceSq', dataset.sq || '');
        set('sourceNrVotavel', dataset.nrVotavel || '');
        set('sourceTurno', dataset.turno || '1');
        setLeaderMode('add', { scroll: false });
        if (externalLeaderModal) {
            openExternalLeaderModal();
            return;
        }

        const leaderAddBody = document.getElementById('leaderAddBody');
        const leaderAddToggle = document.querySelector('[data-toggle-target="leaderAddBody"]');
        if (leaderAddBody && leaderAddBody.hidden) {
            toggleCollapsiblePanel('leaderAddBody', leaderAddToggle);
        }

        const leaderForm = document.getElementById('leaderForm');
        if (leaderForm && (!leaderAddBody || !leaderAddBody.hidden)) {
            window.scrollTo({ top: leaderForm.offsetTop - 24, behavior: 'smooth' });
        }
    }

    function updateLeaderModalSummary(leader) {
        if (!leaderModalSummary) {
            return;
        }

        leaderModalSummary.innerHTML = [
            `<span class="table-pill">${escapeHtml(leader.region_name || 'Sem regiÃ£o')}</span>`,
            `<span class="table-pill">${escapeHtml(leader.municipality || 'Sem municÃ­pio')}</span>`,
            `<span class="table-pill">${formatNumber(leader.leader_votes_2024 || 0)} votos</span>`,
            `<span class="table-pill">${formatNumber(leader.margin_percent || 0)}% margem</span>`,
            `<span class="table-pill">${formatNumber(leader.transfer_rate || 0)}% transferÃªncia</span>`,
            `<span class="table-pill">${escapeHtml(formatSizeLabel(leader.size_class || 'medium'))}</span>`,
        ].join('');
    }

    function openLeaderModal(leaderId) {
        if (!leaderModal) {
            return;
        }

        closeCityComparisonModal(false);
        closeScopeModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);

        const leader = premiumLeaders.find((item) => String(item.id) === String(leaderId));
        if (!leader) {
            return;
        }

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        if (leaderModalTitle) {
            leaderModalTitle.textContent = leader.leader_display_name || leader.leader_name || 'LideranÃ§a';
        }

        if (leaderModalSubtitle) {
            leaderModalSubtitle.textContent = `${leader.municipality || 'MunicÃ­pio'} â€¢ ${leader.leader_cargo || 'Cargo'}${leader.leader_party ? ` â€¢ ${leader.leader_party}` : ''}`;
        }

        set('modalLeaderId', leader.id ?? '');
        set('modalLeaderRegion', leader.region_name ?? '');
        setSelectValue('modalLeaderMunicipality', leader.municipality ?? '', {
            region: leader.region_name ?? '',
        });
        set('modalLeaderName', leader.leader_display_name ?? leader.leader_name ?? '');
        set('modalLeaderCargo', leader.leader_cargo ?? '');
        set('modalLeaderParty', leader.leader_party ?? '');
        set('modalLeaderVotes', leader.leader_votes_2024 ?? '0');
        set('modalLeaderMargin', leader.margin_percent ?? '0');
        set('modalLeaderTransfer', leader.transfer_rate ?? '40');
        set('modalLeaderVisibility', leader.visibility_score ?? '50');
        set('modalLeaderInvestment', leader.investment_score ?? '50');
        set('modalLeaderSizeClass', leader.size_class ?? 'medium');
        set('modalLeaderNotes', leader.notes ?? '');
        set('modalLeaderDeleteId', leader.id ?? '');

        const aligned = document.getElementById('modalLeaderAligned');
        if (aligned) {
            aligned.checked = Boolean(Number(leader.aligned_with_executive || 0));
        }

        syncLeaderRegionFromMunicipality(document.getElementById('modalLeaderMunicipality'), 'modalLeaderRegion');
        updateLeaderModalSummary(leader);

        leaderModal.hidden = false;
        leaderModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeLeaderModal(updateBody = true) {
        if (!leaderModal) {
            return;
        }

        leaderModal.hidden = true;
        leaderModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function setLeaderMode(mode, options = {}) {
        const normalizedMode = String(mode || '').toLowerCase() === 'consult' ? 'consult' : 'add';
        const shouldScroll = options.scroll !== false;

        if (!leaderModeButtons.length || !leaderModePanels.length) {
            return;
        }

        leaderModeButtons.forEach((button) => {
            const isActive = String(button.dataset.leaderModeTarget || '') === normalizedMode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        leaderModePanels.forEach((panel) => {
            const isActive = String(panel.dataset.leaderModePanel || '') === normalizedMode;
            panel.hidden = !isActive;
        });

        if (shouldScroll) {
            const activePanel = leaderModePanels.find((panel) => !panel.hidden);
            if (activePanel) {
                activePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function setOptionsMode(mode) {
        const requestedMode = String(mode || '').toLowerCase();
        const normalizedMode = ['campaign', 'settings', 'security', 'delete'].includes(requestedMode) ? requestedMode : 'campaign';

        if (!optionsModeButtons.length || !optionsModePanels.length) {
            return;
        }

        optionsModeButtons.forEach((button) => {
            const isActive = String(button.dataset.optionsModeTarget || '') === normalizedMode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        optionsModePanels.forEach((panel) => {
            const isActive = String(panel.dataset.optionsModePanel || '') === normalizedMode;
            panel.hidden = !isActive;
        });
    }

    function openExternalLeaderModal() {
        if (!externalLeaderModal) {
            return;
        }

        closeLeaderModal(false);
        closeCityComparisonModal(false);
        closeScopeModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        closeStudyModal(false);

        externalLeaderModal.hidden = false;
        externalLeaderModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeExternalLeaderModal(updateBody = true) {
        if (!externalLeaderModal) {
            return;
        }

        externalLeaderModal.hidden = true;
        externalLeaderModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function toggleCollapsiblePanel(targetId, triggerButton) {
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        const shouldOpen = target.hidden;
        target.hidden = !shouldOpen;

        const buttons = document.querySelectorAll(`[data-toggle-target="${targetId}"]`);
        buttons.forEach((button) => {
            button.textContent = shouldOpen ? 'Recolher' : 'Abrir';
            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });

        if (shouldOpen) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function focusHashTarget() {
        const hashId = decodeURIComponent(String(window.location.hash || '').replace(/^#/, ''));
        if (!hashId) {
            return;
        }

        if (['leaderSearchBody', 'leaderModeAdd', 'leaderExternalAdd', 'leaderAddBody'].includes(hashId)) {
            setLeaderMode('add', { scroll: false });
            if (hashId === 'leaderExternalAdd' || hashId === 'leaderAddBody') {
                openExternalLeaderModal();
                return;
            }
            const addPanel = document.getElementById('leaderSearchBody');
            if (addPanel) {
                addPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        if (['leadersBody', 'leaderModeConsult'].includes(hashId)) {
            setLeaderMode('consult', { scroll: false });
            const consultPanel = document.getElementById('leadersBody');
            if (consultPanel) {
                consultPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        const target = document.getElementById(hashId);
        if (!target) {
            return;
        }

        if (target.hidden) {
            const triggerButton = document.querySelector(`[data-toggle-target="${hashId}"]`);
            toggleCollapsiblePanel(hashId, triggerButton);
            return;
        }

        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resolveOnboardingStepForContext() {
        if (!onboardingSteps.length) {
            return null;
        }

        const params = new URLSearchParams(window.location.search);
        const tab = String(params.get('tab') || 'home').toLowerCase();
        const hashId = decodeURIComponent(String(window.location.hash || '').replace(/^#/, ''));
        const hashStepMap = {
            campaignCreatePanel: 0,
            baselineBody: 0,
            settingsBody: 1,
            leaderSearchBody: 2,
            leaderModeAdd: 2,
            leaderModeConsult: 2,
            leaderExternalAdd: 2,
            leaderAddBody: 2,
            leadersBody: 2,
            agendaPanel: 3,
            reportsPanel: 4,
        };
        const tabStepMap = {
            home: 0,
            opcoes: 0,
            liderancas: 2,
            agenda: 3,
            relatorios: 4,
        };

        if (hashId && Object.prototype.hasOwnProperty.call(hashStepMap, hashId)) {
            return hashStepMap[hashId];
        }

        if (Object.prototype.hasOwnProperty.call(tabStepMap, tab)) {
            return tabStepMap[tab];
        }

        return null;
    }

    window.addEventListener('hashchange', () => {
        focusHashTarget();
        syncOnboardingGuide();
    });

    function loadOnboardingState() {
        const fallback = {
            step: 0,
            hidden: false,
            completed: false,
            campaignStartRequested: false,
        };

        try {
            const raw = window.localStorage.getItem(onboardingStorageKey);
            if (!raw) {
                return fallback;
            }

            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                return fallback;
            }

            return {
                step: Number.isFinite(Number(parsed.step)) ? Number(parsed.step) : fallback.step,
                hidden: Boolean(parsed.hidden),
                completed: Boolean(parsed.completed),
                campaignStartRequested: Boolean(parsed.campaignStartRequested),
            };
        } catch (error) {
            return fallback;
        }
    }

    function saveOnboardingState(nextState) {
        try {
            window.localStorage.setItem(onboardingStorageKey, JSON.stringify(nextState));
        } catch (error) {
            // Ignore storage failures and keep the guide functional for this session.
        }
    }

    let onboardingState = loadOnboardingState();

    function updateOnboardingToggleButtons() {
        const label = onboardingState.hidden ? 'Abrir guia' : 'Ocultar guia';
        onboardingToggleButtons.forEach((button) => {
            button.textContent = label;
            button.setAttribute('aria-pressed', onboardingState.hidden ? 'false' : 'true');
        });
    }

    function renderOnboardingStep(stepIndex) {
        if (!onboardingRoot || !onboardingSteps.length) {
            return;
        }

        const safeIndex = Math.max(0, Math.min(stepIndex, onboardingSteps.length - 1));
        const step = onboardingSteps[safeIndex] || onboardingSteps[0];
        if (!step) {
            return;
        }

        if (onboardingStepCounter) {
            onboardingStepCounter.textContent = `${safeIndex + 1}/${onboardingSteps.length}`;
        }

        if (onboardingStepStatus) {
            onboardingStepStatus.textContent = step.statusLabel || step.title || 'Comece por aqui';
        }

        if (onboardingProgressFill) {
            onboardingProgressFill.style.width = `${Math.min(100, ((safeIndex + 1) / onboardingSteps.length) * 100)}%`;
        }

        if (onboardingStepNumber) {
            onboardingStepNumber.textContent = step.number || String(safeIndex + 1);
        }

        if (onboardingStepTitle) {
            onboardingStepTitle.textContent = step.title || 'Guia rÃ¡pido';
        }

        if (onboardingStepCopy) {
            onboardingStepCopy.innerHTML = step.descriptionHtml || '';
        }

        if (onboardingStepAction) {
            onboardingStepAction.textContent = step.buttonLabel || 'Abrir';
            onboardingStepAction.setAttribute('href', step.href || '#');
            onboardingStepAction.dataset.onboardingIndex = String(safeIndex);
        }
    }

    function syncOnboardingGuide() {
        if (!onboardingRoot || !onboardingSteps.length) {
            return;
        }

        const contextualStep = resolveOnboardingStepForContext();

        if (contextualStep !== null) {
            onboardingState.step = Math.max(0, Math.min(contextualStep, onboardingSteps.length - 1));
            onboardingState.completed = false;
        }

        if (!onboardingData.hasCampaign) {
            onboardingState.step = 0;
            onboardingState.completed = false;
        } else if (onboardingState.campaignStartRequested && onboardingState.step === 0) {
            onboardingState.step = 1;
            onboardingState.campaignStartRequested = false;
        }

        if (onboardingState.step >= onboardingSteps.length) {
            onboardingState.step = onboardingSteps.length - 1;
            onboardingState.completed = true;
            onboardingState.hidden = true;
        }

        if (onboardingState.completed) {
            onboardingState.hidden = true;
        }

        onboardingRoot.hidden = Boolean(onboardingState.hidden);
        if (!onboardingState.hidden) {
            renderOnboardingStep(onboardingState.step);
        }
        updateOnboardingToggleButtons();
        saveOnboardingState(onboardingState);
    }

    function hideOnboardingGuide(completed = false) {
        onboardingState.hidden = true;
        onboardingState.completed = Boolean(completed);
        saveOnboardingState(onboardingState);
        if (onboardingRoot) {
            onboardingRoot.hidden = true;
        }
        updateOnboardingToggleButtons();
    }

    function showOnboardingGuide() {
        onboardingState.hidden = false;
        onboardingState.completed = false;
        onboardingState.step = 0;
        if (onboardingRoot) {
            onboardingRoot.hidden = false;
        }
        renderOnboardingStep(onboardingState.step);
        updateOnboardingToggleButtons();
        saveOnboardingState(onboardingState);
    }

    function advanceOnboardingGuide() {
        if (!onboardingSteps.length) {
            return;
        }

        if (!onboardingData.hasCampaign && onboardingState.step === 0) {
            onboardingState.campaignStartRequested = true;
            saveOnboardingState(onboardingState);
            return;
        }

        if (onboardingState.step >= onboardingSteps.length - 1) {
            hideOnboardingGuide(true);
            return;
        }

        onboardingState.step += 1;
        onboardingState.completed = false;
        onboardingState.hidden = false;
        renderOnboardingStep(onboardingState.step);
        updateOnboardingToggleButtons();
        saveOnboardingState(onboardingState);
    }

    syncOnboardingGuide();

    async function searchLeaders(options = {}) {
        if (!resultsBody) {
            return;
        }

        const fromTyping = Boolean(options.fromTyping);
        const cargoValue = getLeaderSearchCargo();
        const municipalityValue = document.getElementById('searchMunicipality')?.value || '';
        const normalizedQuery = String(searchQueryInput?.value || '').trim();

        if (fromTyping && normalizedQuery.length < 2) {
            resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Digite pelo menos 2 letras para buscar nomes.</td></tr>';
            resetLeaderBatchSelectionState();
            return;
        }

        if (leaderBatchCargo) {
            leaderBatchCargo.value = cargoValue;
        }

        resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Buscando...</td></tr>';
        resetLeaderBatchSelectionState();

        if (leaderSearchAbortController) {
            leaderSearchAbortController.abort();
        }
        leaderSearchAbortController = new AbortController();

        const params = new URLSearchParams({
            action: 'search_leaders',
            cargo: cargoValue,
            municipio: municipalityValue,
            query: normalizedQuery,
        });

        try {
            const response = await fetch('api_premium.php?' + params.toString(), {
                cache: 'no-store',
                signal: leaderSearchAbortController.signal,
            });
            const data = await response.json();
            const rows = data.results || [];

            if (!rows.length) {
                resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Nenhuma liderança encontrada.</td></tr>';
                resetLeaderBatchSelectionState();
                return;
            }

            resultsBody.innerHTML = rows.map((row) => `
                <tr class="leader-search-row">
                    <td>
                        <input
                            type="checkbox"
                            class="leader-batch-checkbox"
                            aria-label="Selecionar liderança"
                            data-region-name="${escapeHtml(row.region_name || '')}"
                            data-municipality="${escapeHtml(row.nm_municipio || '')}"
                            data-leader-display-name="${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel || '')}"
                            data-leader-name="${escapeHtml(row.nm_candidato || row.nm_votavel || '')}"
                            data-cargo="${escapeHtml(cargoValue)}"
                            data-party="${escapeHtml(row.sg_partido || '')}"
                            data-votes="${escapeHtml(row.total_votos || 0)}"
                            data-margin="${escapeHtml(row.margin_percent || 0)}"
                            data-size-class="${escapeHtml(row.size_class || 'medium')}"
                            data-sq="${escapeHtml(row.sq_candidato || '')}"
                            data-nr-votavel="${escapeHtml(row.nr_votavel || '')}"
                            data-turno="${escapeHtml(row.turno || 1)}"
                        >
                    </td>
                    <td>${escapeHtml(row.nm_municipio)}</td>
                    <td>${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel)}</td>
                    <td>${escapeHtml(row.sg_partido || '-')}</td>
                    <td>${formatNumber(row.total_votos)}</td>
                    <td>${formatNumber(row.margin_percent)}%</td>
                    <td>
                        <button
                            type="button"
                            class="btn ghost"
                            data-region-name="${escapeHtml(row.region_name || '')}"
                            data-municipality="${escapeHtml(row.nm_municipio || '')}"
                            data-leader-display-name="${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel || '')}"
                            data-leader-name="${escapeHtml(row.nm_candidato || row.nm_votavel || '')}"
                            data-cargo="${escapeHtml(cargoValue)}"
                            data-party="${escapeHtml(row.sg_partido || '')}"
                            data-votes="${escapeHtml(row.total_votos || 0)}"
                            data-margin="${escapeHtml(row.margin_percent || 0)}"
                            data-size-class="${escapeHtml(row.size_class || 'medium')}"
                            data-sq="${escapeHtml(row.sq_candidato || '')}"
                            data-nr-votavel="${escapeHtml(row.nr_votavel || '')}"
                            data-turno="${escapeHtml(row.turno || 1)}"
                        >Add</button>
                    </td>
                </tr>
            `).join('');
            updateLeaderBatchSelectionState();
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            console.error(error);
            resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Falha ao buscar lideranças.</td></tr>';
            resetLeaderBatchSelectionState();
        } finally {
            leaderSearchAbortController = null;
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            searchLeaders({ fromTyping: false });
        });
    }

    function scheduleTypedLeaderSearch() {
        if (!searchQueryInput) {
            return;
        }

        if (leaderSearchDebounceId) {
            window.clearTimeout(leaderSearchDebounceId);
        }

        leaderSearchDebounceId = window.setTimeout(() => {
            searchLeaders({ fromTyping: true });
        }, 280);
    }

    if (searchQueryInput) {
        searchQueryInput.addEventListener('input', scheduleTypedLeaderSearch);
    }

    const searchMunicipalityInput = document.getElementById('searchMunicipality');
    if (searchMunicipalityInput) {
        searchMunicipalityInput.addEventListener('change', () => {
            const hasTypedQuery = String(searchQueryInput?.value || '').trim().length >= 2;
            searchLeaders({ fromTyping: hasTypedQuery });
        });
    }

    if (resultsBody) {
        resultsBody.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-leader-name]');
            if (!button) {
                return;
            }

            fillLeaderFormFromResult(button.dataset);
        });

        resultsBody.addEventListener('change', (event) => {
            if (!event.target.classList.contains('leader-batch-checkbox')) {
                return;
            }

            updateLeaderBatchSelectionState();
        });
    }

    const leaderMunicipality = document.getElementById('leaderMunicipality');
    if (leaderMunicipality) {
        leaderMunicipality.addEventListener('change', () => syncLeaderRegionFromMunicipality(leaderMunicipality));
        syncLeaderRegionFromMunicipality(leaderMunicipality);
    }

    if (leaderSelectAll) {
        leaderSelectAll.addEventListener('change', () => {
            setLeaderBatchSelection(Boolean(leaderSelectAll.checked));
        });
    }

    if (leaderBatchSelectAllBtn) {
        leaderBatchSelectAllBtn.addEventListener('click', () => {
            setLeaderBatchSelection(true);
        });
    }

    if (leaderBatchClearBtn) {
        leaderBatchClearBtn.addEventListener('click', () => {
            setLeaderBatchSelection(false);
        });
    }

    if (leaderBatchForm) {
        leaderBatchForm.addEventListener('submit', (event) => {
            const payload = buildLeaderBatchPayload();
            if (!payload.length) {
                event.preventDefault();
                alert('Selecione pelo menos uma lideranÃ§a antes de adicionar ao escritÃ³rio.');
                return;
            }

            if (leaderBatchPayload) {
                leaderBatchPayload.value = JSON.stringify(payload);
            }

            const countLabel = payload.length === 1 ? '1 lideranÃ§a' : `${payload.length} lideranÃ§as`;
            const confirmed = window.confirm(`Adicionar ${countLabel} usando os pesos padrÃ£o da campanha?`);
            if (!confirmed) {
                event.preventDefault();
                return;
            }
        });
    }

    if (agendaFilterButtons.length) {
        agendaFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                renderAgendaPreview(button.dataset.agendaFilter || 'pending');
            });
        });
    }

    if (agendaPreviewArea) {
        renderAgendaPreview(agendaFilter);
    }

    if (activeLeadersCityFilter) {
        activeLeadersCityFilter.addEventListener('change', applyActiveLeadersFilters);
    }

    if (activeLeadersTypeFilter) {
        activeLeadersTypeFilter.addEventListener('change', applyActiveLeadersFilters);
    }

    if (activeLeadersPartyFilter) {
        activeLeadersPartyFilter.addEventListener('change', applyActiveLeadersFilters);
    }

    if (activeLeadersResetBtn) {
        activeLeadersResetBtn.addEventListener('click', () => {
            if (activeLeadersCityFilter) {
                activeLeadersCityFilter.value = '';
            }
            if (activeLeadersTypeFilter) {
                activeLeadersTypeFilter.value = '';
            }
            if (activeLeadersPartyFilter) {
                activeLeadersPartyFilter.value = '';
            }
            applyActiveLeadersFilters();
        });
    }

    if (activeLeadersSelectAll) {
        activeLeadersSelectAll.addEventListener('change', () => {
            setActiveLeaderBulkSelection(Boolean(activeLeadersSelectAll.checked), true);
        });
    }

    if (activeLeadersRowsViewport) {
        activeLeadersRowsViewport.addEventListener('change', (event) => {
            if (!event.target.classList.contains('leader-bulk-checkbox')) {
                return;
            }

            updateActiveLeaderBulkSelectionState();
        });
    }

    if (leaderBulkTransferForm) {
        [leaderBulkTransferSelectedBtn, leaderBulkTransferVisibleBtn, leaderBulkTransferAllBtn].forEach((button) => {
            if (!button) {
                return;
            }

            button.addEventListener('click', () => {
                leaderBulkTransferForm.dataset.bulkTransferScope = button.dataset.bulkTransferScope || 'selected';
            });
        });

        leaderBulkTransferForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const scope = String(submitter?.dataset?.bulkTransferScope || leaderBulkTransferForm.dataset.bulkTransferScope || 'selected');
            const transferRate = Number.parseFloat(leaderBulkTransferValue?.value || '');

            if (!Number.isFinite(transferRate) || transferRate < 0 || transferRate > 100) {
                event.preventDefault();
                alert('Informe uma transferÃªncia entre 0 e 100.');
                return;
            }

            const payload = buildActiveLeaderBulkTransferPayload(scope);
            if (!payload.length) {
                event.preventDefault();
                alert(scope === 'all'
                    ? 'NÃ£o hÃ¡ lideranÃ§as na campanha para atualizar.'
                    : 'Selecione pelo menos uma lideranÃ§a antes de alterar a transferÃªncia.');
                return;
            }

            if (leaderBulkTransferScope) {
                leaderBulkTransferScope.value = scope;
            }
            if (leaderBulkTransferPayload) {
                leaderBulkTransferPayload.value = JSON.stringify(payload);
            }

            const scopeLabel = scope === 'all'
                ? 'todas as lideranÃ§as da campanha'
                : scope === 'visible'
                    ? 'as lideranÃ§as visÃ­veis'
                    : 'as lideranÃ§as selecionadas';
            const countLabel = payload.length === 1 ? '1 lideranÃ§a' : `${payload.length} lideranÃ§as`;
            const confirmed = window.confirm(`Aplicar ${formatNumber(transferRate)}% para ${countLabel} (${scopeLabel})?`);
            if (!confirmed) {
                event.preventDefault();
            }
        });
    }

    if (leaderBulkSelectVisibleBtn) {
        leaderBulkSelectVisibleBtn.addEventListener('click', () => {
            setActiveLeaderBulkSelection(true, true);
        });
    }

    if (leaderBulkClearBtn) {
        leaderBulkClearBtn.addEventListener('click', () => {
            setActiveLeaderBulkSelection(false, false);
        });
    }

    if (leaderBulkDeleteForm) {
        leaderBulkDeleteForm.addEventListener('submit', (event) => {
            const payload = buildActiveLeaderBulkDeletePayload();
            if (!payload.length) {
                event.preventDefault();
                alert('Selecione pelo menos uma lideranÃ§a antes de excluir.');
                return;
            }

            if (leaderBulkDeletePayload) {
                leaderBulkDeletePayload.value = JSON.stringify(payload);
            }

            const countLabel = payload.length === 1 ? '1 lideranÃ§a' : `${payload.length} lideranÃ§as`;
            const confirmed = window.confirm(`Excluir ${countLabel} da campanha? Esta aÃ§Ã£o nÃ£o pode ser desfeita.`);
            if (!confirmed) {
                event.preventDefault();
            }
        });
    }

    setLeaderMode('add', { scroll: false });
    setOptionsMode('campaign');
    applyActiveLeadersFilters();
    focusHashTarget();

    document.addEventListener('click', (event) => {
        const onboardingAction = event.target.closest('[data-onboarding-step-action]');
        if (onboardingAction) {
            advanceOnboardingGuide();
            return;
        }

        const onboardingToggle = event.target.closest('[data-onboarding-toggle]');
        if (onboardingToggle) {
            if (onboardingState.hidden) {
                showOnboardingGuide();
            } else {
                hideOnboardingGuide(Boolean(onboardingState.completed));
            }
            return;
        }

        const studyButton = event.target.closest('[data-study-open]');
        if (studyButton) {
            openStudyModal();
            return;
        }

        const leaderModeButton = event.target.closest('[data-leader-mode-target]');
        if (leaderModeButton) {
            setLeaderMode(leaderModeButton.dataset.leaderModeTarget || 'add');
            return;
        }

        const optionsModeButton = event.target.closest('[data-options-mode-target]');
        if (optionsModeButton) {
            setOptionsMode(optionsModeButton.dataset.optionsModeTarget || 'campaign');
            return;
        }

        const externalLeaderButton = event.target.closest('[data-external-leader-open]');
        if (externalLeaderButton) {
            setLeaderMode('add', { scroll: false });
            openExternalLeaderModal();
            return;
        }

        const toggleButton = event.target.closest('[data-toggle-target]');
        if (toggleButton) {
            toggleCollapsiblePanel(toggleButton.dataset.toggleTarget || '', toggleButton);
            return;
        }

        const scopeButton = event.target.closest('.scope-open-btn');
        if (scopeButton) {
            openScopeModal(scopeButton.dataset.scopeType || 'city', scopeButton.dataset.scopeName || '');
            return;
        }

        const cityComparisonOpenButton = event.target.closest('[data-city-comparison-open]');
        if (cityComparisonOpenButton) {
            openCityComparisonModal();
            return;
        }

        const cityComparisonPrintButton = event.target.closest('[data-city-comparison-print]');
        if (cityComparisonPrintButton) {
            openCityComparisonReport();
            return;
        }

        const scopePrintButton = event.target.closest('[data-scope-print]');
        if (scopePrintButton) {
            openScopeReport();
            return;
        }

        const cityComparisonFilterButton = event.target.closest('[data-city-comparison-filter]');
        if (cityComparisonFilterButton) {
            applyCityComparisonFilter(cityComparisonFilterButton.dataset.cityComparisonFilter || 'all');
            return;
        }

        const agendaListButton = event.target.closest('[data-agenda-list-open]');
        if (agendaListButton) {
            openAgendaListModal();
            return;
        }

        const agendaButton = event.target.closest('[data-agenda-id]');
        if (agendaButton) {
            openAgendaModal(agendaButton.dataset.agendaId);
            return;
        }

        const leaderButton = event.target.closest('button[data-leader-id]');
        if (leaderButton) {
            openLeaderModal(leaderButton.dataset.leaderId);
            return;
        }

        if (event.target.closest('[data-modal-close]')) {
            closeAllModals();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            (
                (leaderModal && !leaderModal.hidden) ||
                (externalLeaderModal && !externalLeaderModal.hidden) ||
                (scopeModal && !scopeModal.hidden) ||
                (cityComparisonModal && !cityComparisonModal.hidden) ||
                (agendaModal && !agendaModal.hidden) ||
                (agendaListModal && !agendaListModal.hidden) ||
                (studyModal && !studyModal.hidden)
            )
        ) {
            closeAllModals();
        }
    });
