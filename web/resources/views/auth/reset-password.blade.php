@extends('layouts.auth')

@section('title', 'Reset password – ATLAS')

@section('intro')
    <div class="grid size-[43px] place-items-center rounded-xl border border-[#D8EAD5] bg-white/65 text-[#2F7043] dark:border-white/15 dark:bg-white/5 dark:text-emerald-300">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
    </div>
    <h1 class="mt-5 font-display text-[40px]/[1.05] font-medium tracking-[-.04em] text-[#171B17] min-[900px]:text-[48px]/[1.05] dark:text-white">Reset your password</h1>
    <p class="mt-3 text-sm/[1.5] text-[#637064] dark:text-slate-400">Enter the email you used to create your account.</p>
@endsection

@section('form')
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-3.5">
        @csrf

        @if (session('status'))
            <div class="rounded-[11px] border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
        @endif

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-[11px]/[1.5] font-semibold text-[#283329] dark:text-slate-200">Email address</label>
            <div class="relative">
                <svg class="pointer-events-none absolute top-1/2 left-4 size-[18px] -translate-y-1/2 text-[#38714D] dark:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    placeholder="you@company.com"
                    autocomplete="email"
                    autofocus
                    required
                    class="auth-input @error('email') border-red-500 @enderror"
                >
            </div>
            @error('email')
                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="relative mt-1 flex h-[50px] w-full items-center justify-center rounded-[11px] bg-[linear-gradient(100deg,#30A956,#5CC247)] text-sm font-semibold text-white shadow-[0_8px_18px_rgba(49,148,60,.26)] hover:opacity-95">
            Continue
            <span class="absolute right-5 text-xl" aria-hidden="true">→</span>
        </button>
    </form>

    <p class="mt-auto pt-10 text-center text-xs">
        <a href="{{ route('login') }}" class="font-bold text-[#14843A] dark:text-emerald-300">← Back to sign in</a>
    </p>
@endsection