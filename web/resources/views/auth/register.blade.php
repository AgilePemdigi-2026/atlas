@extends('layouts.app')

@section('title', 'Register – ATLAS')

@php
    $fields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'placeholder' => 'Ristul', 'autocomplete' => 'name'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'ristulop@example.com', 'autocomplete' => 'email'],
        ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'placeholder' => '••••••••••••', 'autocomplete' => 'new-password', 'hint' => 'Gunakan minimal 8 karakter.'],
    ];
@endphp

@section('content')
    <main class="flex flex-col items-center gap-10 bg-surface px-6 py-12 min-[1100px]:flex-row min-[1100px]:gap-[100px] min-[1100px]:px-40 min-[1100px]:py-20 dark:bg-abyss">
        <section class="flex w-full min-w-0 flex-col gap-7 min-[1100px]:w-[500px] min-[1100px]:shrink-0">
            <div class="grid size-11 place-items-center">
                <x-story-icon />
            </div>
            <h1 class="text-[32px]/[1.2] text-body min-[1100px]:text-[44px]/[1.2] dark:text-white">Ubah User-Story Anda menjadi Automated Test Case</h1>

            <div class="flex flex-col gap-4 rounded-[10px] bg-night p-7">
                <div class="font-mono text-[13px]/[1.55] text-[#9AD9BD]">
                    <div>Given I am on the "/register" page</div>
                    <div>And I fill in "Name" with "Rizztul"</div>
                    <div>And I fill in "Email" with "ristulop@gmail.com"</div>
                    <div>And I fill in "Password" with "ristulristul"</div>
                    <div>When I press the "Register" button</div>
                    <div>Then I should see the message "Akun anda berhasil terdaftar"</div>
                    <div>And I should be redirected to the "/login" page</div>
                </div>
                <div class="font-mono text-xs/[1.55] text-[#94A4BE]">user story → Selenium (Automated Test Case)</div>
            </div>
        </section>

        <section class="w-full min-w-0 rounded-[10px] border border-line bg-white p-8 min-[1100px]:w-[460px] min-[1100px]:shrink-0 dark:border-slate-700 dark:bg-night">
            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
                @csrf

                <div class="flex flex-col gap-3">
                    <div class="text-xs/[1.55] font-semibold text-accent dark:text-indigo-300">AKUN</div>
                    <h2 class="text-[32px]/[1.2] text-body dark:text-white">Start With Your Story</h2>
                </div>

                @foreach ($fields as $field)
                    <div class="flex flex-col gap-2">
                        <label for="{{ $field['name'] }}" class="text-sm/[1.55] font-semibold text-body dark:text-slate-200">{{ $field['label'] }}</label>
                        <input
                            id="{{ $field['name'] }}"
                            name="{{ $field['name'] }}"
                            type="{{ $field['type'] }}"
                            value="{{ $field['type'] === 'password' ? '' : old($field['name']) }}"
                            placeholder="{{ $field['placeholder'] }}"
                            autocomplete="{{ $field['autocomplete'] }}"
                            required
                            class="h-11 w-full rounded-md border bg-white px-3 text-[13px] text-body outline-none placeholder:text-subtle/60 focus:border-accent focus:ring-2 focus:ring-accent/30 dark:bg-abyss dark:text-white @error($field['name']) border-red-500 @else border-line dark:border-slate-600 @enderror"
                        >
                        @error($field['name'])
                            <p class="text-xs/[1.55] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @elseif (isset($field['hint']))
                            <p class="text-xs/[1.55] text-subtle dark:text-slate-400">{{ $field['hint'] }}</p>
                        @enderror
                    </div>
                @endforeach

                <button type="submit" class="h-11 w-full rounded-md bg-accent text-sm font-semibold text-white hover:opacity-90">Register</button>

                <p class="text-center text-[13px] text-subtle dark:text-slate-400">
                    Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-accent dark:text-indigo-300">Login</a>
                </p>
            </form>
        </section>
    </main>
@endsection