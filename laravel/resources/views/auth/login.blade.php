<!doctype html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login PMB</title>
    <link rel="shortcut icon" href="/storage/landing/logo_kharisma.png" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            try {
                const saved = localStorage.getItem('theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = saved ? saved === 'dark' : prefersDark;
                if (isDark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
</head>

<body class="h-full bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-50">
    <div id="auth-login"></div>
</body>

</html>
