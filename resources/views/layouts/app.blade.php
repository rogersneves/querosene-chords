@php $htmlLocale = ['pt' => 'pt-BR', 'en' => 'en', 'es' => 'es', 'fr' => 'fr'][app()->getLocale()] ?? 'pt-BR'; @endphp
<!DOCTYPE html>
<html lang="{{ $htmlLocale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0D0D0D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Querosene Chords') — {{ __('ui.app.tagline') }}</title>
    <meta name="description" content="@yield('description', __('ui.app.description'))">
    <meta property="og:site_name" content="Querosene Chords">
    <meta property="og:title" content="@yield('title', 'Querosene Chords')">
    <meta property="og:description" content="@yield('description', __('ui.app.og_desc'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-canvas text-[#F5F5F5] antialiased min-h-screen flex flex-col">

{{-- ── Header ──────────────────────────────────────────────────────────── --}}
<header id="app-header" class="fixed top-0 inset-x-0 z-50 bg-surface border-b border-white/5 h-16">
    <div class="max-w-7xl mx-auto px-4 h-full flex items-center gap-4">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 group">
            <svg class="w-7 h-7 text-primary" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C8.5 6 6 9.5 6 13a6 6 0 0012 0c0-3.5-2.5-7-6-11zm0 16a4 4 0 01-4-4c0-2.2 1.4-4.5 4-7.4 2.6 2.9 4 5.2 4 7.4a4 4 0 01-4 4z"/>
            </svg>
            <span class="font-black text-[1.1rem] leading-none text-[#F5F5F5] group-hover:text-primary transition-colors">
                Querosene <span class="text-primary">Chords</span>
            </span>
        </a>

        {{-- Search --}}
        <div class="flex-1 min-w-0 max-w-sm">
            @livewire('header-search')
        </div>

        {{-- Nav --}}
        <nav class="hidden md:flex items-center gap-1 shrink-0 text-sm font-medium">

            {{-- Categorias dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @keydown.escape="open = false"
                    class="flex items-center gap-1 px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors"
                >
                    {{ __('ui.nav.categories') }}
                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="open = false"
                    class="absolute right-0 top-full mt-1 w-48 bg-surface border border-white/10 rounded-xl shadow-xl overflow-hidden"
                >
                    @foreach(\App\Models\Category::orderBy('name')->get() as $cat)
                    <a
                        href="{{ route('categories.show', $cat) }}"
                        @click="open = false"
                        class="flex items-center gap-2.5 px-4 py-2.5 text-[#F5F5F5] hover:bg-white/5 transition-colors text-sm"
                    >
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $cat->color }}"></span>
                        {{ $cat->name }}
                    </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('search') }}?tab=artists"
               class="px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors">
                {{ __('ui.nav.artists') }}
            </a>

            <a href="{{ route('songs.browse') }}"
               class="px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors">
                {{ __('ui.nav.browse') }}
            </a>

            <a href="{{ route('tools.capo') }}"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9.05 3.44L5.06 7.43c-.59.59-.59 1.54 0 2.12l1.41 1.41L4.1 13.34c-1.56 1.56-1.56 4.09 0 5.66l1 1c1.56 1.56 4.09 1.56 5.65 0l2.38-2.38 1.41 1.41c.59.59 1.54.59 2.12 0l3.99-3.99c.59-.59.59-1.54 0-2.12L9.05 3.44zm-.71 14.14c-.78.78-2.05.78-2.83 0l-1-1c-.78-.78-.78-2.05 0-2.83l2.37-2.37 3.83 3.83-2.37 2.37zm7.78-5.66L12.24 16l-4.24-4.24 3.83-3.83 4.29 4.19z"/>
                </svg>
                Capo
            </a>

            {{-- Caderno / Auth --}}
            @auth
            <a href="{{ route('setlists.index') }}"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                {{ __('ui.nav.my_setlists') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                        class="px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors text-sm">
                    {{ __('ui.nav.logout') }}
                </button>
            </form>
            @else
            <a href="{{ route('login') }}"
               class="px-3 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors">
                {{ __('ui.nav.login') }}
            </a>
            @endauth

            {{-- Seletor de idioma --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @keydown.escape="open = false"
                    class="flex items-center gap-1 px-2.5 py-2 rounded-lg text-[#888] hover:text-[#F5F5F5] hover:bg-white/5 transition-colors text-xs font-medium"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                    {{ strtoupper(app()->getLocale()) }}
                    <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="open = false"
                    class="absolute right-0 top-full mt-1 w-36 bg-surface border border-white/10 rounded-xl shadow-xl overflow-hidden"
                >
                    @foreach(['pt' => 'Português', 'en' => 'English', 'es' => 'Español', 'fr' => 'Français'] as $code => $name)
                    <a
                        href="{{ route('locale.set', $code) }}"
                        @click="open = false"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm transition-colors
                            {{ app()->getLocale() === $code ? 'text-primary bg-primary/10' : 'text-[#F5F5F5] hover:bg-white/5' }}"
                    >
                        {{ $name }}
                    </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>
</header>

{{-- ── Main ─────────────────────────────────────────────────────────────── --}}
<main id="app-main" class="pt-16 flex-1">
    @hasSection('content')
        @yield('content')
    @else
        {{ $slot ?? '' }}
    @endif
</main>

{{-- ── Footer ───────────────────────────────────────────────────────────── --}}
<footer id="app-footer" class="bg-surface border-t border-white/5 mt-auto">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-6">
            <div class="flex flex-col items-center md:items-start gap-1">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.5 6 6 9.5 6 13a6 6 0 0012 0c0-3.5-2.5-7-6-11zm0 16a4 4 0 01-4-4c0-2.2 1.4-4.5 4-7.4 2.6 2.9 4 5.2 4 7.4a4 4 0 01-4 4z"/>
                    </svg>
                    <span class="font-black text-[#F5F5F5]">Querosene <span class="text-primary">Chords</span></span>
                    <span class="text-muted text-xs">{{ __('ui.app.tagline') }}</span>
                </div>
                {{-- Seletor de idioma (mobile) --}}
                <div class="flex gap-2 mt-2 md:hidden">
                    @foreach(['pt' => 'PT', 'en' => 'EN', 'es' => 'ES', 'fr' => 'FR'] as $code => $label)
                    <a href="{{ route('locale.set', $code) }}"
                       class="text-xs px-2 py-1 rounded transition-colors
                           {{ app()->getLocale() === $code ? 'text-primary font-bold' : 'text-muted hover:text-[#F5F5F5]' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
            <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-muted">
                <a href="#" class="hover:text-[#F5F5F5] transition-colors">{{ __('ui.footer.about') }}</a>
                <a href="#" class="hover:text-[#F5F5F5] transition-colors">{{ __('ui.footer.contact') }}</a>
                <a href="#" class="hover:text-[#F5F5F5] transition-colors">{{ __('ui.footer.privacy') }}</a>
                <a href="{{ route('tools.capo') }}" class="hover:text-[#F5F5F5] transition-colors">{{ __('ui.capo.title') }}</a>
                <a href="{{ route('sitemap') }}" class="hover:text-[#F5F5F5] transition-colors">{{ __('ui.footer.sitemap') }}</a>
            </nav>
        </div>
        <p class="text-center text-muted text-xs mt-6">
            &copy; {{ date('Y') }} Querosene Chords. {{ __('ui.footer.copyright') }}
        </p>
    </div>
</footer>

@stack('scripts')

{{-- ── Toast global ────────────────────────────────────────────────── --}}
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

{{-- ── Modal global de cifra ───────────────────────────────────────── --}}
<div
    id="song-modal"
    x-data="{ open: false, src: '', title: '' }"
    @open-song-modal.window="
        title = $event.detail.title;
        src   = $event.detail.url + ($event.detail.url.includes('?') ? '&' : '?') + 'embed=1';
        open  = true;
        document.documentElement.classList.add('overflow-hidden')
    "
    @keydown.escape.window="if(open){ open = false; src = ''; document.documentElement.classList.remove('overflow-hidden') }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 flex flex-col bg-canvas"
    style="display:none; z-index:200"
>
    <div class="flex items-center gap-3 px-4 h-12 border-b border-white/10 bg-surface shrink-0">
        <button @click="open = false; src = ''; document.documentElement.classList.remove('overflow-hidden')"
                class="p-1.5 rounded-lg hover:bg-white/10 transition-colors shrink-0 text-[#F5F5F5]">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <span class="text-sm font-semibold truncate" x-text="title"></span>
        <a :href="src.replace(/[?&]embed=1/, '')" target="_blank" rel="noopener"
           class="ml-auto p-1.5 rounded-lg text-muted hover:text-[#F5F5F5] hover:bg-white/10 transition-colors shrink-0"
           title="Abrir em nova aba">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
            </svg>
        </a>
    </div>
    <iframe
        :src="open ? src : 'about:blank'"
        class="flex-1 w-full border-0"
        frameborder="0"
        allow="autoplay; encrypted-media; fullscreen"
        allowfullscreen
    ></iframe>
</div>

<script>
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    if (link.target === '_blank') return;
    if (!link.href.includes('/cifras/')) return;

    e.preventDefault();

    const title = link.dataset.title
        || link.querySelector('h3, [class*="font-bold"], [class*="font-semibold"]')?.textContent.trim()
        || link.textContent.trim().split('\n')[0].trim();

    window.dispatchEvent(new CustomEvent('open-song-modal', {
        detail: { url: link.href, title }
    }));
});
</script>
</body>
</html>
