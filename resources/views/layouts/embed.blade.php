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
<div id="qs-toast"
     style="display:none;position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);z-index:400;padding:.625rem 1rem;border-radius:.75rem;box-shadow:0 10px 25px rgba(0,0,0,.5);font-size:.875rem;font-weight:500;pointer-events:none;white-space:nowrap;transition:opacity .2s"
></div>
<script>
(function(){
    var el, _t;
    function show(msg, type) {
        if (!el) el = document.getElementById('qs-toast');
        if (!el) return;
        el.textContent = msg;
        if (type === 'removed') {
            el.style.background = '#1A1A1A';
            el.style.color = '#F5F5F5';
            el.style.border = '1px solid rgba(255,255,255,.1)';
        } else if (type === 'updated') {
            el.style.background = '#1d4ed8';
            el.style.color = '#fff';
            el.style.border = 'none';
        } else {
            el.style.background = '#15803d';
            el.style.color = '#fff';
            el.style.border = 'none';
        }
        el.style.display = '';
        el.style.opacity = '1';
        clearTimeout(_t);
        _t = setTimeout(function(){ el.style.opacity='0'; setTimeout(function(){ el.style.display='none'; },200); }, 2500);
    }
    window.addEventListener('show-toast', function(e){ show(e.detail.msg||'', e.detail.type||'success'); });
})();
</script>
</body>
</html>
