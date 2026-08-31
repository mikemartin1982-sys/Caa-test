<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Compliance Assurance Associates, Inc.')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: -apple-system, sans-serif; margin: 0; color: #1a1a1a; }
        h1, h2, h3 { font-family: 'Montserrat', sans-serif; }
        header.site-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; border-bottom: 1px solid #e5e5e5; }
        header.site-header img.logo { height: 48px; }
        nav a { margin-left: 1.5rem; text-decoration: none; color: #1a1a1a; }
        main { padding: 2rem; max-width: 1100px; margin: 0 auto; }
        footer.site-footer { padding: 2rem; text-align: center; color: #666; font-size: 0.85rem; border-top: 1px solid #e5e5e5; margin-top: 3rem; }
    </style>
    @stack('styles')
</head>
<body>
<header class="site-header">
    <a href="{{ route('public.calendar') }}">
        <img class="logo" src="/images/caa-logo.png" alt="Compliance Assurance Associates, Inc.">
    </a>
    <nav>
        <a href="{{ route('public.calendar') }}">Smoke School Calendar</a>
        <a href="{{ route('public.map') }}">Find a School</a>
        <a href="{{ route('public.become-a-client') }}">Become a Client</a>
        <a href="{{ route('portal.dashboard') }}">Client Login</a>
    </nav>
</header>

<main>
    @if (session('status'))
        <div style="background:#e8f5e9; border:1px solid #a5d6a7; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
            {{ session('status') }}
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    &copy; {{ date('Y') }} Compliance Assurance Associates, Inc. &middot; 682 Orvil Smith Rd. Harvest, AL 35749
</footer>
</body>
</html>
