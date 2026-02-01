<!doctype html>
<html lang="id" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Halamn Admin</title>
  <link rel="shortcut icon" href="/storage/landing/logo_kharisma.png" type="image/x-icon">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-100 dark:bg-slate-950">
  <div
    id="admin-app"
    data-page="users"
    data-user-name="{{ auth()->user()->name ?? 'User' }}"
    data-user-photo="{{ auth()->user()->profile_photo_url ?? '' }}"
  ></div>

  <script>
    window.__AUTH__ = {
      user: @json(auth()->user())
    };
  </script>
</body>
</html>
