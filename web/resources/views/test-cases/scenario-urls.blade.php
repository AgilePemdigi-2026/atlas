@extends('layouts.app')

@section('title', $testCase->name.' - ATLAS')

@section('content')
    <section class="mx-auto flex w-full max-w-[860px] flex-col gap-6 px-6 py-10">
        <div class="flex flex-col gap-1">
            <h1 class="text-lg/[1.55] font-semibold text-body dark:text-slate-100">{{ $testCase->name }}</h1>
            <p class="text-[13px]/[1.55] text-subtle dark:text-slate-400">Lengkapi URL awal tiap skenario sebelum generate.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[13px] text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <x-console.auth-profile :test-case="$testCase" />

        <form method="POST" action="{{ route('test-cases.scenario-urls.generate', $testCase) }}" class="flex flex-col gap-5" data-generate-form>
            @csrf

            <table class="w-full text-left text-[13px] text-body dark:text-slate-200">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wide text-subtle dark:text-slate-400">
                        <th class="px-2 py-2">Skenario</th>
                        <th class="w-[55%] px-2 py-2">URL Awal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testCase->scenarios as $index => $scenario)
                        <tr class="border-t border-line align-middle dark:border-slate-700">
                            <td class="px-2 py-2">{{ $scenario->name }}</td>
                            <td class="px-2 py-2">
                                <input type="hidden" name="scenarios[{{ $index }}][scenario_id]" value="{{ $scenario->getKey() }}">
                                <input type="url" name="scenarios[{{ $index }}][scenario_url]" required
                                    value="{{ old("scenarios.$index.scenario_url", $scenario->scenario_url ?? $testCase->base_url) }}"
                                    aria-label="URL awal {{ $scenario->name }}" placeholder="https://app.example.com/login"
                                    class="h-9 w-full rounded-md border border-line bg-white px-3 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex items-center gap-3">
                <button type="submit" data-generate-button class="h-9 rounded-md bg-accent px-4 text-xs font-semibold text-white">Lanjut &amp; Generate</button>
                <p class="hidden text-xs text-subtle dark:text-slate-400" data-generate-hint>Sedang menghasilkan test case …</p>
            </div>
        </form>
    </section>

    <script>
        (function () {
            var form = document.querySelector('[data-generate-form]');
            var button = document.querySelector('[data-generate-button]');
            var hint = document.querySelector('[data-generate-hint]');

            if (!form || !button || !hint) {
                return;
            }

            form.addEventListener('submit', function () {
                button.disabled = true;
                button.classList.add('opacity-60');
                hint.classList.remove('hidden');
            });
        })();
    </script>
@endsection
