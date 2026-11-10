@extends('layouts.app')

@section('title', 'Console - ATLAS')

@section('content')
    <div class="flex min-h-[calc(100vh-72px)] flex-col min-[900px]:flex-row min-[1100px]:min-h-[calc(100vh-88px)]">
        <x-console.collection-sidebar :collections="$collections" :selected="$selected" :active-collection-id="$activeCollectionId" />

        <section class="flex min-w-0 flex-1 flex-col gap-7 px-6 pb-10 min-[900px]:px-9">
            @if (session('status'))
                <div
                    class="mt-6 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                    {{ session('status') }}</div>
            @endif

            <div role="tablist" aria-label="Pengaturan test"
                class="flex gap-7 overflow-x-auto border-b border-line dark:border-slate-700">
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
                {{-- Tahap 2: isi tab User Story
                <p class="text-sm text-subtle dark:text-slate-400">Isi tab User Story dipasang di tahap 2.</p> --}}

                <form method="POST" action="{{ route('test-cases.store') }}" class="mt-6 flex max-w-3xl flex-col gap-4">
                    @csrf

                    <input type="hidden" name="collection_id" value="{{ $activeCollectionId }}">

                    <div class="flex flex-col gap-1.5">
                        <label for="test-case-name" class="text-sm font-semibold text-body dark:text-slate-100">
                            Nama Test Case
                        </label>
                        <input id="test-case-name" name="name" type="text" value="{{ old('name') }}" required
                            maxlength="256"
                            class="rounded-md border border-line bg-white px-3 py-2 text-sm text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                        @error('name')
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="user-story" class="text-sm font-semibold text-body dark:text-slate-100">
                            User Story
                        </label>
                        <textarea id="user-story" name="user_story" rows="4" required
                            class="rounded-md border border-line bg-white px-3 py-2 text-sm text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">{{ old('user_story') }}</textarea>
                        @error('user_story')
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="acceptance-criteria" class="text-sm font-semibold text-body dark:text-slate-100">
                            Acceptance criteria
                        </label>
                        <textarea id="acceptance-criteria" name="acceptance_criteria" rows="6" required
                            class="rounded-md border border-line bg-white px-3 py-2 text-sm text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">{{ old('acceptance_criteria') }}</textarea>
                        @error('acceptance_criteria')
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="base-url" class="text-sm font-semibold text-body dark:text-slate-100">
                            Base URL (opsional)
                        </label>
                        <input id="base-url" name="base_url" type="url" value="{{ old('base_url') }}"
                            class="rounded-md border border-line bg-white px-3 py-2 text-sm text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                        @error('base_url')
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button type="submit" class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white">
                            Generate Test
                        </button>
                    </div>
                </form>


                @if ($selected)
                    @php $hasScript = $selected->generatedScript !== null; @endphp
                    <div class="mt-6 flex flex-col gap-4 border-t border-line pt-4 dark:border-slate-700">
                        <div role="tablist" aria-label="Hasil generate"
                            class="flex gap-7 border-b border-line dark:border-slate-700">
                            <button type="button" role="tab" id="subtab-scenario" data-subtab="scenario"
                                aria-controls="subpanel-scenario" aria-selected="{{ $hasScript ? 'false' : 'true' }}"
                                class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                Scenario URL
                            </button>
                            <button type="button" role="tab" id="subtab-script" data-subtab="script"
                                aria-controls="subpanel-script" aria-selected="{{ $hasScript ? 'true' : 'false' }}"
                                class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                Generated Script
                            </button>
                        </div>

                        <div role="tabpanel" id="subpanel-scenario" data-subpanel="scenario"
                            aria-labelledby="subtab-scenario" {{ $hasScript ? 'hidden' : '' }}>
                            {{-- F8 (Hiero): tabel Scenario URL dipasang di sini --}}
                            <p class="text-sm text-subtle dark:text-slate-400">Tabel Scenario URL dipasang di F8.</p>
                        </div>

                        <div role="tabpanel" id="subpanel-script" data-subpanel="script" aria-labelledby="subtab-script"
                            {{ $hasScript ? '' : 'hidden' }}>
                            {{-- F15: Generated Script --}}
                            <x-console.script-review :test-case="$selected" />
                        </div>
                    </div>
                @endif
            </div>

            <div role="tabpanel" id="panel-config" data-panel="config" aria-labelledby="tab-config" hidden>
                @if ($selected)
                    <div class="mt-6 flex max-w-3xl flex-col gap-3">
                        <x-console.auth-profile :test-case="$selected" />
                        <a href="{{ route('test-cases.scenario-urls', $selected) }}"
                            class="inline-flex h-9 w-fit items-center rounded-md bg-accent px-4 text-xs font-semibold text-white hover:opacity-90">Buka Config &amp; Generate</a>
                    </div>
                @else
                    <p class="mt-6 text-sm text-subtle dark:text-slate-400">Pilih test case di sidebar untuk mengatur Auth Profile.</p>
                @endif
            </div>
        </section>
    </div>

    <script>
        (function() {
            var tabs = document.querySelectorAll('[data-tab]');
            var panels = document.querySelectorAll('[data-panel]');

            function show(name) {
                tabs.forEach(function(tab) {
                    tab.setAttribute('aria-selected', tab.dataset.tab === name ? 'true' : 'false');
                });
                panels.forEach(function(panel) {
                    panel.hidden = panel.dataset.panel !== name;
                });
            }

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    show(tab.dataset.tab);
                });
            });

            var subtabs = document.querySelectorAll('[data-subtab]');
            var subpanels = document.querySelectorAll('[data-subpanel]');

            function showSub(name) {
                subtabs.forEach(function(tab) {
                    tab.setAttribute('aria-selected', tab.dataset.subtab === name ? 'true' : 'false');
                });
                subpanels.forEach(function(panel) {
                    panel.hidden = panel.dataset.subpanel !== name;
                });
            }

            subtabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    showSub(tab.dataset.subtab);
                });
            });

            // Elemen apa pun dengan data-open-tab="config" akan membuka tab itu (dipakai link "Buka Config →" di tahap 2)
            document.querySelectorAll('[data-open-tab]').forEach(function(el) {
                el.addEventListener('click', function() {
                    show(el.dataset.openTab);
                });
            });
        })();
    </script>
@endsection
