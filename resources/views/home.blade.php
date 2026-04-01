<x-layouts.app title="Legion Bankin">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    @endpush

    <section class="row g-4 align-items-stretch mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg hero-card h-100">
                <div class="card-body p-4 p-lg-5">
                    <span class="badge text-bg-warning mb-3">Conta digital</span>
                    <h1 class="display-5 fw-semibold mb-3">Sua vida financeira com mais controle, segurança e praticidade.</h1>
                    <p class="lead text-secondary mb-4">
                        Abra sua conta, acompanhe o saldo, realize transferências, registre depósitos e consulte cada movimentação em um só lugar.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-warning btn-lg text-dark fw-semibold" href="{{ route('register') }}">Abrir minha conta</a>
                        <a class="btn btn-outline-dark btn-lg" href="{{ route('login') }}">Acessar conta</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="row g-3 h-100">
                <div class="col-12">
                    <div class="card border-0 shadow-sm metric-card h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-secondary fw-semibold">Transferências</small>
                            <h3 class="h2 mt-2">Ágeis</h3>
                            <p class="mb-0 text-secondary">Envie valores entre contas com validação de saldo e registro da operação.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-12">
                    <div class="card border-0 shadow-sm metric-card h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-secondary fw-semibold">Segurança</small>
                            <h3 class="h2 mt-2">Confiável</h3>
                            <p class="mb-0 text-secondary">Cada movimentação fica registrada para garantir rastreabilidade e controle.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-12">
                    <div class="card border-0 shadow-sm metric-card h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-secondary fw-semibold">Acompanhamento</small>
                            <h3 class="h2 mt-2">Completo</h3>
                            <p class="mb-0 text-secondary">Consulte saldo, histórico e detalhes das suas operações sempre que precisar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Acesso à conta</h2>
                    <p class="text-secondary mb-0">Entre com segurança para acompanhar saldo, movimentações e recursos da sua conta digital.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Gestão de saldo</h2>
                    <p class="text-secondary mb-0">Visualize seu saldo atual e realize depósitos com atualização imediata na carteira.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4">Histórico financeiro</h2>
                    <p class="text-secondary mb-0">Acompanhe entradas, saídas e reversões com uma visualização clara das movimentações.</p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>