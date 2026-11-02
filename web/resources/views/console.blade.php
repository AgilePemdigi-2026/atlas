@extends('layouts.app')

@section('title', 'Console – ATLAS')

@section('content')
    <div class="flex min-h-[calc(100vh-72px)] flex-col min-[900px]:flex-row min-[1100px]:min-h-[calc(100vh-88px)]">
        <x-console.collection-sidebar :collections="$collections" />

        <section class="flex min-w-0 flex-1 flex-col gap-7 px-6 pb-10 min-[900px]:px-9">
            @if (session('status'))
                <div class="mt-6 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
            @endif

            <div role="tablist" aria-label="Pengaturan test" class="flex gap-7 overflow-x-auto border-b border-line dark:border-slate-700">
                <button type="button" role="tab" id="tab-story" data-tab="story" aria-controls="panel-story" aria-selected="true"
                    class="-mb-px border-b-2 border-transparent px-1 py-4 text-sm whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                    User Story
                </button>
                <button type="button" role="tab" id="tab-config" data-tab="config" aria-controls="panel-config" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-1 py-4 text-sm whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                    Config
                </button>
            </div>

            <div role="tabpanel" id="panel-story" data-panel="story" aria-labelledby="tab-story">
                {{-- Tahap 2: isi tab User Story --}}
                <p class="text-sm text-subtle dark:text-slate-400">Isi tab User Story dipasang di tahap 2.</p>
            </div>

            <div role="tabpanel" id="panel-config" data-panel="config" aria-labelledby="tab-config" hidden>
                {{-- Tab Config: menunggu desain --}}
                <p class="text-sm text-subtle dark:text-slate-400">Isi tab Config menunggu desain.</p>
            </div>
        </section>
    </div>

    <script>
        (function () {
            var tabs = document.querySelectorAll('[role="tab"]');
            var panels = document.querySelectorAll('[role="tabpanel"]');

            function show(name) {
                tabs.forEach(function (tab) {
                    tab.setAttribute('aria-selected', tab.dataset.tab === name ? 'true' : 'false');
                });
                panels.forEach(function (panel) {
                    panel.hidden = panel.dataset.panel !== name;
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () { show(tab.dataset.tab); });
            });

            // Elemen apa pun dengan data-open-tab="config" akan membuka tab itu (dipakai link "Buka Config →" di tahap 2)
            document.querySelectorAll('[data-open-tab]').forEach(function (el) {
                el.addEventListener('click', function () { show(el.dataset.openTab); });
            });
        })();
    </script>
@endsection