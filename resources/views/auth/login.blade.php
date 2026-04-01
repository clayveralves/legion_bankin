<x-layouts.app title="Acessar Conta | Legion Bankin">
    <section class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4 p-lg-5">
                    <span class="badge text-bg-warning mb-3">Internet Banking</span>
                    <h1 class="h2 mb-2">Acesse sua conta</h1>
                    <p class="text-secondary mb-4">Informe seu e-mail ou número da conta com a sua senha para consultar saldo e movimentações.</p>

                    <form method="post" action="{{ route('login.store') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="login" class="form-label">E-mail ou número da conta</label>
                            <input id="login" type="text" class="form-control form-control-lg" name="login" value="{{ old('login') }}" placeholder="Ex.: ana@legionbankin.test ou 000001" required>
                        </div>

                        <div>
                            <label for="password" class="form-label">Senha</label>
                            <input id="password" type="password" class="form-control form-control-lg" name="password" required>
                        </div>

                        <button class="btn btn-warning btn-lg text-dark fw-semibold" type="submit">Acessar conta</button>
                    </form>

                    <p class="text-secondary mt-4 mb-0">Ainda não é cliente? <a class="link-dark fw-semibold" href="{{ route('register') }}">Abra sua conta</a></p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>