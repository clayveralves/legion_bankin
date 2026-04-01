$(function () {
    let lookupController;
    let accountLookupValid = false;
    let pendingConfirmationForm = null;
    const dashboardScrollStorageKey = 'legion-bankin.dashboard.scroll-y';

    const confirmationModalElement = document.getElementById('transactionConfirmationModal');
    const profileModalElement = document.getElementById('profileDetailsModal');
    const operationsHistorySection = document.getElementById('operations-history-section');
    const recipientAccountInput = document.getElementById('recipient_account');

    if (!confirmationModalElement || !operationsHistorySection || !recipientAccountInput) {
        return;
    }

    const operationsAccordionStorageKey = operationsHistorySection.dataset.accordionStorageKey || 'legion-bankin.operations-history-filters.open';
    const hasOperationFilters = operationsHistorySection.dataset.hasFilters === 'true';
    const isAccountAvailable = operationsHistorySection.dataset.accountAvailable === 'true';
    const accountLookupUrl = recipientAccountInput.dataset.lookupUrl;

    const confirmationModal = new bootstrap.Modal(confirmationModalElement);
    const profileModal = profileModalElement ? new bootstrap.Modal(profileModalElement) : null;
    const confirmationPasswordInput = document.getElementById('transaction-confirmation-password');
    const confirmationTitle = document.getElementById('transactionConfirmationModalLabel');
    const confirmationMessage = document.getElementById('transactionConfirmationMessage');
    const confirmationSummary = document.getElementById('transactionConfirmationSummary');
    const confirmationSubmitButton = document.getElementById('transaction-confirmation-submit');
    const profileInfoFields = document.getElementById('profile-info-fields');
    const profilePasswordFields = document.getElementById('profile-password-fields');
    const profileSavePanel = document.getElementById('profile-save-panel');
    const profileSaveButton = document.getElementById('profile-save-button');
    const profileEditInfoInput = document.getElementById('profile_edit_info');
    const profileEditPasswordInput = document.getElementById('profile_edit_password');
    const profileInfoToggleButton = document.getElementById('toggle-profile-info');
    const profilePasswordToggleButton = document.getElementById('toggle-profile-password');
    const profileNameInput = document.getElementById('profile_name');
    const profileEmailInput = document.getElementById('profile_email');
    const profilePasswordInput = document.getElementById('profile_password');
    const profilePasswordConfirmationInput = document.getElementById('profile_password_confirmation');
    const profileCurrentPasswordInput = document.getElementById('profile_current_password');
    const profileAccountStatusInput = document.getElementById('profile_account_status');
    const profileAccountStatusToggle = document.getElementById('profile_account_status_toggle');
    const profileEditStatusInput = document.getElementById('profile_edit_status');
    const profileStatusFields = document.getElementById('profile-status-fields');
    const profileStatusToggleButton = document.getElementById('toggle-profile-status');
    const profileDeleteFields = document.getElementById('profile-delete-fields');
    const profileDeleteToggleButton = document.getElementById('toggle-profile-delete');
    const profileDeleteCancelButton = document.getElementById('cancel-profile-delete');
    const profileDeletePasswordInput = document.getElementById('delete_current_password');

    // Guarda a posicao atual da pagina antes de um submit que recarrega o dashboard.
    function persistDashboardScroll() {
        window.sessionStorage.setItem(dashboardScrollStorageKey, String(window.scrollY));
    }

    // Restaura a posicao anterior da pagina depois do recarregamento.
    function restoreDashboardScroll() {
        const savedScroll = window.sessionStorage.getItem(dashboardScrollStorageKey);

        if (savedScroll === null) {
            return;
        }

        window.sessionStorage.removeItem(dashboardScrollStorageKey);
        window.requestAnimationFrame(() => {
            window.scrollTo({ top: Number(savedScroll), behavior: 'auto' });
        });
    }

    // Escapa texto dinamico antes de montar HTML do modal de confirmacao.
    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // Reaplica o estado aberto ou fechado do acordeao de filtros do extrato.
    function syncOperationsAccordionState() {
        const collapseElement = document.getElementById('operationsHistoryFiltersCollapse');

        if (!collapseElement) {
            return;
        }

        const shouldBeOpen = window.localStorage.getItem(operationsAccordionStorageKey) === 'true';
        const collapseInstance = bootstrap.Collapse.getOrCreateInstance(collapseElement, { toggle: false });

        if (shouldBeOpen) {
            collapseInstance.show();
            return;
        }

        collapseInstance.hide();
    }

    // Alterna a interface entre transferencia por amigo e por numero de conta.
    function toggleTransferOptions() {
        const recipientType = $('input[name="recipient_type"]:checked').val();
        $('.transfer-option-friend').toggle(recipientType === 'friend');
        $('.transfer-option-account').toggle(recipientType === 'account');

        if (recipientType === 'friend') {
            updateFriendPreview();
        }

        if (recipientType === 'account') {
            lookupAccountHolder();
        } else {
            clearAccountFeedback();
        }

        updateTransferSubmitState();
    }

    // Exibe os dados do amigo selecionado e replica sua conta no campo auxiliar.
    function updateFriendPreview() {
        const selectedOption = $('#recipient_friend_id option:selected');
        const preview = $('#friend-transfer-preview');

        if (!selectedOption.val()) {
            preview.addClass('d-none').text('');
            updateTransferSubmitState();
            return;
        }

        const friendName = selectedOption.data('name');
        const friendEmail = selectedOption.data('email');
        const accountFormatted = selectedOption.data('account-formatted');
        const accountId = selectedOption.data('account');

        $('#recipient_account').val(accountId);
        preview
            .removeClass('d-none')
            .text(`Destinatario selecionado: ${friendName} · ${friendEmail} · Conta ${accountFormatted}`);

        updateTransferSubmitState();
    }

    // Mostra o retorno visual da consulta de conta com o estilo correspondente.
    function showAccountFeedback(message, type) {
        const feedback = $('#account-holder-feedback');
        feedback
            .removeClass('d-none alert-info alert-success alert-warning alert-danger')
            .addClass(`alert-${type}`)
            .text(message);
    }

    // Limpa o estado visual e logico da consulta de conta.
    function clearAccountFeedback() {
        accountLookupValid = false;
        $('#account-holder-feedback')
            .addClass('d-none')
            .removeClass('alert-info alert-success alert-warning alert-danger')
            .text('');

        updateTransferSubmitState();
    }

    // Atualiza apenas a secao do extrato sem recarregar a pagina inteira.
    async function updateOperationsHistory(url, shouldPushState = true) {
        if (!isAccountAvailable) {
            return;
        }

        if (!url) {
            return;
        }

        operationsHistorySection.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Falha ao atualizar o extrato.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const documentHtml = parser.parseFromString(html, 'text/html');
            const nextHistorySection = documentHtml.getElementById('operations-history-section');

            if (!nextHistorySection) {
                throw new Error('Extrato nao encontrado na resposta.');
            }

            operationsHistorySection.innerHTML = nextHistorySection.innerHTML;
            operationsHistorySection.dataset.hasFilters = nextHistorySection.dataset.hasFilters || 'false';

            if (shouldPushState) {
                window.history.pushState({ operationsUrl: url }, '', url);
            }

            syncOperationsAccordionState();
        } catch (error) {
            window.location.href = url;
        } finally {
            operationsHistorySection.classList.remove('is-loading');
        }
    }

    // Habilita o botao de transferencia somente quando destinatario e valor sao validos.
    function updateTransferSubmitState() {
        const recipientType = $('input[name="recipient_type"]:checked').val();
        const amount = $('#transfer-amount').val();
        const hasAmount = amount !== '' && Number(amount) > 0;

        let recipientValid = false;

        if (recipientType === 'friend') {
            recipientValid = $('#recipient_friend_id').val() !== '';
        }

        if (recipientType === 'account') {
            recipientValid = accountLookupValid;
        }

        $('#transfer-submit').prop('disabled', !(recipientValid && hasAmount));
    }

    // Abre o modal de confirmacao e carrega o contexto da operacao selecionada.
    function openConfirmationModal(form) {
        pendingConfirmationForm = form;
        const operationKind = form.dataset.operationKind || 'transfer';

        confirmationTitle.textContent = form.dataset.confirmationTitle || 'Confirmar operacao';
        confirmationMessage.textContent = form.dataset.confirmationMessage || 'Informe sua senha para continuar.';
        confirmationSummary.innerHTML = buildConfirmationSummary(form);
        confirmationSummary.classList.toggle('d-none', confirmationSummary.innerHTML === '');
        confirmationSubmitButton.classList.toggle('btn-primary', operationKind === 'transfer');
        confirmationSubmitButton.classList.toggle('btn-danger', operationKind === 'reversal');
        confirmationPasswordInput.value = '';
        confirmationModal.show();
        window.setTimeout(() => confirmationPasswordInput.focus(), 200);
    }

    // Monta o resumo exibido no modal para transferencia ou reversao.
    function buildConfirmationSummary(form) {
        const operationKind = form.dataset.operationKind;

        if (operationKind === 'transfer') {
            const recipientType = $('input[name="recipient_type"]:checked').val();
            const amount = $('#transfer-amount').val();
            const description = $('#transfer-description').val() || 'Sem descricao adicional.';

            let recipientLabel = 'Destinatario nao informado';

            if (recipientType === 'friend') {
                const selectedOption = $('#recipient_friend_id option:selected');

                if (selectedOption.val()) {
                    recipientLabel = `${selectedOption.data('name')} · ${selectedOption.data('email')} · Conta ${selectedOption.data('account-formatted')}`;
                }
            }

            if (recipientType === 'account') {
                recipientLabel = $('#account-holder-feedback').hasClass('d-none')
                    ? `Conta ${$('#recipient_account').val() || 'nao informada'}`
                    : $('#account-holder-feedback').text();
            }

            return `
                <div class="confirmation-summary-title">Resumo da transferencia</div>
                <div class="confirmation-summary-grid confirmation-summary-transfer">
                    <div><strong>Destinatario:</strong> ${escapeHtml(recipientLabel)}</div>
                    <div class="confirmation-summary-amount text-primary">R$ ${escapeHtml(amount || '0,00')}</div>
                    <div><strong>Descricao:</strong> ${escapeHtml(description)}</div>
                </div>
            `;
        }

        if (operationKind === 'reversal') {
            const reason = form.querySelector('input[name="reason"]')?.value || 'Sem motivo informado.';
            const title = form.dataset.summaryTitle || 'Operacao';
            const amount = form.dataset.summaryAmount || 'N/D';
            const description = form.dataset.summaryDescription || 'Sem descricao adicional.';

            return `
                <div class="confirmation-summary-title">Resumo da reversao</div>
                <div class="confirmation-summary-grid confirmation-summary-reversal">
                    <div><strong>Operacao:</strong> ${escapeHtml(title)}</div>
                    <div class="confirmation-summary-amount text-danger">${escapeHtml(amount)}</div>
                    <div><strong>Descricao:</strong> ${escapeHtml(description)}</div>
                    <div><strong>Motivo:</strong> ${escapeHtml(reason)}</div>
                </div>
            `;
        }

        return '';
    }

    // Consulta a API local para identificar o titular da conta digitada.
    function lookupAccountHolder() {
        const account = $('#recipient_account').val();

        if (!account) {
            clearAccountFeedback();
            return;
        }

        if (lookupController) {
            lookupController.abort();
        }

        lookupController = new AbortController();

        fetch(`${accountLookupUrl}?account=${account}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            signal: lookupController.signal
        })
            .then(async (response) => {
                const data = await response.json();

                if (!response.ok) {
                    throw data;
                }

                if (!data.found) {
                    accountLookupValid = false;
                    showAccountFeedback(data.message || 'Conta nao encontrada.', 'danger');
                    updateTransferSubmitState();
                    return;
                }

                if (data.is_own_account) {
                    accountLookupValid = false;
                    showAccountFeedback(`Conta ${data.account_number} pertence a ${data.holder_name} · ${data.holder_email}.`, 'warning');
                    updateTransferSubmitState();
                    return;
                }

                accountLookupValid = true;
                showAccountFeedback(`Titular localizado: ${data.holder_name} · ${data.holder_email} · Conta ${data.account_number}`, 'success');
                updateTransferSubmitState();
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                accountLookupValid = false;
                showAccountFeedback(error.message || 'Conta nao encontrada.', 'danger');
                updateTransferSubmitState();
            });
    }

    // Alterna os blocos editaveis do perfil e exibe o envio apenas quando existir alguma edicao ativa.
    function syncProfileEditorState() {
        if (!profileInfoFields || !profilePasswordFields || !profileSavePanel || !profileSaveButton || !profileStatusFields) {
            return;
        }

        const isInfoOpen = profileEditInfoInput?.value === '1';
        const isPasswordOpen = profileEditPasswordInput?.value === '1';
        const hasStatusChange = profileEditStatusInput?.value === '1';
        const isStatusOpen = !profileStatusFields.classList.contains('d-none');
        const hasOpenSection = isInfoOpen || isPasswordOpen || hasStatusChange;

        profileInfoFields.classList.toggle('d-none', !isInfoOpen);
        profilePasswordFields.classList.toggle('d-none', !isPasswordOpen);
        profileSavePanel.classList.toggle('d-none', !hasOpenSection);
        profileSaveButton.classList.toggle('d-none', !hasOpenSection);

        profileInfoToggleButton?.classList.toggle('active', isInfoOpen);
        profilePasswordToggleButton?.classList.toggle('active', isPasswordOpen);
        profileInfoToggleButton?.setAttribute('aria-expanded', String(isInfoOpen));
        profilePasswordToggleButton?.setAttribute('aria-expanded', String(isPasswordOpen));

        if (profileInfoToggleButton) {
            profileInfoToggleButton.textContent = isInfoOpen ? 'Cancelar alteração das informações' : 'Alterar informações';
        }

        if (profilePasswordToggleButton) {
            profilePasswordToggleButton.textContent = isPasswordOpen ? 'Cancelar alteração de senha' : 'Alterar senha';
        }

        if (profileStatusToggleButton) {
            profileStatusToggleButton.classList.toggle('active', isStatusOpen);
            profileStatusToggleButton.setAttribute('aria-expanded', String(isStatusOpen));
            profileStatusToggleButton.textContent = isStatusOpen ? 'Cancelar alteração de status' : 'Alterar status';
        }

        if (profileDeleteToggleButton && profileDeleteFields) {
            const isDeleteOpen = !profileDeleteFields.classList.contains('d-none');
            profileDeleteToggleButton.classList.toggle('active', isDeleteOpen);
            profileDeleteToggleButton.setAttribute('aria-expanded', String(isDeleteOpen));
            profileDeleteToggleButton.textContent = isDeleteOpen ? 'Cancelar exclusão da conta' : 'Excluir conta';
        }

        if (!hasOpenSection && profileCurrentPasswordInput) {
            profileCurrentPasswordInput.value = '';
        }
    }

    // Marca se o status atual difere do valor original configurado para a conta.
    function syncProfileStatusEditor() {
        if (!profileAccountStatusInput || !profileAccountStatusToggle || !profileEditStatusInput) {
            return;
        }

        profileAccountStatusInput.value = profileAccountStatusToggle.checked ? 'active' : 'inactive';
        profileEditStatusInput.value = profileAccountStatusInput.value !== profileAccountStatusInput.dataset.initialValue ? '1' : '0';
        syncProfileEditorState();
    }

    // Restaura o status da conta para o valor inicial ao cancelar a alteracao.
    function restoreProfileStatusValue() {
        if (!profileAccountStatusInput || !profileAccountStatusToggle || !profileEditStatusInput) {
            return;
        }

        const initialStatus = profileAccountStatusInput.dataset.initialValue || 'active';

        profileAccountStatusInput.value = initialStatus;
        profileAccountStatusToggle.checked = initialStatus === 'active';
        profileEditStatusInput.value = '0';
    }

    // Restaura os dados originais de nome e e-mail ao cancelar a edicao dessas informacoes.
    function restoreProfileInfoValues() {
        if (profileNameInput) {
            profileNameInput.value = profileNameInput.dataset.initialValue || '';
        }

        if (profileEmailInput) {
            profileEmailInput.value = profileEmailInput.dataset.initialValue || '';
        }
    }

    // Limpa os campos de nova senha ao cancelar a alteracao de senha.
    function clearProfilePasswordValues() {
        if (profilePasswordInput) {
            profilePasswordInput.value = '';
        }

        if (profilePasswordConfirmationInput) {
            profilePasswordConfirmationInput.value = '';
        }
    }

    // Exibe ou oculta a edicao dos dados cadastrais do perfil.
    function toggleProfileInfoEditor() {
        if (!profileEditInfoInput) {
            return;
        }

        const isClosing = profileEditInfoInput.value === '1';

        profileEditInfoInput.value = isClosing ? '0' : '1';

        if (isClosing) {
            restoreProfileInfoValues();
        }

        syncProfileEditorState();

        if (profileEditInfoInput.value === '1') {
            window.setTimeout(() => document.getElementById('profile_name')?.focus(), 100);
        }
    }

    // Exibe ou oculta a edicao de senha dentro do modal de perfil.
    function toggleProfilePasswordEditor() {
        if (!profileEditPasswordInput) {
            return;
        }

        const isClosing = profileEditPasswordInput.value === '1';

        profileEditPasswordInput.value = isClosing ? '0' : '1';

        if (isClosing) {
            clearProfilePasswordValues();
        }

        syncProfileEditorState();

        if (profileEditPasswordInput.value === '1') {
            window.setTimeout(() => document.getElementById('profile_password')?.focus(), 100);
        }
    }

    // Exibe ou oculta o editor de status da conta baseado em um switch.
    function toggleProfileStatusEditor() {
        if (!profileStatusFields) {
            return;
        }

        const isClosing = !profileStatusFields.classList.contains('d-none');

        profileStatusFields.classList.toggle('d-none');

        if (isClosing) {
            restoreProfileStatusValue();
        } else {
            window.setTimeout(() => profileAccountStatusToggle?.focus(), 100);
        }

        syncProfileEditorState();
    }

    // Exibe ou oculta o painel de exclusao logica da conta.
    function toggleProfileDeleteEditor() {
        if (!profileDeleteFields) {
            return;
        }

        profileDeleteFields.classList.toggle('d-none');

        if (profileDeleteFields.classList.contains('d-none') && profileDeletePasswordInput) {
            profileDeletePasswordInput.value = '';
        }

        if (!profileDeleteFields.classList.contains('d-none')) {
            window.setTimeout(() => profileDeletePasswordInput?.focus(), 100);
        }

        syncProfileEditorState();
    }

    // Inicializa o campo de selecao de amigos com Select2.
    $('#recipient_friend_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Selecione um amigo'
    });

    $('#recipient_friend_id').prop('disabled', $('#recipient_friend_id').is(':disabled')).trigger('change.select2');

    // Intercepta formularios que exigem senha para confirmar antes do envio real.
    $(document).on('submit', '.transaction-confirmation-form', function (event) {
        if (this.dataset.confirmed === 'true') {
            delete this.dataset.confirmed;
            return;
        }

        event.preventDefault();
        openConfirmationModal(this);
    });

    // Salva a posicao atual da tela para formularios que recarregam o dashboard.
    $(document).on('submit', 'form[data-preserve-scroll="true"]', function () {
        persistDashboardScroll();
    });

    // Injeta a senha confirmada no formulario pendente e conclui o envio.
    $('#transaction-confirmation-submit').on('click', function () {
        if (!pendingConfirmationForm) {
            return;
        }

        const password = confirmationPasswordInput.value;

        if (!password) {
            confirmationPasswordInput.focus();
            return;
        }

        pendingConfirmationForm.querySelector('input[name="current_password"]').value = password;
        pendingConfirmationForm.dataset.confirmed = 'true';
        confirmationModal.hide();
        pendingConfirmationForm.submit();
    });

    // Limpa o estado visual do modal sempre que ele e fechado.
    confirmationModalElement.addEventListener('hidden.bs.modal', function () {
        confirmationPasswordInput.value = '';
        confirmationSummary.innerHTML = '';
        confirmationSummary.classList.add('d-none');
        confirmationSubmitButton.classList.remove('btn-danger');
        confirmationSubmitButton.classList.add('btn-primary');
    });

    // Mantem a paginacao do extrato dentro da atualizacao parcial da secao.
    $(document).on('click', '#operations-history-section .pagination a', function (event) {
        event.preventDefault();
        updateOperationsHistory(this.href);
    });

    // Reaproveita o mesmo fluxo AJAX local para links auxiliares do extrato.
    $(document).on('click', '#operations-history-section .operations-history-ajax-link', function (event) {
        event.preventDefault();
        updateOperationsHistory(this.href);
    });

    // Reinicia a pagina do extrato ao alterar a quantidade de registros por pagina.
    $(document).on('change', '#operations-history-section #operations_per_page', function () {
        const form = this.form;
        const formData = new FormData(form);
        formData.set('operations_page', '1');
        const url = `${form.action}?${new URLSearchParams(formData).toString()}`;
        updateOperationsHistory(url);
    });

    // Submete os filtros do extrato via atualizacao parcial da secao.
    $(document).on('submit', '#operations-history-section #operations-history-form', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        formData.set('operations_page', '1');
        const url = `${this.action}?${new URLSearchParams(formData).toString()}`;
        updateOperationsHistory(url);
    });

    // Aplica filtros selecionaveis do extrato sem depender do botao principal.
    $(document).on('change', '#operations-history-section select[name="operations_type"], #operations-history-section select[name="operations_status"], #operations-history-section input[name="operations_date_start"], #operations-history-section input[name="operations_date_end"]', function () {
        const form = document.getElementById('operations-history-form');

        if (!form) {
            return;
        }

        const formData = new FormData(form);
        formData.set('operations_page', '1');
        const url = `${form.action}?${new URLSearchParams(formData).toString()}`;
        updateOperationsHistory(url);
    });

    // Sincroniza a navegacao do navegador com o estado do extrato atualizado dinamicamente.
    window.addEventListener('popstate', function () {
        updateOperationsHistory(window.location.href, false);
    });

    // Persiste o estado aberto do acordeao quando o usuario o expande.
    document.addEventListener('shown.bs.collapse', function (event) {
        if (event.target.id === 'operationsHistoryFiltersCollapse') {
            window.localStorage.setItem(operationsAccordionStorageKey, 'true');
        }
    });

    // Persiste o estado fechado do acordeao quando o usuario o recolhe.
    document.addEventListener('hidden.bs.collapse', function (event) {
        if (event.target.id === 'operationsHistoryFiltersCollapse') {
            window.localStorage.setItem(operationsAccordionStorageKey, 'false');
        }
    });

    // Liga os eventos principais da transferencia aos componentes da tela.
    $('input[name="recipient_type"]').on('change', toggleTransferOptions);
    $('#recipient_friend_id').on('change', updateFriendPreview);
    $('#recipient_account').on('input', lookupAccountHolder);
    $('#transfer-amount').on('input', updateTransferSubmitState);
    profileInfoToggleButton?.addEventListener('click', toggleProfileInfoEditor);
    profilePasswordToggleButton?.addEventListener('click', toggleProfilePasswordEditor);
    profileStatusToggleButton?.addEventListener('click', toggleProfileStatusEditor);
    profileAccountStatusToggle?.addEventListener('change', syncProfileStatusEditor);
    profileDeleteToggleButton?.addEventListener('click', toggleProfileDeleteEditor);
    profileDeleteCancelButton?.addEventListener('click', toggleProfileDeleteEditor);

    // Reidrata a interface ao carregar a pagina com valores antigos ou filtros ativos.
    toggleTransferOptions();
    updateFriendPreview();

    if ($('input[name="recipient_type"]:checked').val() === 'account' && $('#recipient_account').val()) {
        lookupAccountHolder();
    }

    if (window.localStorage.getItem(operationsAccordionStorageKey) === null && hasOperationFilters) {
        window.localStorage.setItem(operationsAccordionStorageKey, 'true');
    }

    restoreDashboardScroll();
    syncOperationsAccordionState();
    updateTransferSubmitState();
    syncProfileStatusEditor();
    syncProfileEditorState();

    // Reabre o modal de perfil ao carregar a pagina quando o fluxo exigir continuidade.
    if (profileModal && profileModalElement.dataset.openOnLoad === 'true') {
        profileModal.show();

        window.setTimeout(() => {
            if (profileEditInfoInput?.value === '1') {
                document.getElementById('profile_name')?.focus();
                return;
            }

            if (profileEditPasswordInput?.value === '1') {
                document.getElementById('profile_password')?.focus();
            }
        }, 200);
    }
});
