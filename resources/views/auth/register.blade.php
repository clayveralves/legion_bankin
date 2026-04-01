<x-layouts.app title="Abrir Conta | Legion Bankin">
    <section class="row justify-content-center">
        <div class="col-md-9 col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4 p-lg-5">
                    <span class="badge text-bg-warning mb-3">Abertura de conta</span>
                    <h1 class="h2 mb-2">Abra sua conta digital</h1>
                    <p class="text-secondary mb-4">Preencha seus dados para começar a movimentar sua conta.</p>

                    <form method="post" action="{{ route('register.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label for="name" class="form-label">Nome</label>
                            <input id="name" type="text" class="form-control form-control-lg" name="name" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-12">
                            <label for="register-email" class="form-label">E-mail</label>
                            <input id="register-email" type="email" class="form-control form-control-lg" name="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="register-password" class="form-label">Senha</label>
                            <input id="register-password" type="password" class="form-control form-control-lg" name="password" required>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmar senha</label>
                            <input id="password_confirmation" type="password" class="form-control form-control-lg" name="password_confirmation" required>
                        </div>

                        <div class="col-12 d-grid">
                            <button class="btn btn-warning btn-lg text-dark fw-semibold" type="submit">Abrir conta</button>
                        </div>
                    </form>

                    <p class="text-secondary mt-4 mb-0">Já é cliente? <a class="link-dark fw-semibold" href="{{ route('login') }}">Acessar conta</a></p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>