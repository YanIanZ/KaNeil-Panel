<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'KaNeil') }}</title>
    <script>
        (function() {
            try {
                var t = JSON.parse(localStorage.getItem('galleon-tweaks') || '{}');
                if (t.dark) document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.style.setProperty('--density',
                    ({compact:'0.85',regular:'1',comfy:'1.15'})[t.density] || '1');
                if (Array.isArray(t.accent)) {
                    document.documentElement.style.setProperty('--accent', t.accent[0]);
                    document.documentElement.style.setProperty('--brass',  t.accent[1]);
                    document.documentElement.style.setProperty('--sea',    t.accent[2]);
                }
            } catch(e) {}
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/js/galleon/app.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>