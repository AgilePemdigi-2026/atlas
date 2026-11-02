@extends('layouts.app')

@section('title', $testCase->name.' – ATLAS')

@section('content')
    <section class="mx-auto flex w-full max-w-[860px] flex-col gap-6 px-6 py-10">
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-lg/[1.55] font-semibold text-body dark:text-slate-100">{{ $testCase->name }}</h1>
                <x-console.status-badge :status="$testCase->status->value" />
            </div>
            <p class="text-[13px]/[1.55] text-subtle dark:text-slate-400">{{ $statusCopy }}</p>
        </div>

        <div role="tablist" aria-label="Hasil test case" class="flex gap-7 overflow-x-auto border-b border-line dark:border-slate-700">
            <button type="button" role="tab" id="tab-scenarios" aria-controls="panel-scenarios" aria-selected="true"
                class="-mb-px border-b-2 border-transparent px-1 py-4 text-sm whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                Scenario URL
            </button>
            <button type="button" role="tab" id="tab-script" aria-controls="panel-script" aria-selected="false"
                class="-mb-px border-b-2 border-transparent px-1 py-4 text-sm whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                Generated Script
            </button>
        </div>

        <div role="tabpanel" id="panel-scenarios" aria-labelledby="tab-scenarios">
            <table class="w-full text-left text-[13px] text-body dark:text-slate-200">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wide text-subtle dark:text-slate-400">
                        <th class="px-2 py-2">Skenario</th>
                        <th class="px-2 py-2">URL Awal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testCase->scenarios as $scenario)
                        <tr class="border-t border-line dark:border-slate-700">
                            <td class="px-2 py-2">{{ $scenario->name }}</td>
                            <td class="px-2 py-2">
                                @if ($scenario->scenario_url)
                                    <span class="break-all">{{ $scenario->scenario_url }}</span>
                                @else
                                    <span class="text-subtle dark:text-slate-400">Belum ada URL</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div role="tabpanel" id="panel-script" aria-labelledby="tab-script" hidden>
            @if ($script)
                @if ($meta)
                    <p class="mb-3 text-[11px] text-subtle dark:text-slate-400">
                        @if (! empty($meta['meta']['model']))
                            Model {{ $meta['meta']['model'] }} ·
                        @endif
                        attempts {{ $meta['meta']['attempts'] ?? 0 }}
                        @if (! empty($meta['meta']['dom_source']))
                            · dom_source {{ $meta['meta']['dom_source'] }}
                        @endif
                    </p>
                @endif

                <pre class="overflow-x-auto rounded-md border border-line bg-surface p-4 text-[12px]/[1.6] text-body dark:border-slate-700 dark:bg-night dark:text-slate-200"><code>{{ $script }}</code></pre>
            @else
                <p class="text-sm text-subtle dark:text-slate-400">Script belum tersedia.</p>
            @endif
        </div>

        <a href="{{ route('console') }}" class="text-xs font-semibold text-accent hover:underline dark:text-indigo-300">Kembali ke console</a>
    </section>
@endsection
