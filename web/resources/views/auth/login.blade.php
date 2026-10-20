@extends('layouts.app')

@section('title', 'Login – ATLAS')

@section('content')
    <main class="flex flex-col items-center gap-10 bg-surface px-6 py-12 min-[1100px]:flex-row min-[1100px]:gap-[100px] min-[1100px]:px-40 min-[1100px]:py-20 dark:bg-abyss">
        <section class="flex w-full min-w-0 flex-col gap-7 min-[1100px]:w-[500px] min-[1100px]:shrink-0">
            <div class="grid size-11 place-items-center">
                <x-story-icon />
            </div>
            <h1 class="text-[32px]/[1.2] text-body min-[1100px]:text-[44px]/[1.2] dark:text-white">Ubah User-Story Anda menjadi Automated Test Case</h1>

            <div class="flex flex-col gap-4 rounded-[10px] bg-night p-7">
                <div class="font-mono text-[13px]/[1.55] text-[#9AD9BD]">
                    <div>Given I am on the "/login" page</div>
                    <div>And I fill in "Email" with "ristulop@gmail.com"</div>
                    <div>And I fill in "Password" with "ristulristul"</div>
                    <div>When I press the "Login" button</div>
                    <div>Then I should be redirected to the "/" page</div>
                </div>
                <div class="font-mono text-xs/[1.55] text-[#94A4BE]">user story → Selenium (Automated Test Case)</div>
            </div>
        </section>

        <section class="w-full min-w-0 rounded-[10px] border border-line bg-white p-8 min-[1100px]:w-[460px] min-[1100px]:shrink-0 dark:border-slate-700 dark:bg-night">
            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
                @csrf

                <div class="flex flex-col gap-3">
                    <h2 class="text-[32px]/[1.2] text-body dark:text-white">Selamat datang kembali.</h2>
                    <p class="text-sm/[1.55] text-subtle dark:text-slate-400">Login untuk melanjutkan pekerjaan Anda di Console.</p>
                </div>

                @if (session('status'))
                    <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
                @endif

                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm/[1.55] font-semibold text-body dark:text-slate-200">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="ristulop@example.com" autocomplete="email" required autofocus
                        class="h-11 w-full rounded-md border bg-white px-3 text-[13px] text-body outline-none placeholder:text-subtle/60 focus:border-accent focus:ring-2 focus:ring-accent/30 dark:bg-abyss dark:text-white @error('email') border-red-500 @else border-line dark:border-slate-600 @enderror">
                    @error('email')
                        <p class="text-xs/[1.55] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm/[1.55] font-semibold text-body dark:text-slate-200">Password</label>
                    <input id="password" name="password" type="password" placeholder="••••••••••••" autocomplete="current-password" required
                        class="h-11 w-full rounded-md border border-line bg-white px-3 text-[13px] text-body outline-none placeholder:text-subtle/60 focus:border-accent focus:ring-2 focus:ring-accent/30 dark:border-slate-600 dark:bg-abyss dark:text-white">
                </div>

                <div class="text-right">
                    <a href="#" class="text-[13px] font-medium text-accent dark:text-indigo-300">Lupa Password</a>
                </div>

                <button type="submit" class="h-11 w-full rounded-md bg-accent text-sm font-semibold text-white hover:opacity-90">Login</button>

                <p class="text-center text-[13px] text-subtle dark:text-slate-400">
                    Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-accent dark:text-indigo-300">Register</a>
                </p>
            </form>
        </section>
    </main>
@endsection
