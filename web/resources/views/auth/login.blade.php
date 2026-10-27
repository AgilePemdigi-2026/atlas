@extends('layouts.auth')

@section('title', 'Login – ATLAS')

@section('intro')
    <h1 class="font-display text-[40px]/[1.05] font-medium tracking-[-.04em] text-[#171B17] min-[900px]:text-[48px]/[1.05] dark:text-white">Welcome back</h1>
    <p class="mt-3 text-sm/[1.5] text-[#637064] dark:text-slate-400">Sign in to continue where you left off.</p>
@endsection

@section('form')
    @php
        $fields = [
            ['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'placeholder' => 'you@company.com', 'autocomplete' => 'email',
             'icon' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>'],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter your password', 'autocomplete' => 'current-password', 'toggle' => true,
             'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
        ];
    @endphp

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-3.5">
        @csrf

        @if (session('status'))
            <div class="rounded-[11px] border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
        @endif

        @foreach ($fields as $field)
            <div class="flex flex-col gap-1.5">
                <label for="{{ $field['name'] }}" class="text-[11px]/[1.5] font-semibold text-[#283329] dark:text-slate-200">{{ $field['label'] }}</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute top-1/2 left-4 size-[18px] -translate-y-1/2 text-[#38714D] dark:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $field['icon'] !!}</svg>
                    <input
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        type="{{ $field['type'] }}"
                        value="{{ $field['type'] === 'password' ? '' : old($field['name']) }}"
                        placeholder="{{ $field['placeholder'] }}"
                        autocomplete="{{ $field['autocomplete'] }}"
                        required
                        class="auth-input @if (!empty($field['toggle'])) pr-11 @endif @error($field['name']) border-red-500 @enderror"
                    >
                    @if (!empty($field['toggle']))
                        <button type="button" aria-label="Tampilkan atau sembunyikan password"
                            onclick="const i = document.getElementById('{{ $field['name'] }}'); i.type = i.type === 'password' ? 'text' : 'password'"
                            class="absolute top-1/2 right-3 grid size-8 -translate-y-1/2 place-items-center text-[#38714D] dark:text-emerald-300">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    @endif
                </div>
                @error($field['name'])
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-[11px] text-[#647066] dark:text-slate-400">
                <input type="checkbox" name="remember" class="size-4 rounded border-[#B9CBB7] accent-[#30A956]">
                Keep me signed in
            </label>
            <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-[#258C40] dark:text-emerald-300">Forgot password?</a>
        </div>

        <button type="submit" class="relative mt-1 flex h-[50px] w-full items-center justify-center rounded-[11px] bg-[linear-gradient(100deg,#30A956,#5CC247)] text-sm font-semibold text-white shadow-[0_8px_18px_rgba(49,148,60,.26)] hover:opacity-95">
            Sign in
            <span class="absolute right-5 text-xl" aria-hidden="true">→</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-[#7B837C] dark:text-slate-400">
        New to ATLAS? <a href="{{ route('register') }}" class="font-bold text-[#14843A] dark:text-emerald-300">Create an account</a>
    </p>
@endsection