@extends('layouts.app')
@section('title', __('ui.auth.login_title'))

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
        <h1 class="text-2xl font-black text-center mb-1">{{ __('ui.auth.login_title') }}</h1>
        <p class="text-muted text-sm text-center mb-8">{{ __('ui.auth.login_subtitle') }}</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('ui.auth.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full bg-surface border border-white/10 rounded-xl px-4 py-3 text-sm
                              focus:outline-none focus:border-primary transition-colors
                              @error('email') border-red-500 @enderror">
                @error('email')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('ui.auth.password') }}</label>
                <input type="password" name="password" required
                       class="w-full bg-surface border border-white/10 rounded-xl px-4 py-3 text-sm
                              focus:outline-none focus:border-primary transition-colors">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="accent-primary">
                <label for="remember" class="text-sm text-muted">{{ __('ui.auth.remember') }}</label>
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors">
                {{ __('ui.auth.login_btn') }}
            </button>
        </form>

        <p class="text-center text-sm text-muted mt-6">
            {{ __('ui.auth.no_account') }}
            <a href="{{ route('register') }}" class="text-primary hover:text-secondary transition-colors">
                {{ __('ui.auth.register_link') }}
            </a>
        </p>
    </div>
</div>
@endsection
