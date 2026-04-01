<x-layouts.app title="Minha Conta | Legion Bankin">
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @endpush

    @php
        $shouldOpenProfileModal = request()->boolean('open_profile') || old('profile_form') === '1';
        $isAccountAvailable = $currentUser->wallet->opt_active && ! $currentUser->wallet->opt_deleted;
        $currentAccountStatusValue = $currentUser->wallet->opt_active ? 'active' : 'inactive';
        $shouldShowProfileInfoFields = old('profile_edit_info') === '1' || $errors->has('name') || $errors->has('email');
        $shouldShowProfilePasswordFields = old('profile_edit_password') === '1' || $errors->has('password');
        $shouldShowProfileStatusEditor = old('profile_edit_status') === '1';
        $shouldShowProfileDeleteEditor = old('delete_form') === '1' || $errors->has('delete_current_password') || $errors->has('delete_account');
        $shouldShowProfileStatusActions = old('profile_edit_status') === '1' || old('account_status', $currentAccountStatusValue) !== $currentAccountStatusValue;
        $shouldShowProfileSaveActions = $shouldShowProfileInfoFields || $shouldShowProfilePasswordFields || $shouldShowProfileStatusActions || $errors->has('current_password');
        $accountStatusLabel = $currentUser->wallet->opt_deleted ? 'Deletada' : ($currentUser->wallet->opt_active ? 'Ativa' : 'Inativa');
        $accountStatusClass = $currentUser->wallet->opt_deleted ? 'status-pill-deleted' : ($currentUser->wallet->opt_active ? 'status-pill-active' : 'status-pill-inactive');
        $accountStatusDescription = $currentUser->wallet->opt_deleted
            ? 'A conta foi marcada como deletada.'
            : ($currentUser->wallet->opt_active
                ? 'A conta esta disponivel para movimentacoes.'
                : 'A conta esta cadastrada, mas no momento esta inativa.');
        $canRequestDeletion = ! $currentUser->wallet->opt_deleted && (float) $currentUser->wallet->balance === 0.0;
    @endphp

    @if (! $isAccountAvailable)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <span aria-hidden="true">&#9888;</span>
            <span>Sua conta está inativa. Você pode consultar as informações, mas depósitos, transferências, amizades e reversões estão bloqueados até a reativação no perfil.</span>
        </div>
    @endif

    <section class="card border-0 shadow-lg text-bg-dark balance-summary mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <small class="text-uppercase text-warning fw-semibold">Saldo disponível</small>
                <h1 class="display-5 fw-semibold mb-1">R$ {{ number_format((float) $currentUser->wallet->balance, 2, ',', '.') }}</h1>
            </div>
            <div class="balance-summary-meta">
                <x-account-identity
                    :name="$currentUser->name"
                    :email="$currentUser->email"
                    :account-number="$currentUser->wallet->formatted_account_number"
                    :contrast="true"
                    class="balance-summary-identity"
                />

                <div class="balance-summary-copy text-white-50">
                    <div>Resumo financeiro da conta</div>
                    <div>Acompanhe entradas, saídas e reversões</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <span class="badge text-bg-success mb-2">Depósito</span>
                    <h2 class="h4 mb-0">Receber depósito</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('transactions.deposit') }}" method="post" class="row g-3">
                        @csrf
                        <fieldset @disabled(! $isAccountAvailable)>
                            <div class="col-12">
                                <label for="deposit-amount" class="form-label">Valor</label>
                                <input id="deposit-amount" type="number" class="form-control" name="amount" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-12 mt-3">
                                <label for="deposit-description" class="form-label">Descrição</label>
                                <input id="deposit-description" type="text" class="form-control" name="description" placeholder="Ex.: aporte pessoal">
                            </div>
                            <div class="col-12 d-grid mt-3">
                                <button class="btn btn-success" type="submit">Depositar</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <span class="badge text-bg-primary mb-2">Transferência</span>
                    <h2 class="h4 mb-0">Transferir valor</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('transactions.transfer') }}" method="post" class="transaction-confirmation-form" data-confirmation-title="Confirmar transferência" data-confirmation-message="Informe sua senha para confirmar a transferência." data-operation-kind="transfer">
                        @csrf
                        <input type="hidden" name="current_password" value="">
                        <fieldset class="row g-3" @disabled(! $isAccountAvailable)>
                        <div class="col-12">
                            <label class="form-label d-block">Forma de transferência</label>
                            @php
                                $recipientType = old('recipient_type', $friends->isNotEmpty() ? 'friend' : 'account');
                            @endphp
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="recipient_type_friend" value="friend" {{ $recipientType === 'friend' ? 'checked' : '' }} {{ $friends->isEmpty() || ! $isAccountAvailable ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="recipient_type_friend">Selecionar amigo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="recipient_type_account" value="account" {{ $recipientType === 'account' ? 'checked' : '' }} {{ ! $isAccountAvailable ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="recipient_type_account">Informar número da conta</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 transfer-option transfer-option-friend">
                            <label for="recipient_friend_id" class="form-label">Amigo</label>
                            <select id="recipient_friend_id" class="form-select" name="recipient_friend_id" @disabled(! $isAccountAvailable || $friends->isEmpty())>
                                <option value="">Selecione um amigo</option>
                                @foreach ($friends as $friend)
                                    <option value="{{ $friend->id }}" data-account="{{ $friend->wallet->id }}" data-account-formatted="{{ $friend->wallet->formatted_account_number }}" data-name="{{ $friend->name }}" data-email="{{ $friend->email }}" {{ (string) old('recipient_friend_id') === (string) $friend->id ? 'selected' : '' }}>
                                        {{ $friend->name }} · {{ $friend->email }} · Conta {{ $friend->wallet->formatted_account_number }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Escolha um usuário da sua lista de amigos.</div>
                            <div id="friend-transfer-preview" class="alert alert-info mt-3 mb-0 d-none" role="alert"></div>
                        </div>
                        <div class="col-12 transfer-option transfer-option-account">
                            <label for="recipient_account" class="form-label">Número da conta de destino</label>
                            <input id="recipient_account" type="number" class="form-control" name="recipient_account" min="1" step="1" value="{{ old('recipient_account') }}" data-lookup-url="{{ route('accounts.lookup') }}">
                            <div class="form-text">Use esta opção para transferir para uma conta fora da sua lista de amigos.</div>
                            <div id="account-holder-feedback" class="alert mt-3 mb-0 d-none" role="alert"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="transfer-amount" class="form-label">Valor</label>
                            <input id="transfer-amount" type="number" class="form-control" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                        </div>
                        <div class="col-md-8">
                            <label for="transfer-description" class="form-label">Descrição</label>
                            <input id="transfer-description" type="text" class="form-control" name="description" placeholder="Ex.: pagamento" value="{{ old('description') }}">
                        </div>
                        <div class="col-12 d-grid d-md-flex justify-content-md-end">
                            <button id="transfer-submit" class="btn btn-primary px-4" type="submit" disabled>Transferir</button>
                        </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <span class="badge text-bg-warning mb-2">Amizades</span>
                    <h2 class="h4 mb-0">Adicionar amigo</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('dashboard') }}" method="get" class="row g-3 mb-4" data-preserve-scroll="true">
                        <fieldset @disabled(! $isAccountAvailable)>
                            <div class="col-12">
                                <label for="friend_search" class="form-label">Buscar por nome, e-mail ou número da conta</label>
                                <input id="friend_search" type="text" class="form-control" name="friend_search" value="{{ $friendSearch }}" placeholder="Ex.: ana@legionbankin.test ou 000002">
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end mt-3">
                                <button class="btn btn-warning text-dark fw-semibold" type="submit">Buscar usuário</button>
                            </div>
                        </fieldset>
                    </form>

                    @if ($friendSearch !== '')
                        @if ($friendSearchResults->isNotEmpty())
                            <div class="list-group">
                                @foreach ($friendSearchResults as $result)
                                    <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                        <x-account-identity
                                            :name="$result->name"
                                            :email="$result->email"
                                            :account-number="$result->wallet->formatted_account_number"
                                        />
                                        <form action="{{ route('friendships.store') }}" method="post" data-preserve-scroll="true">
                                            @csrf
                                            <input type="hidden" name="friend_id" value="{{ $result->id }}">
                                            <button class="btn btn-outline-primary btn-sm" type="submit" @disabled(! $isAccountAvailable)>Enviar solicitação</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-0" role="alert">
                                <span aria-hidden="true">&#9888;</span>
                                <span>Nenhum usuário disponível foi encontrado para a busca informada.</span>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0" role="status">
                            <span aria-hidden="true">&#9432;</span>
                            <span>Pesquise um usuário para enviar uma solicitação de amizade.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <span class="badge text-bg-secondary mb-2">Pendências</span>
                    <h2 class="h4 mb-0">Solicitações pendentes</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <h3 class="h6 text-uppercase text-secondary mb-3">Recebidas</h3>
                    @if ($incomingRequests->isNotEmpty())
                        <div class="list-group mb-4">
                            @foreach ($incomingRequests as $friendship)
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <x-account-identity
                                            :name="$friendship->requester->name"
                                            :email="$friendship->requester->email"
                                            :account-number="$friendship->requester->wallet->formatted_account_number"
                                        />
                                        <div class="mt-2">
                                            <span class="badge rounded-pill status-pill status-pill-pending">Solicitação recebida</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('friendships.respond', $friendship) }}" method="post" data-preserve-scroll="true">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="accept">
                                            <button class="btn btn-success btn-sm" type="submit" @disabled(! $isAccountAvailable)>Aceitar</button>
                                        </form>
                                        <form action="{{ route('friendships.respond', $friendship) }}" method="post" data-preserve-scroll="true">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="decline">
                                            <button class="btn btn-outline-danger btn-sm" type="submit" @disabled(! $isAccountAvailable)>Recusar</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-secondary d-flex align-items-center gap-2 mb-4" role="status">
                            <span aria-hidden="true">&#9432;</span>
                            <span>Nenhuma solicitação recebida no momento.</span>
                        </div>
                    @endif

                    <h3 class="h6 text-uppercase text-secondary mb-3">Enviadas</h3>
                    @if ($outgoingRequests->isNotEmpty())
                        <div class="list-group">
                            @foreach ($outgoingRequests as $friendship)
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <x-account-identity
                                            :name="$friendship->addressee->name"
                                            :email="$friendship->addressee->email"
                                            :account-number="$friendship->addressee->wallet->formatted_account_number"
                                        />
                                        <div class="mt-2">
                                            <span class="badge rounded-pill status-pill status-pill-info">Aguardando resposta</span>
                                        </div>
                                    </div>
                                    <form action="{{ route('friendships.destroy', $friendship) }}" method="post" data-preserve-scroll="true">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-secondary btn-sm" type="submit" @disabled(! $isAccountAvailable)>Cancelar solicitação</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0" role="status">
                            <span aria-hidden="true">&#9432;</span>
                            <span>Nenhuma solicitação enviada aguardando resposta.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 pt-4 px-4">
            <div>
                <span class="badge text-bg-primary mb-2">Rede</span>
                <h2 class="h4 mb-0">Meus amigos</h2>
            </div>
            <small class="text-secondary">Usuários conectados para facilitar futuras transferências</small>
        </div>
        <div class="card-body px-4 pb-4">
            @if ($friends->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Identificação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($friends as $friend)
                                <tr>
                                    <td>
                                        <x-account-identity
                                            :name="$friend->name"
                                            :email="$friend->email"
                                            :account-number="$friend->wallet->formatted_account_number"
                                            :compact="true"
                                        />
                                    </td>
                                    <td>
                                        <form action="{{ route('friendships.destroy', $acceptedFriendships[$friend->id]) }}" method="post" data-preserve-scroll="true">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit" @disabled(! $isAccountAvailable)>Remover amigo</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0" role="status">
                    <span aria-hidden="true">&#9432;</span>
                    <span>Voce ainda nao possui amigos adicionados.</span>
                </div>
            @endif
        </div>
    </section>

    <section id="operations-history-section" class="card border-0 shadow-sm {{ ! $isAccountAvailable ? 'operations-history-locked' : '' }}" data-accordion-storage-key="legion-bankin.operations-history-filters.open" data-has-filters="{{ ($operationsSearch !== '' || $operationsType !== '' || $operationsStatus !== '' || $operationsDateStart !== '' || $operationsDateEnd !== '') ? 'true' : 'false' }}" data-account-available="{{ $isAccountAvailable ? 'true' : 'false' }}">
        <div class="operations-history-overlay" aria-hidden="true">
            <div class="operations-history-loading-card">
                @for ($index = 0; $index < 5; $index++)
                    <div class="operations-history-skeleton-row">
                        <div class="operations-history-skeleton-block md"></div>
                        <div class="operations-history-skeleton-block sm"></div>
                        <div>
                            <div class="operations-history-skeleton-block lg mb-2"></div>
                            <div class="operations-history-skeleton-block md"></div>
                        </div>
                        <div class="operations-history-skeleton-block sm"></div>
                        <div class="operations-history-skeleton-block sm"></div>
                        <div>
                            <div class="operations-history-skeleton-block lg mb-2"></div>
                            <div class="operations-history-skeleton-block md"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        <div class="card-header bg-white border-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 pt-4 px-4">
            <div>
                <span class="badge text-bg-dark mb-2">Histórico</span>
                <h2 class="h4 mb-0">Extrato da conta</h2>
            </div>
            <small class="text-secondary">Consulte as movimentações registradas na sua conta</small>
        </div>
        <div class="card-body px-4 pb-4" id="operations-history-content">
            @php
                $hasOperationFilters = $operationsSearch !== '' || $operationsType !== '' || $operationsStatus !== '' || $operationsDateStart !== '' || $operationsDateEnd !== '';
                $typeLabels = collect($operationTypes)->mapWithKeys(fn ($type) => [$type->value => strtoupper($type->value)]);
                $statusLabels = collect($operationStatuses)->mapWithKeys(fn ($status) => [$status->value => strtoupper($status->value)]);
                $baseFilterQuery = array_filter([
                    'operations_per_page' => $operationsPerPage,
                    'friend_search' => $friendSearch ?: null,
                ], fn ($value) => $value !== null && $value !== '');
            @endphp

            <div class="operations-history-toolbar mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap text-secondary small">
                    <label for="operations_per_page" class="mb-0">Exibir</label>
                    <select id="operations_per_page" name="operations_per_page" form="operations-history-form" class="form-select form-select-sm w-auto" @disabled(! $isAccountAvailable)>
                        @foreach ([5, 10, 20, 50] as $option)
                            <option value="{{ $option }}" {{ $operationsPerPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span>por página</span>
                </div>

                <div class="accordion operations-history-filter-accordion" id="operationsHistoryFiltersAccordion">
                    <div class="accordion-item border rounded-4 overflow-hidden">
                        <h3 class="accordion-header">
                            <button class="accordion-button {{ $hasOperationFilters ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#operationsHistoryFiltersCollapse" aria-expanded="{{ $hasOperationFilters ? 'true' : 'false' }}" aria-controls="operationsHistoryFiltersCollapse" @disabled(! $isAccountAvailable)>
                                Filtros do extrato
                            </button>
                        </h3>
                        <div id="operationsHistoryFiltersCollapse" class="accordion-collapse collapse {{ $hasOperationFilters ? 'show' : '' }}" data-bs-parent="#operationsHistoryFiltersAccordion">
                            <div class="accordion-body">
                                <form id="operations-history-form" action="{{ route('dashboard') }}" method="get" class="operations-history-filters text-secondary small w-100">
                                    @if ($friendSearch !== '')
                                        <input type="hidden" name="friend_search" value="{{ $friendSearch }}">
                                    @endif
                                    <input type="hidden" name="operations_page" value="1">

                                    <div class="operations-history-filter-bar">
                                        <div class="input-group input-group-sm operations-history-search-group operations-history-search-input">
                                            <span class="input-group-text">Destinatário</span>
                                            <input
                                                id="operations_search"
                                                type="text"
                                                name="operations_search"
                                                class="form-control"
                                                value="{{ $operationsSearch }}"
                                                placeholder="Nome do destinatário"
                                                @disabled(! $isAccountAvailable)
                                            >
                                        </div>

                                        <select name="operations_type" class="form-select form-select-sm operations-history-select" @disabled(! $isAccountAvailable)>
                                            <option value="">Todos os tipos</option>
                                            @foreach ($operationTypes as $type)
                                                <option value="{{ $type->value }}" {{ $operationsType === $type->value ? 'selected' : '' }}>{{ strtoupper($type->value) }}</option>
                                            @endforeach
                                        </select>

                                        <select name="operations_status" class="form-select form-select-sm operations-history-select" @disabled(! $isAccountAvailable)>
                                            <option value="">Todos os status</option>
                                            @foreach ($operationStatuses as $status)
                                                <option value="{{ $status->value }}" {{ $operationsStatus === $status->value ? 'selected' : '' }}>{{ strtoupper($status->value) }}</option>
                                            @endforeach
                                        </select>

                                        <div class="operations-history-date-range">
                                            <input type="date" name="operations_date_start" class="form-control form-control-sm w-auto" value="{{ $operationsDateStart }}" aria-label="Data inicial" @disabled(! $isAccountAvailable)>
                                            <span class="text-secondary small">até</span>
                                            <input type="date" name="operations_date_end" class="form-control form-control-sm w-auto" value="{{ $operationsDateEnd }}" aria-label="Data final" @disabled(! $isAccountAvailable)>
                                        </div>
                                    </div>

                                    <div class="operations-history-filter-actions">
                                        <button class="btn btn-outline-secondary btn-sm" type="submit" @disabled(! $isAccountAvailable)>Aplicar filtros</button>
                                        @if ($isAccountAvailable)
                                            <a class="btn btn-warning btn-sm text-dark fw-semibold operations-history-ajax-link" href="{{ route('dashboard', $baseFilterQuery) }}">Limpar filtros</a>
                                        @else
                                            <span class="btn btn-warning btn-sm text-dark fw-semibold disabled">Limpar filtros</span>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($hasOperationFilters)
                    <div class="operations-history-active-filters">
                        @if ($operationsSearch !== '')
                            @if ($isAccountAvailable)
                                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark operations-history-ajax-link" href="{{ route('dashboard', array_filter(array_merge($baseFilterQuery, ['operations_type' => $operationsType ?: null, 'operations_status' => $operationsStatus ?: null, 'operations_date_start' => $operationsDateStart ?: null, 'operations_date_end' => $operationsDateEnd ?: null]), fn ($value) => $value !== null && $value !== '')) }}">Destinatário: {{ $operationsSearch }} ×</a>
                            @else
                                <span class="badge rounded-pill text-bg-light border text-dark">Destinatário: {{ $operationsSearch }}</span>
                            @endif
                        @endif
                        @if ($operationsType !== '')
                            @if ($isAccountAvailable)
                                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark operations-history-ajax-link" href="{{ route('dashboard', array_filter(array_merge($baseFilterQuery, ['operations_search' => $operationsSearch ?: null, 'operations_status' => $operationsStatus ?: null, 'operations_date_start' => $operationsDateStart ?: null, 'operations_date_end' => $operationsDateEnd ?: null]), fn ($value) => $value !== null && $value !== '')) }}">Tipo: {{ $typeLabels[$operationsType] ?? strtoupper($operationsType) }} ×</a>
                            @else
                                <span class="badge rounded-pill text-bg-light border text-dark">Tipo: {{ $typeLabels[$operationsType] ?? strtoupper($operationsType) }}</span>
                            @endif
                        @endif
                        @if ($operationsStatus !== '')
                            @if ($isAccountAvailable)
                                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark operations-history-ajax-link" href="{{ route('dashboard', array_filter(array_merge($baseFilterQuery, ['operations_search' => $operationsSearch ?: null, 'operations_type' => $operationsType ?: null, 'operations_date_start' => $operationsDateStart ?: null, 'operations_date_end' => $operationsDateEnd ?: null]), fn ($value) => $value !== null && $value !== '')) }}">Status: {{ $statusLabels[$operationsStatus] ?? strtoupper($operationsStatus) }} ×</a>
                            @else
                                <span class="badge rounded-pill text-bg-light border text-dark">Status: {{ $statusLabels[$operationsStatus] ?? strtoupper($operationsStatus) }}</span>
                            @endif
                        @endif
                        @if ($operationsDateStart !== '')
                            @if ($isAccountAvailable)
                                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark operations-history-ajax-link" href="{{ route('dashboard', array_filter(array_merge($baseFilterQuery, ['operations_search' => $operationsSearch ?: null, 'operations_type' => $operationsType ?: null, 'operations_status' => $operationsStatus ?: null, 'operations_date_end' => $operationsDateEnd ?: null]), fn ($value) => $value !== null && $value !== '')) }}">De: {{ $operationsDateStart }} ×</a>
                            @else
                                <span class="badge rounded-pill text-bg-light border text-dark">De: {{ $operationsDateStart }}</span>
                            @endif
                        @endif
                        @if ($operationsDateEnd !== '')
                            @if ($isAccountAvailable)
                                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark operations-history-ajax-link" href="{{ route('dashboard', array_filter(array_merge($baseFilterQuery, ['operations_search' => $operationsSearch ?: null, 'operations_type' => $operationsType ?: null, 'operations_status' => $operationsStatus ?: null, 'operations_date_start' => $operationsDateStart ?: null]), fn ($value) => $value !== null && $value !== '')) }}">Até: {{ $operationsDateEnd }} ×</a>
                            @else
                                <span class="badge rounded-pill text-bg-light border text-dark">Até: {{ $operationsDateEnd }}</span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            @if ($operations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Detalhe</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($operations as $operation)
                                @php
                                    $signedAmount = $operation->signedAmountFor($currentUser);
                                    $counterparty = $operation->counterpartyFor($currentUser);
                                @endphp
                                <tr>
                                    <td>{{ $operation->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $operation->type->value === 'deposit' ? 'text-bg-success' : ($operation->type->value === 'transfer' ? 'text-bg-primary' : 'text-bg-warning') }}">
                                            {{ strtoupper($operation->type->value) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            @if ($operation->type->value === 'deposit')
                                                Depósito em conta
                                            @elseif ($operation->type->value === 'transfer')
                                                {{ $counterparty ? 'Transferência com '.$counterparty->name.' · '.$counterparty->email.' · Conta '.$counterparty->wallet->formatted_account_number : 'Transferência' }}
                                            @else
                                                Reversão de operação
                                            @endif
                                        </div>
                                        <div class="small text-secondary">{{ $operation->description ?: 'Sem descrição adicional.' }}</div>
                                        <div class="small text-muted">{{ $operation->uuid }}</div>
                                    </td>
                                    <td class="fw-semibold {{ str_starts_with($signedAmount ?? '', '+') ? 'text-success' : 'text-danger' }}">{{ $signedAmount ?? 'N/D' }}</td>
                                    <td>
                                        <span class="badge rounded-pill status-pill {{ $operation->status->value === 'completed' ? 'status-pill-completed' : 'status-pill-reversed' }}">
                                            {{ $operation->status->value === 'completed' ? 'Concluída' : 'Revertida' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($operation->canBeReversed())
                                            <form action="{{ route('operations.reverse', $operation) }}" method="post" class="row g-2 align-items-center reversal-form transaction-confirmation-form" data-confirmation-title="Confirmar reversão" data-confirmation-message="Informe sua senha para reverter esta operação." data-operation-kind="reversal" data-summary-title="{{ $operation->type->value === 'transfer' && $counterparty ? 'Transferência com '.$counterparty->name : 'Operação '.$operation->uuid }}" data-summary-amount="{{ $signedAmount ?? 'N/D' }}" data-summary-description="{{ $operation->description ?: 'Sem descrição adicional.' }}">
                                                @csrf
                                                <input type="hidden" name="current_password" value="">
                                                <div class="col-12">
                                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Motivo da reversão" @disabled(! $isAccountAvailable)>
                                                </div>
                                                <div class="col-12 d-grid">
                                                    <button class="btn btn-outline-danger btn-sm" type="submit" @disabled(! $isAccountAvailable)>Reverter</button>
                                                </div>
                                            </form>
                                        @else
                                            @if ($operation->status->value === 'reversed')
                                                <span class="badge rounded-pill status-pill status-pill-reversed">&#8635; Já revertida</span>
                                            @else
                                                <span class="badge rounded-pill status-pill status-pill-muted">&#9432; Sem reversão disponível</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert {{ $hasOperationFilters ? 'alert-warning' : 'alert-secondary' }} d-flex align-items-center gap-2 mb-0" role="status">
                    <span aria-hidden="true">{{ $hasOperationFilters ? '⚠' : 'ℹ' }}</span>
                    <span>
                        {{ $hasOperationFilters ? 'Nenhuma movimentação foi encontrada com os filtros informados.' : 'Ainda não há movimentações registradas na sua conta.' }}
                    </span>
                </div>
            @endif

            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mt-4">
                <div class="text-secondary small">
                    Mostrando {{ $operations->firstItem() ?? 0 }} a {{ $operations->lastItem() ?? 0 }} de {{ $operations->total() }} registros
                </div>
                <div class="d-flex justify-content-lg-end">
                    {{ $operations->onEachSide(1)->links('vendor.pagination.bootstrap-5-bankin') }}
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="transactionConfirmationModal" tabindex="-1" aria-labelledby="transactionConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0" id="transactionConfirmationModalLabel">Confirmar operação</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p id="transactionConfirmationMessage" class="text-secondary mb-3">Informe sua senha para continuar.</p>
                    <div id="transactionConfirmationSummary" class="alert alert-light border mb-3 d-none"></div>
                    <div>
                        <label for="transaction-confirmation-password" class="form-label">Senha atual</label>
                        <input id="transaction-confirmation-password" type="password" class="form-control" autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="transaction-confirmation-submit">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="profileDetailsModal" tabindex="-1" aria-labelledby="profileDetailsModalLabel" aria-hidden="true" data-open-on-load="{{ $shouldOpenProfileModal ? 'true' : 'false' }}" data-info-open-on-load="{{ $shouldShowProfileInfoFields ? 'true' : 'false' }}" data-password-open-on-load="{{ $shouldShowProfilePasswordFields ? 'true' : 'false' }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow profile-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="badge text-bg-dark mb-2">Perfil</span>
                        <h2 class="modal-title h4 mb-1" id="profileDetailsModalLabel">Detalhes da sua conta</h2>
                        <p class="text-secondary mb-0">Visualize seus dados e confirme com a senha para salvar qualquer alteração.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-4 profile-details-grid">
                        <div class="col-md-6">
                            <div class="profile-detail-card">
                                <span class="profile-detail-label">Identificação da conta</span>
                                <x-account-identity
                                    :name="$currentUser->name"
                                    :email="$currentUser->email"
                                    :account-number="$currentUser->wallet->formatted_account_number"
                                    :compact="true"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="profile-detail-card">
                                <span class="profile-detail-label">Status da conta</span>
                                <div>
                                    <span class="badge rounded-pill status-pill {{ $accountStatusClass }}">{{ $accountStatusLabel }}</span>
                                </div>
                                <small>{{ $accountStatusDescription }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="profile-editor-actions mb-4">
                        <button type="button" class="btn btn-outline-primary" id="toggle-profile-info" aria-controls="profile-info-fields" aria-expanded="{{ $shouldShowProfileInfoFields ? 'true' : 'false' }}" @disabled(! $isAccountAvailable)>
                            Alterar informações
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="toggle-profile-password" aria-controls="profile-password-fields" aria-expanded="{{ $shouldShowProfilePasswordFields ? 'true' : 'false' }}" @disabled(! $isAccountAvailable)>
                            Alterar senha
                        </button>
                        <button type="button" class="btn btn-outline-dark" id="toggle-profile-status" aria-controls="profile-status-fields" aria-expanded="{{ $shouldShowProfileStatusEditor ? 'true' : 'false' }}">
                            Alterar status
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="toggle-profile-delete" aria-controls="profile-delete-fields" aria-expanded="{{ $shouldShowProfileDeleteEditor ? 'true' : 'false' }}">
                            Excluir conta
                        </button>
                    </div>

                    <form action="{{ route('profile.update') }}" method="post" id="profile-details-form" class="row g-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="profile_form" value="1">
                        <input type="hidden" name="account_status" id="profile_account_status" value="{{ old('account_status', $currentAccountStatusValue) }}" data-initial-value="{{ $currentAccountStatusValue }}">
                        <input type="hidden" name="profile_edit_info" id="profile_edit_info" value="{{ $shouldShowProfileInfoFields ? '1' : '0' }}">
                        <input type="hidden" name="profile_edit_password" id="profile_edit_password" value="{{ $shouldShowProfilePasswordFields ? '1' : '0' }}">
                        <input type="hidden" name="profile_edit_status" id="profile_edit_status" value="{{ $shouldShowProfileStatusActions ? '1' : '0' }}">

                        <div id="profile-status-fields" class="col-12 {{ $shouldShowProfileStatusEditor ? '' : 'd-none' }}">
                            <div class="profile-status-editor">
                                <div>
                                    <label for="profile_account_status_toggle" class="form-label mb-1">Status da conta</label>
                                    <p class="text-secondary small mb-0">Use a chave para ativar ou inativar a conta. A alteração só será aplicada após confirmar com sua senha.</p>
                                </div>
                                <div class="form-check form-switch profile-status-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="profile_account_status_toggle" {{ old('account_status', $currentAccountStatusValue) === 'active' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="profile_account_status_toggle">Conta ativa</label>
                                </div>
                            </div>
                        </div>

                        <div id="profile-delete-fields" class="col-12 {{ $shouldShowProfileDeleteEditor ? '' : 'd-none' }}">
                            @if ($canRequestDeletion)
                                <div class="profile-delete-editor">
                                    <div>
                                        <label for="delete_current_password" class="form-label mb-1 text-danger">Excluir conta</label>
                                        <p class="text-danger small mb-0">Tem certeza de que deseja excluir a conta? A exclusão será lógica: a conta ficará deletada, inativa e dependerá de um admin para eventual reativação.</p>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-8">
                                            <label for="delete_current_password" class="form-label">Confirmar com senha</label>
                                            <input id="delete_current_password" type="password" class="form-control @error('delete_current_password') is-invalid @enderror" name="delete_current_password" autocomplete="current-password" form="profile-delete-form" required>
                                            @error('delete_current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2 justify-content-md-end">
                                            <button type="button" class="btn btn-outline-secondary" id="cancel-profile-delete">Cancelar</button>
                                            <button type="submit" class="btn btn-danger" form="profile-delete-form">Confirmar exclusão</button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">
                                    <span aria-hidden="true">&#9888;</span>
                                    <span>É necessário retirar todo o saldo da conta primeiro para habilitar a exclusão.</span>
                                </div>
                            @endif

                            @error('delete_account')
                                <div class="alert alert-warning mt-3 mb-0" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="profile-info-fields" class="row g-3 {{ $shouldShowProfileInfoFields ? '' : 'd-none' }}">
                            <div class="col-md-6">
                                <label for="profile_name" class="form-label">Nome</label>
                                <input id="profile_name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $currentUser->name) }}" data-initial-value="{{ $currentUser->name }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="profile_email" class="form-label">E-mail</label>
                                <input id="profile_email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $currentUser->email) }}" data-initial-value="{{ $currentUser->email }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="profile-password-fields" class="row g-3 {{ $shouldShowProfilePasswordFields ? '' : 'd-none' }}">
                            <div class="col-md-6">
                                <label for="profile_password" class="form-label">Nova senha</label>
                                <input id="profile_password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password">
                                <div class="form-text">Preencha apenas se quiser alterar sua senha atual.</div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="profile_password_confirmation" class="form-label">Confirmar nova senha</label>
                                <input id="profile_password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>

                        <div id="profile-save-panel" class="col-12 {{ $shouldShowProfileSaveActions ? '' : 'd-none' }}">
                            <label for="profile_current_password" class="form-label">Senha atual para confirmar</label>
                            <input id="profile_current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" autocomplete="current-password" required>
                            <div class="form-text">A edição do perfil so sera salva apos a confirmacao com sua senha atual.</div>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>

                    <form action="{{ route('profile.destroy') }}" method="post" id="profile-delete-form" class="d-none">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_form" value="1">
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary {{ $shouldShowProfileSaveActions ? '' : 'd-none' }}" form="profile-details-form" id="profile-save-button">Salvar alterações</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="{{ asset('js/dashboard.js') }}"></script>
    @endpush
</x-layouts.app>