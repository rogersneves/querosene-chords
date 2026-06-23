@extends('layouts.app')
@section('title', __('ui.auth.mfa_title'))

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        {{-- Icon --}}
        <div class="flex justify-center mb-6">
            <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-black text-center mb-1">{{ __('ui.auth.mfa_title') }}</h1>
        <p class="text-muted text-sm text-center mb-8">{{ __('ui.auth.mfa_subtitle') }}</p>

        @if(session('resent'))
        <div class="bg-green-500/10 border border-green-500/20 text-green-400 rounded-xl px-4 py-3 text-sm mb-5 text-center">
            {{ __('ui.auth.mfa_resent') }}
        </div>
        @endif

        <form method="POST" action="{{ route('mfa.verify.store') }}" x-data>
            @csrf

            {{-- 6 digit inputs for better UX --}}
            <div class="flex gap-2 justify-center mb-6" x-data="codeInput()">
                @for($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="1"
                    x-ref="d{{ $i }}"
                    @input="onInput($event, {{ $i }})"
                    @keydown.backspace="onBackspace($event, {{ $i }})"
                    @paste.prevent="onPaste($event)"
                    class="w-12 h-14 text-center text-2xl font-mono font-black rounded-xl
                           bg-surface border-2 transition-colors focus:outline-none
                           {{ $errors->has('code') ? 'border-red-500' : 'border-white/10 focus:border-primary' }}"
                    autocomplete="one-time-code"
                >
                @endfor
                <input type="hidden" name="code" x-ref="hidden" :value="digits.join('')">
            </div>

            @error('code')
            <p class="text-red-400 text-sm text-center mb-4">{{ $message }}</p>
            @enderror

            {{-- Trust this browser --}}
            <label class="flex items-start gap-3 mb-6 cursor-pointer group">
                <input type="checkbox" name="trust_device" value="1"
                       class="mt-0.5 accent-primary shrink-0 w-4 h-4">
                <span class="text-sm text-muted group-hover:text-[#F5F5F5] transition-colors leading-snug">
                    {{ __('ui.auth.mfa_trust_device') }}
                </span>
            </label>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors">
                {{ __('ui.auth.mfa_confirm_btn') }}
            </button>
        </form>

        <div class="flex items-center justify-between mt-6 text-sm">
            <form method="POST" action="{{ route('mfa.resend') }}">
                @csrf
                <button type="submit" class="text-muted hover:text-primary transition-colors">
                    {{ __('ui.auth.mfa_resend_btn') }}
                </button>
            </form>
            <a href="{{ route('login') }}" class="text-muted hover:text-[#F5F5F5] transition-colors">
                {{ __('ui.auth.mfa_back') }}
            </a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function codeInput() {
    return {
        digits: Array(6).fill(''),
        onInput(e, i) {
            const v = e.target.value.replace(/\D/g, '').slice(-1);
            e.target.value = v;
            this.digits[i] = v;
            this.$refs.hidden.value = this.digits.join('');
            if (v && i < 5) this.$refs['d' + (i + 1)].focus();
        },
        onBackspace(e, i) {
            if (!e.target.value && i > 0) {
                this.digits[i - 1] = '';
                this.$refs['d' + (i - 1)].value = '';
                this.$refs['d' + (i - 1)].focus();
            }
        },
        onPaste(e) {
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            text.split('').forEach((ch, i) => {
                this.digits[i] = ch;
                if (this.$refs['d' + i]) this.$refs['d' + i].value = ch;
            });
            this.$refs.hidden.value = this.digits.join('');
            const next = Math.min(text.length, 5);
            if (this.$refs['d' + next]) this.$refs['d' + next].focus();
        },
    };
}
</script>
@endpush
