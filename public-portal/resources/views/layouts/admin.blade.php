<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop):
         needed so admin pages can POST via fetch() with a valid CSRF
         token, not a form submit -- the calendar's Save Changes button
         reads this directly. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CAA Administration')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/caa-brand.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: -apple-system, sans-serif; margin: 0; }
        h1, h2, h3 { font-family: 'Montserrat', sans-serif; color: #1a1a1a; }
        .status-banner { background:#e8f5e9; border:1px solid #a5d6a7; padding:1rem; border-radius:0.375rem; margin-bottom:1.5rem; color: #2e5e33; }
    </style>
    @stack('styles')
</head>
<body>
<div class="admin-shell">
    @include('admin.partials.sidebar')
 
    <main class="admin-content">
        @if (session('status'))
            <div class="status-banner">
                {{ session('status') }}
            </div>
        @endif
 
        @yield('content')
    </main>
</div>
{{-- Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop):
     only @stack('styles') existed before -- any page pushing to
     'scripts' (like the calendar's drag-and-drop <script> block) was
     being silently discarded, never actually output at all. Placed at
     the end of <body>, not <head>, so pushed scripts run after the
     DOM they reference already exists. --}}
@stack('scripts')
</body>
</html>