<!doctype html>
<html lang="id" class="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Dashboard User</title>
    <link rel="shortcut icon" href="/storage/landing/logo_kharisma.png" type="image/x-icon">

    @vite(['resources/js/app.js'])
</head>

<body class="bg-slate-100 dark:bg-slate-950">
    @php
        $rootId = $mode === 'admin' ? 'admin-app' : 'dashboard-app';
    @endphp
    <div id="{{ $rootId }}" data-mode="{{ $mode }}"></div>
</body>

</html>
