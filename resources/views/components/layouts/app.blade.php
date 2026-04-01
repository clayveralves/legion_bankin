@props(['title' => 'Legion Bankin'])

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @stack('styles')
    </head>
    <body class="bankin-body">
        <nav class="navbar navbar-expand-lg navbar-dark bankin-navbar sticky-top">
            <div class="container py-2">
                <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-3">
                    <span class="brand-mark">LB</span>
                    <span>
                        <span class="d-block fw-semibold">Legion Bankin</span>
                        <small class="text-white-50">Banco digital</small>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegação">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <div class="ms-auto d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                        @auth
                            @if (request()->routeIs('dashboard'))
                                <button class="btn btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#profileDetailsModal">Minha conta</button>
                            @else
                                <a class="btn btn-outline-light" href="{{ route('dashboard', ['open_profile' => 1]) }}">Minha conta</a>
                            @endif
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit" class="btn btn-light">Sair</button>
                            </form>
                        @else
                            <a class="btn btn-outline-light" href="{{ route('login') }}">Acessar conta</a>
                            <a class="btn btn-warning text-dark fw-semibold" href="{{ route('register') }}">Abrir conta</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main class="container py-4 py-lg-5">
            @if (session('status'))
                <div class="alert alert-success border-0 shadow-sm" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script src="{{ asset('js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>