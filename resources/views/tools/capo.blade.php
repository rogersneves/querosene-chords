@extends('layouts.app')

@section('title', 'Calculadora de Capo')
@section('description', 'Descubra em qual casa colocar o capo para tocar na tonalidade que você quiser usando os acordes que já conhece.')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Breadcrumb --}}
    <a href="{{ route('home') }}"
       class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-[#F5F5F5] transition-colors mb-8">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Início
    </a>

    {{-- Header --}}
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9.05 3.44L5.06 7.43c-.59.59-.59 1.54 0 2.12l1.41 1.41L4.1 13.34c-1.56 1.56-1.56 4.09 0 5.66l1 1c1.56 1.56 4.09 1.56 5.65 0l2.38-2.38 1.41 1.41c.59.59 1.54.59 2.12 0l3.99-3.99c.59-.59.59-1.54 0-2.12L9.05 3.44zm-.71 14.14c-.78.78-2.05.78-2.83 0l-1-1c-.78-.78-.78-2.05 0-2.83l2.37-2.37 3.83 3.83-2.37 2.37zm7.78-5.66L12.24 16l-4.24-4.24 3.83-3.83 4.29 4.19z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-[#F5F5F5]">Calculadora de Capo</h1>
        </div>
        <p class="text-muted leading-relaxed">
            Descubra em qual casa colocar o capo para tocar na tonalidade que quiser
            usando os acordes que você já conhece.
        </p>
    </div>

    {{-- Calculator --}}
    <div x-data="capoCalc()" class="space-y-6">

        {{-- Selects + swap --}}
        <div class="grid gap-3 items-end" style="grid-template-columns: 1fr auto 1fr">

            {{-- Quero tocar em (target) --}}
            <div>
                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">
                    Quero tocar em
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
                    title="Inverter">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </button>

            {{-- Sei tocar em (source) --}}
            <div>
                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">
                    Sei tocar em
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
                        <div class="text-lg font-bold text-[#F5F5F5]">Sem capo necessário</div>
                        <div class="text-sm text-muted mt-0.5">Você já conhece os acordes nessa tonalidade!</div>
                    </div>
                </div>
            </template>

            {{-- Capo at fret N --}}
            <template x-if="fret > 0">
                <div class="flex items-center gap-5">
                    <div class="shrink-0 w-20 h-20 rounded-2xl bg-primary/20 border-2 border-primary/40 flex flex-col items-center justify-center">
                        <span class="text-3xl font-black text-primary leading-none" x-text="fret"></span>
                        <span class="text-[10px] font-bold text-primary/70 mt-1 uppercase tracking-wide">ª casa</span>
                    </div>
                    <div>
                        <div class="text-xl font-black text-[#F5F5F5]">
                            Capo na <span x-text="fretLabel"></span> casa
                        </div>
                        <div class="text-sm text-muted mt-1 leading-relaxed">
                            Toque os acordes de
                            <span class="text-[#F5F5F5] font-semibold" x-text="sourceKey.label"></span>
                            com o capo na <span x-text="fretLabel"></span> casa
                            para soar em
                            <span class="text-primary font-semibold" x-text="targetKey.label"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Chord map --}}
        <template x-if="fret > 0">
            <div>
                <h2 class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">Mapa de acordes</h2>
                <div class="bg-surface rounded-2xl border border-white/5 overflow-hidden">
                    <div class="grid border-b border-white/5 text-xs font-semibold text-[#555] uppercase tracking-wider"
                         style="grid-template-columns: 2rem 1fr 2rem 1fr">
                        <div class="col-span-2 px-5 py-3">Você toca (forma)</div>
                        <div class="col-span-2 px-5 py-3 border-l border-white/5">Soa como (capo <span x-text="fret"></span>)</div>
                    </div>
                    <template x-for="(row, i) in mapping" :key="i">
                        <div class="grid border-t border-white/5 hover:bg-white/[0.03] transition-colors"
                             style="grid-template-columns: 2rem 1fr 2rem 1fr">
                            <div class="flex items-center justify-center py-3.5 pl-4 text-xs font-mono text-[#444]" x-text="row.degree"></div>
                            <div class="flex items-center py-3.5 pr-5 font-mono font-bold text-[#F5F5F5]" x-text="row.shape"></div>
                            <div class="flex items-center justify-center py-3.5 pl-4 text-xs font-mono text-[#444] border-l border-white/5" x-text="row.degree"></div>
                            <div class="flex items-center py-3.5 pr-5 font-mono font-bold text-primary" x-text="row.sounds"></div>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-[#555] mt-2.5 text-center leading-relaxed">
                    Com o capo na <span x-text="fretLabel"></span> casa, cada forma que você toca soa
                    <span x-text="fret"></span> semitons mais alto.
                </p>
            </div>
        </template>

        {{-- How to use --}}
        <div class="bg-surface rounded-2xl border border-white/5 p-5">
            <h2 class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">Como usar o capo</h2>
            <ol class="space-y-2 text-sm text-muted">
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">1</span>
                    Escolha a tonalidade em que a música está (ou que você quer que ela soe).
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">2</span>
                    Escolha a tonalidade em que você já sabe os acordes — preferencialmente uma tonalidade aberta como G, D, A, E ou C.
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">3</span>
                    Prenda o capo logo atrás do traste de metal indicado.
                </li>
                <li class="flex gap-3 items-start">
                    <span class="text-primary font-black shrink-0 w-4 text-right">4</span>
                    Toque normalmente as formas que você já conhece — elas soarão na nova tonalidade automaticamente.
                </li>
            </ol>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function capoCalc() {
    const CHROMATIC = ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];
    const SHARPS    = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const FLATS     = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];
    const FLAT_KEYS = new Set(['F', 'Bb', 'Eb', 'Ab', 'Db', 'Gb']);

    const KEYS = [
        { note: 'C',  label: 'C (Dó)'         },
        { note: 'C#', label: 'C# / Db (Dó#)'   },
        { note: 'D',  label: 'D (Ré)'           },
        { note: 'Eb', label: 'Eb (Mib)'         },
        { note: 'E',  label: 'E (Mi)'           },
        { note: 'F',  label: 'F (Fá)'           },
        { note: 'F#', label: 'F# / Gb (Fá#)'   },
        { note: 'G',  label: 'G (Sol)'          },
        { note: 'Ab', label: 'Ab (Láb)'         },
        { note: 'A',  label: 'A (Lá)'           },
        { note: 'Bb', label: 'Bb (Sib)'         },
        { note: 'B',  label: 'B (Si)'           },
    ];

    const MAJOR_INTERVALS = [0, 2, 4, 5, 7, 9, 11];
    const QUALITIES       = ['', 'm', 'm', '', '', 'm', 'dim'];
    const DEGREE_LABELS   = ['I', 'ii', 'iii', 'IV', 'V', 'vi', 'vii°'];
    const FRET_ORDINALS   = ['', '1ª', '2ª', '3ª', '4ª', '5ª', '6ª', '7ª', '8ª', '9ª', '10ª', '11ª'];

    function noteName(idx, key) {
        return (FLAT_KEYS.has(key) ? FLATS : SHARPS)[((idx % 12) + 12) % 12];
    }

    function scaleChords(key) {
        const root = CHROMATIC.indexOf(key);
        return MAJOR_INTERVALS.map((interval, i) =>
            noteName(root + interval, key) + QUALITIES[i]
        );
    }

    return {
        keys:   KEYS,
        target: 'C',
        source: 'G',

        get fret() {
            const t = CHROMATIC.indexOf(this.target);
            const s = CHROMATIC.indexOf(this.source);
            return ((t - s) + 12) % 12;
        },

        get fretLabel() {
            return FRET_ORDINALS[this.fret] ?? (this.fret + 'ª');
        },

        get sourceKey() {
            return KEYS.find(k => k.note === this.source);
        },

        get targetKey() {
            return KEYS.find(k => k.note === this.target);
        },

        get mapping() {
            const sc = scaleChords(this.source);
            const tc = scaleChords(this.target);
            return sc.map((shape, i) => ({
                shape,
                sounds: tc[i],
                degree: DEGREE_LABELS[i],
            }));
        },
    };
}
</script>
@endpush
