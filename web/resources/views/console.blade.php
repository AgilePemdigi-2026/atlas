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
                                $hasScript = $selected?->generatedScript !== null;
                                $bag = $errors->updateTestCase;
                                $activeResultTab = $activeResultTab ?? ($hasScript ? 'script' : 'scenario');
                            @endphp

                            @if ($selected === null)
                                <div class="flex flex-col gap-2 rounded-xl border border-dashed border-edge-strong p-6 dark:border-slate-700">
                                    <p class="text-sm font-semibold text-body dark:text-slate-100">Belum ada test case.</p>
                                    <p class="console-hint">Pilih test case di sidebar, atau buka menu collection lalu pilih &ldquo;Test case baru&rdquo;.</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('test-cases.update', $selected) }}" class="flex flex-col gap-5">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="collection_id" value="{{ $selected->collection_id }}">

                                    <div class="grid gap-5 min-[900px]:grid-cols-[minmax(0,1fr)_240px]">
                                        <div class="flex min-w-0 flex-col gap-4">
                                            <div class="flex flex-col gap-1.5">
                                                <label for="test-case-name" class="console-label">
                                                    Nama Test Case
                                                </label>
                                                <input id="test-case-name" name="name" type="text"
                                                    value="{{ old('name', $selected->name) }}" required
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
                                                    class="console-area min-h-[96px]">{{ old('user_story', $selected->user_story) }}</textarea>
                                                @if ($bag->has('user_story'))
                                                    <p class="console-hint text-red-600 dark:text-red-400">{{ $bag->first('user_story') }}</p>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-1.5">
                                                <label for="acceptance-criteria" class="console-label">
                                                    Acceptance criteria
                                                </label>
                                                <textarea id="acceptance-criteria" name="acceptance_criteria" rows="6" required
                                                    class="console-area min-h-[128px]">{{ old('acceptance_criteria', $selected->acceptance_criteria) }}</textarea>
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
                                                    value="{{ old('base_url', $selected->base_url) }}"
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
                                                    {{ $selected->authProfile ? 'Profil auth tersimpan.' : 'Belum ada profil auth.' }}</p>
                                                <button type="button" data-open-tab="config"
                                                    class="mt-2 text-xs font-semibold text-accent transition hover:underline dark:text-indigo-300">Buka Config</button>
                                            </div>
                                        </aside>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-edge pt-4 dark:border-slate-800">
                                        <span class="console-hint">Hasil perlu direview sebelum dijalankan.</span>
                                        <button type="submit" class="console-btn-primary">{{ $selected->scenarios->isNotEmpty() ? 'Sinkronkan' : 'Lanjutkan' }}</button>
                                    </div>
                                </form>

                                @if (! $isDraft)
                                    <div class="mt-6 flex flex-col gap-4 border-t border-edge pt-6 dark:border-slate-800" data-active-tab="{{ $activeResultTab }}">
                                        <div role="tablist" aria-label="Hasil generate"
                                            class="flex gap-7 border-b border-edge dark:border-slate-800">
                                            <button type="button" role="tab" id="subtab-scenario" data-subtab="scenario"
                                                aria-controls="subpanel-scenario" aria-selected="{{ $activeResultTab === 'script' ? 'false' : 'true' }}"
                                                class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                                Scenario URL
                                            </button>
                                            <button type="button" role="tab" id="subtab-script" data-subtab="script"
                                                aria-controls="subpanel-script" aria-selected="{{ $activeResultTab === 'script' ? 'true' : 'false' }}"
                                                class="-mb-px border-b-2 border-transparent px-1 py-3 text-xs font-medium whitespace-nowrap text-subtle transition-colors duration-200 hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300">
                                                Generated Script
                                            </button>
                                        </div>

                                        <div role="tabpanel" id="subpanel-scenario" data-subpanel="scenario"
                                            aria-labelledby="subtab-scenario" class="console-panel" {{ $activeResultTab === 'script' ? 'hidden' : '' }}>
                                            @if ($selected->scenarios->isEmpty())
                                                <p class="console-hint">Belum ada skenario. Isi user story dan acceptance criteria terlebih dahulu.</p>
                                            @else
                                                @if ($errors->any())
                                                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[13px] text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                        {{ $errors->first() }}
                                                    </div>
                                                @endif

                                                <form method="POST" action="{{ route('test-cases.scenario-urls.generate', $selected) }}"
                                                    class="flex flex-col gap-5" data-generate-form>
                                                    @csrf

                                                    <table class="w-full text-left text-[13px] text-body dark:text-slate-200">
                                                        <thead>
                                                            <tr class="text-[11px] uppercase tracking-wide text-subtle dark:text-slate-400">
                                                                <th class="px-2 py-2">Skenario</th>
                                                                <th class="w-[55%] px-2 py-2">URL Awal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($selected->scenarios as $index => $scenario)
                                                                <tr data-scenario-row
                                                                    data-save-url="{{ route('scenarios.update', $scenario) }}"
                                                                    class="border-t border-line align-middle dark:border-slate-700">
                                                                    <td class="px-2 py-2">
                                                                        <input type="text" name="scenarios[{{ $index }}][name]" required maxlength="256"
                                                                            value="{{ old("scenarios.$index.name", $scenario->name) }}"
                                                                            data-scenario-name aria-label="Nama skenario {{ $scenario->name }}"
                                                                            class="h-9 w-full rounded-md border border-transparent bg-transparent px-2 text-[13px] font-medium text-body outline-none transition hover:border-line focus:border-accent focus:bg-white focus:ring-2 focus:ring-accent/20 dark:text-slate-100 dark:hover:border-slate-600 dark:focus:border-indigo-300 dark:focus:bg-abyss">
                                                                    </td>
                                                                    <td class="px-2 py-2">
                                                                        <input type="hidden" name="scenarios[{{ $index }}][scenario_id]" value="{{ $scenario->getKey() }}">
                                                                        <div class="flex items-center gap-2">
                                                                            <input type="url" name="scenarios[{{ $index }}][scenario_url]" required
                                                                                value="{{ old("scenarios.$index.scenario_url", $scenario->scenario_url ?? $selected->base_url) }}"
                                                                                data-scenario-url aria-label="URL awal {{ $scenario->name }}" placeholder="https://app.example.com/login"
                                                                                class="h-9 w-full rounded-md border border-line bg-white px-3 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                                                                            <p data-scenario-status role="status"
                                                                                class="hidden text-xs text-subtle dark:text-slate-400"></p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>

                                                    <div class="flex items-center gap-3">
                                                        <button type="submit" data-generate-button
                                                            class="h-9 rounded-md bg-accent px-4 text-xs font-semibold text-white">Lanjut &amp; Generate</button>
                                                        <p class="hidden text-xs text-subtle dark:text-slate-400" data-generate-hint>Sedang menghasilkan test case …</p>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>

                                        <div role="tabpanel" id="subpanel-script" data-subpanel="script"
                                            aria-labelledby="subtab-script" class="console-panel" {{ $activeResultTab === 'script' ? '' : 'hidden' }}>
                                            <x-console.script-review :test-case="$selected" />
                                        </div>
                                    </div>
                                @endif
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

            var generateForm = document.querySelector('[data-generate-form]');
            var generateButton = document.querySelector('[data-generate-button]');
            var generateHint = document.querySelector('[data-generate-hint]');

            if (generateForm && generateButton && generateHint) {
                generateForm.addEventListener('submit', function() {
                    generateButton.disabled = true;
                    generateButton.classList.add('opacity-60');
                    generateHint.classList.remove('hidden');
                });
            }

            var scenarioRows = document.querySelectorAll('[data-scenario-row]');
            var scenarioToken = document.querySelector('[data-generate-form] input[name="_token"]');

            scenarioRows.forEach(function (row) {
                var nameInput = row.querySelector('[data-scenario-name]');
                var urlInput = row.querySelector('[data-scenario-url]');
                var status = row.querySelector('[data-scenario-status]');

                if (!nameInput || !urlInput || !status || !scenarioToken) {
                    return;
                }

                var saving = false;

                function setStatus(message, error) {
                    status.textContent = message;
                    status.classList.toggle('hidden', message === '');
                    status.classList.toggle('text-subtle', !error);
                    status.classList.toggle('dark:text-slate-400', !error);
                    status.classList.toggle('text-red-600', Boolean(error));
                    status.classList.toggle('dark:text-red-400', Boolean(error));
                }

                function save() {
                    if (saving) {
                        return;
                    }

                    if (!nameInput.value.trim()) {
                        setStatus('Field wajib diisi.', true);
                        return;
                    }

                    saving = true;
                    setStatus('Menyimpan …', false);

                    fetch(row.dataset.saveUrl, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': scenarioToken.value,
                        },
                        body: JSON.stringify({
                            name: nameInput.value,
                            scenario_url: urlInput.value.trim() || null,
                        }),
                    }).then(async function (response) {
                        var body = await response.json().catch(function () {
                            return {};
                        });

                        if (!response.ok) {
                            setStatus(body.message || 'Gagal menyimpan skenario.', true);
                            return;
                        }

                        nameInput.defaultValue = nameInput.value;
                        urlInput.defaultValue = urlInput.value;
                        setStatus(body.message || 'Skenario disimpan.', false);
                    }).catch(function () {
                        setStatus('Gagal menyimpan skenario.', true);
                    }).finally(function () {
                        saving = false;
                    });
                }

                [nameInput, urlInput].forEach(function (input) {
                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            save();
                        } else if (event.key === 'Escape') {
                            event.preventDefault();
                            nameInput.value = nameInput.defaultValue;
                            urlInput.value = urlInput.defaultValue;
                            setStatus('', false);
                        }
                    });
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
