<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Legion Bankin') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
    </head>
    <body class="welcome-body">
        <main class="welcome-panel">
            <span class="welcome-eyebrow">Projeto configurado</span>
            <h1>Legion Bankin pronto para uso</h1>
            <p>Esta view padrao do Laravel foi mantida apenas como pagina auxiliar. O fluxo principal da aplicacao utiliza a home, a autenticacao e o dashboard do banco digital.</p>
            <div class="welcome-actions">
                <a class="welcome-primary" href="{{ route('home') }}">Ir para a home</a>
                @auth
                    <a class="welcome-secondary" href="{{ route('dashboard') }}">Abrir minha conta</a>
                @else
                    <a class="welcome-secondary" href="{{ route('login') }}">Acessar conta</a>
                @endauth
            </div>
        </main>
    </body>
</html>
