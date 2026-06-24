@php $htmlLocale = ['pt' => 'pt-BR', 'en' => 'en', 'es' => 'es', 'fr' => 'fr'][app()->getLocale()] ?? 'pt-BR'; @endphp
<!DOCTYPE html>
<html lang="{{ $htmlLocale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-canvas text-[#F5F5F5] antialiased min-h-screen">
@yield('content')
@stack('scripts')
@livewireScripts
</body>
</html>
