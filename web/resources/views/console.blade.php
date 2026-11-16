@extends('layouts.app')

@section('without-footer')
@endsection

@section('title', 'Console - ATLAS')

@section('content')
    <div class="min-h-[calc(100vh-72px)] bg-canvas min-[1100px]:min-h-[calc(100vh-88px)] dark:bg-abyss">
        <div class="flex min-h-[calc(100vh-72px)] flex-col min-[900px]:flex-row min-[1100px]:min-h-[calc(100vh-88px)]">
            <x-console.collection-sidebar :collections="$collections" :selected="$selected" :active-collection-id="$activeCollectionId" />

            <section class="min-w-0 flex-1 px-4 py-6 min-[900px]:px-6 min-[900px]:py-8 min-[1100px]:px-8">
                @if (session('status'))
                    <div
                        class="console-enter mb-5 rounded-lg border border-green-200 bg-green-50 px-3.5 py-2.5 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                        {{ session('status') }}</div>
                @endif

                @if ($collections->isEmpty())
                    <div data-empty-state class="flex flex-1 items-start justify-center pt-8">
                        <div class="console-card w-full max-w-md p-6">
                            <h2 class="font-display text-base font-semibold text-body dark:text-white">Belum ada test case.</h2>
                            <p class="console-hint mt-2">Buat test case pertama Anda untuk mengubah user story menjadi skrip Selenium.</p>

                            <form method="POST" action="{{ route('test-cases.drafts') }}" class="mt-5 flex flex-col gap-2">
                                @csrf
                                <input type="hidden" name="source" value="empty">
                                <div class="flex items-center gap-2">
                                    <input name="name" type="text" value="{{ old('name') }}" required maxlength="256" placeholder="Nama test case"
                                        aria-label="Nama test case" class="console-field min-w-0 flex-1">
                                    <button type="submit" class="console-btn-primary flex-none">Buat</button>
                                </div>
                                @if ($errors->createFirstTestCase->has('name'))
                                    <p class="console-hint text-red-600 dark:text-red-400">{{ $errors->createFirstTestCase->first('name') }}</p>
                                @endif
                            </form>

                            <p class="console-hint mt-3">
                                Atau
                                <button type="button" data-create-collection
                                    class="font-semibold text-accent transition hover:underline dark:text-indigo-300">buat collection baru</button>
                                terlebih dahulu.
                            </p>
                        </div>
                    </div>
                @endif

                <div data-workspace @if ($collections->isEmpty()) hidden @endif class="flex flex-col gap-5">
                    <div class="console-card console-enter p-5 min-[900px]:p-6">
                        <div role="tablist" aria-label="Pengaturan test"
                            class="flex gap-7 border-b border-edge dark:border-slate-800">
                            <button type="button" role="tab" id="tab-story" data-tab="story" aria-controls="panel-story"
                                aria-selected="true"
                                class="-mb-px border-b-2 border-transparent px-1 py-3.5 text-sm font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                User Story
                            </button>
                            <button type="button" role="tab" id="tab-config" data-tab="config" aria-controls="panel-config"
                                aria-selected="false"
                                class="-mb-px border-b-2 border-transparent px-1 py-3.5 text-sm font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                Config
                            </button>
                        </div>

                        <div role="tabpanel" id="panel-story" data-panel="story" aria-labelledby="tab-story" class="console-panel pt-6">
                            @php
                                $isDraft = $selected !== null && $selected->user_story === null;
                                $formBag = $isDraft ? 'updateTestCase' : 'default';
                                $bag = $errors->{$formBag};
                                $formAction = $isDraft ? route('test-cases.update', $selected) : route('test-cases.store');
                            @endphp

                            @if ($isDraft)
                                <form method="POST" action="{{ $formAction }}" class="flex flex-col gap-5">
                                    @csrf

                                    @if ($isDraft)
                                        @method('PATCH')
                                    @endif

                                    <input type="hidden" name="collection_id"
                                        value="{{ $isDraft ? $selected->collection_id : $activeCollectionId }}">

                                    <div class="grid gap-5 min-[900px]:grid-cols-[minmax(0,1fr)_240px]">
                                        <div class="flex min-w-0 flex-col gap-4">
                                            <div class="flex flex-col gap-1.5">
                                                <label for="test-case-name" class="console-label">
                                                    Nama Test Case
                                                </label>
                                                <input id="test-case-name" name="name" type="text"
                                                    value="{{ old('name', $isDraft ? $selected->name : '') }}" required
                                                    maxlength="256" class="console-field">
                                                @if ($bag->has('name'))
                                                    <p class="console-hint text-red-600 dark:text-red-400">{{ $bag->first('name') }}</p>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-1.5">
                                                <label for="user-story" class="console-label">
                                                    User Story
                                                </label>
                                                <textarea id="user-story" name="user_story" rows="4" required
                                                    @if ($isDraft && request()->boolean('focus')) autofocus @endif
                                                    class="console-area min-h-[96px]">{{ old('user_story', $isDraft ? ($selected->user_story ?? '') : '') }}</textarea>
                                                @if ($bag->has('user_story'))
                                                    <p class="console-hint text-red-600 dark:text-red-400">{{ $bag->first('user_story') }}</p>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-1.5">
                                                <label for="acceptance-criteria" class="console-label">
                                                    Acceptance criteria
                                                </label>
                                                <textarea id="acceptance-criteria" name="acceptance_criteria" rows="6" required
                                                    class="console-area min-h-[128px]">{{ old('acceptance_criteria', $isDraft ? ($selected->acceptance_criteria ?? '') : '') }}</textarea>
                                                <p class="console-hint">Tulis dalam bahasa sehari-hari. Sertakan hasil yang diharapkan untuk setiap skenario.</p>
                                                @if ($bag->has('acceptance_criteria'))
                                                    <p class="console-hint text-red-600 dark:text-red-400">{{ $bag->first('acceptance_criteria') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <aside class="flex flex-col gap-4">
                                            <div class="rounded-xl border border-edge p-4 dark:border-slate-800 dark:bg-white/5">
                                                <label for="base-url" class="console-label">Base URL
                                                    <span class="font-normal text-subtle dark:text-slate-400">(opsional)</span></label>
                                                <input id="base-url" name="base_url" type="url"
                                                    value="{{ old('base_url', $isDraft ? ($selected->base_url ?? '') : '') }}"
                                                    placeholder="https://app.example.com" class="console-field mt-2">
                                                <p class="console-hint mt-2">Alamat dasar aplikasi yang akan diuji.</p>
                                                @if ($bag->has('base_url'))
                                                    <p class="console-hint mt-1 text-red-600 dark:text-red-400">{{ $bag->first('base_url') }}</p>
                                                @endif
                                            </div>

                                            <div class="rounded-xl border border-edge p-4 dark:border-slate-800 dark:bg-white/5">
                                                <span class="console-label">Autentikasi
                                                    <span class="font-normal text-subtle dark:text-slate-400">(opsional)</span></span>
                                                <p class="console-hint mt-2">
                                                    {{ $selected?->authProfile ? 'Profil auth tersimpan.' : 'Belum ada profil auth.' }}</p>
                                                <button type="button" data-open-tab="config"
                                                    class="mt-2 text-xs font-semibold text-accent transition hover:underline dark:text-indigo-300">Buka Config</button>
                                            </div>
                                        </aside>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-edge pt-4 dark:border-slate-800">
                                        <span class="console-hint">Hasil perlu direview sebelum dijalankan.</span>
                                        <button type="submit" class="console-btn-primary">Lanjutkan</button>
                                    </div>
                                </form>
                            @elseif ($selected === null)
                                <div class="flex flex-col gap-2 rounded-xl border border-dashed border-edge-strong p-6 dark:border-slate-700">
                                    <p class="text-sm font-semibold text-body dark:text-slate-100">Belum ada test case.</p>
                                    <p class="console-hint">Pilih test case di sidebar, atau buka menu collection lalu pilih &ldquo;Test case baru&rdquo;.</p>
                                </div>
                            @else
                                @php $hasScript = $selected->generatedScript !== null; @endphp

                                <div class="flex flex-col gap-4">
                                    <div role="tablist" aria-label="Hasil generate"
                                        class="flex gap-7 border-b border-edge dark:border-slate-800">
                                        <button type="button" role="tab" id="subtab-scenario" data-subtab="scenario"
                                            aria-controls="subpanel-scenario" aria-selected="{{ $hasScript ? 'false' : 'true' }}"
                                            class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                            Scenario URL
                                        </button>
                                        <button type="button" role="tab" id="subtab-script" data-subtab="script"
                                            aria-controls="subpanel-script" aria-selected="{{ $hasScript ? 'true' : 'false' }}"
                                            class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                            Generated Script
                                        </button>
                                    </div>

                                    <div role="tabpanel" id="subpanel-scenario" data-subpanel="scenario"
                                        aria-labelledby="subtab-scenario" class="console-panel" {{ $hasScript ? 'hidden' : '' }}>
                                        <div class="flex flex-col gap-3 rounded-xl border border-dashed border-edge-strong p-5 dark:border-slate-700">
                                            <p class="text-sm font-semibold text-body dark:text-slate-100">Scenario URL</p>
                                            <p class="console-hint">Lengkapi URL awal tiap skenario sebelum generate.</p>
                                            <div>
                                                <a href="{{ route('test-cases.scenario-urls', $selected) }}"
                                                    class="console-btn-ghost h-9 px-4 text-xs">Buka Scenario URL</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div role="tabpanel" id="subpanel-script" data-subpanel="script"
                                        aria-labelledby="subtab-script" class="console-panel" {{ $hasScript ? '' : 'hidden' }}>
                                        <x-console.script-review :test-case="$selected" />
                                    </div>
                                </div>

                                <div class="mt-5 flex items-center justify-between gap-3 border-t border-edge pt-4 dark:border-slate-800">
                                    <span class="console-hint">Hasil perlu direview sebelum dijalankan.</span>
                                    <a href="{{ route('test-cases.scenario-urls', $selected) }}"
                                        class="console-btn-ghost">Buka Config &amp; Generate</a>
                                </div>
                            @endif
                        </div>

                        <div role="tabpanel" id="panel-config" data-panel="config" aria-labelledby="tab-config" hidden
                            class="console-panel pt-6">
                            @if ($selected)
                                <div class="max-w-3xl">
                                    <x-console.auth-profile :test-case="$selected" />
                                </div>
                            @else
                                <p class="console-hint">Pilih test case di sidebar untuk mengatur Auth Profile.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </div>
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

            document.querySelectorAll('[data-open-tab]').forEach(function(el) {
                el.addEventListener('click', function() {
                    show(el.dataset.openTab);
                });
            });

            document.querySelectorAll('[data-create-collection]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.dispatchEvent(new CustomEvent('atlas:new-collection'));
                });
            });
        })();
    </script>
@endsection
