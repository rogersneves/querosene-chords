@extends('layouts.app')

@section('title', __('ui.capo.title'))
@section('description', __('ui.capo.description'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Breadcrumb --}}
    <a href="{{ route('home') }}"
       class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-[#F5F5F5] transition-colors mb-8">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('ui.capo.back') }}
    </a>

    {{-- Header --}}
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9.05 3.44L5.06 7.43c-.59.59-.59 1.54 0 2.12l1.41 1.41L4.1 13.34c-1.56 1.56-1.56 4.09 0 5.66l1 1c1.56 1.56 4.09 1.56 5.65 0l2.38-2.38 1.41 1.41c.59.59 1.54.59 2.12 0l3.99-3.99c.59-.59.59-1.54 0-2.12L9.05 3.44zm-.71 14.14c-.78.78-2.05.78-2.83 0l-1-1c-.78-.78-.78-2.05 0-2.83l2.37-2.37 3.83 3.83-2.37 2.37zm7.78-5.66L12.24 16l-4.24-4.24 3.83-3.83 4.29 4.19z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-[#F5F5F5]">{{ __('ui.capo.title') }}</h1>
        </div>
        <p class="text-muted leading-relaxed">{{ __('ui.capo.subtitle') }}</p>
    </div>

    {{-- Calculator --}}
    @php
        $capoI18n = [
            'keys'          => __('ui.capo.keys'),
            'fret_ordinals' => __('ui.capo.fret_ordinals'),
            'result_title'  => __('ui.capo.result_title'),
            'result_desc'   => __('ui.capo.result_desc'),
            'col_sounds'    => __('ui.capo.col_sounds'),
            'map_footer'    => __('ui.capo.map_footer'),
        ];
    @endphp
    <div x-data="capoCalc()" class="space-y-6">

        {{-- Selects + swap --}}
        <div class="grid gap-3 items-end" style="grid-template-columns: 1fr auto 1fr">

            {{-- Quero tocar em (target) --}}
            <div>
                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">
                    {{ __('ui.capo.label_want') }}
                </label>
                <div class="relative">
                    <select x-model="target"
                            class="w-full appearance-none bg-surface border border-white/10 rounded-xl px-4 py-3 pr-9 text-[#F5F5F5] text-sm focus:outline-none focus:border-primary transition-colors cursor-pointer">
                        <template x-for="k in keys" :key="k.note">
                            <option :value="k.note" x-text="k.label"></option>
                        </template>
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </div>
            </div>

            {{-- Swap --}}
            <button @click="[source, target] = [target, source]"
                    class="w-10 h-[46px] flex items-center justify-center rounded-xl bg-surface border border-white/10 text-muted hover:text-[#F5F5F5] hover:border-white/25 transition-colors"
                    title="{{ __('ui.capo.swap_title') }}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </button>

            {{-- Sei tocar em (source) --}}
            <div>
                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">
                    {{ __('ui.capo.label_know') }}
                </label>
                <div class="relative">
                    <select x-model="source"
                            class="w-full appearance-none bg-surface border border-white/10 rounded-xl px-4 py-3 pr-9 text-[#F5F5F5] text-sm focus:outline-none focus:border-primary transition-colors cursor-pointer">
                        <template x-for="k in keys" :key="k.note">
                            <option :value="k.note" x-text="k.label"></option>
                        </template>
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Result card --}}
        <div class="rounded-2xl border p-6 transition-colors duration-200"
             :class="fret === 0
                 ? 'border-emerald-500/30 bg-emerald-500/5'
                 : 'border-primary/40 bg-primary/5'">

            {{-- No capo needed --}}
            <template x-if="fret === 0">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-[#F5F5F5]">{{ __('ui.capo.no_capo') }}</div>
                        <div class="text-sm text-muted mt-0.5">{{ __('ui.capo.no_capo_hint') }}</div>
                    </div>
                </div>
            </template>

            {{-- Capo at fret N --}}
            <template x-if="fret > 0">
                <div class="flex items-center gap-5">
                    <div class="shrink-0 w-20 h-20 rounded-2xl bg-primary/20 border-2 border-primary/40 flex flex-col items-center justify-center" style="margin-right:14px">
                        <span class="text-3xl font-black text-primary leading-none" x-text="fret + fretOrdinalSuffix"></span>
                        <span class="text-[10px] font-bold text-primary/70 mt-1 uppercase tracking-wide whitespace-nowrap">{{ __('ui.capo.badge_suffix') }}</span>
                    </div>
                    <div>
                        <div class="text-xl font-black text-[#F5F5F5]" x-text="resultTitle"></div>
                        <div class="text-sm text-muted mt-1 leading-relaxed" x-text="resultDesc"></div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Chord map --}}
        <template x-if="fret > 0">
            <div>
                <h2 class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">{{ __('ui.capo.map_title') }}</h2>
                <div class="bg-surface rounded-2xl border border-white/5 overflow-hidden">
                    <div class="grid grid-cols-2 border-b border-white/5 text-xs font-semibold text-[#555] uppercase tracking-wider">
                        <div class="px-5 py-3">{{ __('ui.capo.col_play') }}</div>
                        <div class="px-5 py-3 border-l border-white/5" x-text="soundsColLabel"></div>
                    </div>
                    <template x-for="(row, i) in mapping" :key="i">
                        <div class="grid grid-cols-2 border-t border-white/5 hover:bg-white/[0.03] transition-colors">
                            <div class="px-5 py-3.5 flex items-center gap-2.5">
                                <span class="text-xs font-mono text-[#444] w-6 shrink-0" x-text="row.degree"></span>
                                <span class="font-mono font-bold text-[#F5F5F5]" x-text="row.shape"></span>
                            </div>
                            <div class="px-5 py-3.5 flex items-center gap-2.5 border-l border-white/5">
                                <span class="text-xs font-mono text-[#444] w-6 shrink-0" x-text="row.degree"></span>
                                <span class="font-mono font-bold text-primary" x-text="row.sounds"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-[#555] mt-2.5 text-center leading-relaxed" x-text="mapFooter"></p>
            </div>
        </template>

        {{-- How to use --}}
        <div class="bg-surface rounded-2xl border border-white/5 p-5">
            <h2 class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">{{ __('ui.capo.tips_title') }}</h2>
            <ol class="space-y-2 text-sm text-muted">
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">1</span>
                    {{ __('ui.capo.tip_1') }}
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">2</span>
                    {{ __('ui.capo.tip_2') }}
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">3</span>
                    {{ __('ui.capo.tip_3') }}
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">4</span>
                    {{ __('ui.capo.tip_4') }}
                </li>
            </ol>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function capoCalc() {
    const i18n = {!! json_encode($capoI18n, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
    const CHROMATIC = ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];
    const SHARPS    = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const FLATS     = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];
    const FLAT_KEYS = new Set(['F', 'Bb', 'Eb', 'Ab', 'Db', 'Gb']);

    const keys = CHROMATIC.map((note, idx) => ({ note, label: i18n.keys[idx] }));

    const MAJOR_INTERVALS = [0, 2, 4, 5, 7, 9, 11];
    const QUALITIES       = ['', 'm', 'm', '', '', 'm', 'dim'];
    const DEGREE_LABELS   = ['I', 'ii', 'iii', 'IV', 'V', 'vi', 'vii°'];

    function noteName(idx, key) {
        return (FLAT_KEYS.has(key) ? FLATS : SHARPS)[((idx % 12) + 12) % 12];
    }

    function scaleChords(key) {
        const root = CHROMATIC.indexOf(key);
        return MAJOR_INTERVALS.map((interval, i) =>
            noteName(root + interval, key) + QUALITIES[i]
        );
    }

    function tpl(str, vars) {
        // Use split/join for global replace; caller must pass fretLabel before fret
        return Object.entries(vars).reduce((s, [k, v]) => s.split(':' + k).join(String(v)), str);
    }

    return {
        keys,
        target: 'C',
        source: 'G',

        get fret() {
            const t = CHROMATIC.indexOf(this.target);
            const s = CHROMATIC.indexOf(this.source);
            return ((t - s) + 12) % 12;
        },

        get fretLabel() {
            return i18n.fret_ordinals[this.fret] ?? String(this.fret);
        },

        get fretOrdinalSuffix() {
            return (i18n.fret_ordinals[this.fret] ?? '').replace(/^\d+/, '');
        },

        get sourceKey() { return keys.find(k => k.note === this.source); },
        get targetKey() { return keys.find(k => k.note === this.target); },

        get resultTitle() {
            return tpl(i18n.result_title, { fretLabel: this.fretLabel, fret: this.fret });
        },

        get resultDesc() {
            return tpl(i18n.result_desc, {
                fretLabel: this.fretLabel,
                fret:      this.fret,
                source:    this.sourceKey?.label ?? this.source,
                target:    this.targetKey?.label ?? this.target,
            });
        },

        get soundsColLabel() {
            return tpl(i18n.col_sounds, { fret: this.fret });
        },

        get mapFooter() {
            return tpl(i18n.map_footer, { fretLabel: this.fretLabel, fret: this.fret });
        },

        get mapping() {
            const sc = scaleChords(this.source);
            const tc = scaleChords(this.target);
            return sc.map((shape, i) => ({ shape, sounds: tc[i], degree: DEGREE_LABELS[i] }));
        },
    };
}
</script>
@endpush
