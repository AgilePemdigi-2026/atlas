
@extends('layouts.auth')

@section('title', 'Create New Password – ATLAS')

@section('intro')
    <div class="grid size-[43px] place-items-center rounded-xl border border-[#D8EAD5] bg-white/65 text-[#2F7043] dark:border-white/15 dark:bg-white/5 dark:text-emerald-300">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
            <rect x="5" y="10" width="14" height="11" rx="2"/>
            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
        </svg>
    </div>

    <h1 class="mt-5 font-display text-[40px]/[1.05] font-medium tracking-[-.04em] text-[#171B17] dark:text-white">
        Create new password
    </h1>

    <p class="mt-3 text-sm text-[#637064] dark:text-slate-400">
        Enter your new password to recover your ATLAS account.
    </p>
@endsection

@section('form')
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-3.5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-semibold dark:text-white">
                New Password
            </label>

            <input
                id="password"
                name="password"
                type="password"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Enter new password"
                class="auth-input"
            >
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password_confirmation" class="text-sm font-semibold dark:text-white">
                Confirm New Password
            </label>

            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Confirm new password"
                class="auth-input"
            >
        </div>

        <button type="submit"
            class="mt-2 flex h-[50px] w-full items-center justify-center rounded-[11px] bg-[linear-gradient(100deg,#30A956,#5CC247)] text-sm font-semibold text-white hover:opacity-95">
            Reset Password
        </button>
    </form>

    <p class="mt-auto pt-10 text-center text-xs">
        <a href="{{ route('login') }}" class="font-bold text-[#14843A] dark:text-emerald-300">
            ← Back to sign in
        </a>
    </p>
@endsection