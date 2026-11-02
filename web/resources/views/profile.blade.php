@extends('layouts.app')

@section('title', 'Profile – ATLAS')

@section('content')
    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        $tabClass = '-mb-px border-b-2 border-transparent px-1 py-4 text-sm whitespace-nowrap text-subtle transition hover:text-body aria-selected:border-accent aria-selected:font-semibold aria-selected:text-accent dark:text-slate-400 dark:hover:text-slate-100 dark:aria-selected:border-indigo-300 dark:aria-selected:text-indigo-300';
    @endphp

    <div style="zoom: 0.9" class="flex min-h-[calc((100vh-72px)/0.9)] flex-col gap-7 bg-surface px-6 py-10 min-[1100px]:min-h-[calc((100vh-88px)/0.9)] min-[1100px]:px-[72px] min-[1100px]:py-12 dark:bg-abyss">
        <h1 class="text-3xl text-body dark:text-white">My Profile</h1>

        <div class="flex flex-col gap-7 min-[900px]:flex-row min-[900px]:items-start">
            {{-- Kartu kiri --}}
            <aside class="w-full flex-none overflow-hidden rounded-lg border border-line bg-white min-[900px]:w-60 dark:border-slate-700 dark:bg-night">
                <div class="flex flex-col items-center gap-4 p-6">
                    <div class="grid size-[88px] place-items-center rounded-full bg-[#EEEEFC] text-2xl font-semibold text-accent dark:bg-accent/30 dark:text-indigo-200" aria-hidden="true">{{ $initials }}</div>
                    <div class="flex w-full flex-col gap-1 text-center">
                        <p class="text-base font-semibold break-words text-body dark:text-white">{{ $user->name }}</p>
                        <p class="text-xs break-all text-subtle dark:text-slate-400">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="border-t border-line p-5 dark:border-slate-700">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="h-11 w-full rounded-md border border-line bg-white text-sm font-semibold text-accent hover:bg-surface dark:border-slate-600 dark:bg-transparent dark:text-indigo-300 dark:hover:bg-white/5">Log Out</button>
                    </form>
                </div>
            </aside>

            {{-- Kartu kanan --}}
            <section class="min-w-0 flex-1 overflow-hidden rounded-lg border border-line bg-white dark:border-slate-700 dark:bg-night">
                <div class="px-7">
                    <div role="tablist" aria-label="Profile" class="flex gap-7 overflow-x-auto border-b border-line dark:border-slate-700">
                        <button type="button" role="tab" id="tab-history" data-tab="history" aria-controls="panel-history" aria-selected="true" class="{{ $tabClass }}">Test History</button>
                        <button type="button" role="tab" id="tab-settings" data-tab="settings" aria-controls="panel-settings" aria-selected="false" class="{{ $tabClass }}">Settings</button>
                    </div>
                </div>

                <div role="tabpanel" id="panel-history" data-panel="history" aria-labelledby="tab-history" class="flex flex-col gap-6 p-7">
                    {{-- Tahap 4: Test History --}}
                    @php
                        $statusStyles = [
                            'completed' => ['label' => 'Completed', 'badge' => 'bg-[#EDF8F2] text-[#23765B] dark:bg-emerald-900/30 dark:text-emerald-300', 'dot' => 'bg-[#23765B] dark:bg-emerald-300'],
                            'pending' => ['label' => 'Pending', 'badge' => 'bg-[#FFF7E6] text-[#9A6700] dark:bg-amber-900/30 dark:text-amber-300', 'dot' => 'bg-[#9A6700] dark:bg-amber-300'],
                            'failed' => ['label' => 'Failed', 'badge' => 'bg-[#FFF1F2] text-[#BA3D4A] dark:bg-red-950/40 dark:text-red-300', 'dot' => 'bg-[#BA3D4A] dark:bg-red-300'],
                        ];

                        $pageBase = 'grid size-11 place-items-center rounded-md border text-[13px]';
                        $pageLink = $pageBase . ' border-line bg-white text-subtle hover:bg-surface dark:border-slate-600 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5';
                        $pageOff = $pageBase . ' border-line bg-white text-subtle opacity-40 dark:border-slate-600 dark:bg-transparent dark:text-slate-300';
                        $pageOn = $pageBase . ' border-accent bg-accent text-white';

                        $current = $testCases->currentPage();
                        $pageUrls = $testCases->getUrlRange(max(1, $current - 2), min($testCases->lastPage(), $current + 2));
                    @endphp

                    <div class="flex flex-col gap-2">
                        <h2 class="text-2xl/[1.2] text-body dark:text-white">Test History</h2>
                        <p class="text-sm/[1.55] text-subtle dark:text-slate-400">Riwayat hasil generate skrip Anda. Completed berarti skrip berhasil dibuat, bukan hasil eksekusi pengujian.</p>
                    </div>

                    <div class="overflow-x-auto rounded-md border border-line dark:border-slate-700">
                        <div class="min-w-[560px]">
                            <div class="flex items-start gap-6 bg-surface p-4 text-xs/[1.55] font-semibold text-body dark:bg-abyss dark:text-slate-100">
                                <div class="min-w-0 flex-1">Test-case name</div>
                                <div class="w-[110px] flex-none">Status</div>
                                <div class="w-[210px] flex-none">Dibuat</div>
                            </div>

                            @forelse ($testCases as $case)
                                @php $style = $statusStyles[$case->status->value]; @endphp
                                <div class="flex items-center gap-6 border-t border-line p-4 dark:border-slate-700">
                                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                        <p class="text-sm/[1.55] font-semibold break-words text-body dark:text-white">{{ $case->name }}</p>
                                        <p class="truncate text-xs/[1.55] text-subtle dark:text-slate-400">{{ $case->scenarios_count }} skenario{{ $case->base_url ? ' · ' . $case->base_url : '' }}</p>
                                    </div>
                                    <div class="w-[110px] flex-none">
                                        <span class="inline-flex items-center gap-1.5 rounded px-2 py-1 text-[11px] font-medium {{ $style['badge'] }}">
                                            <span class="size-[5px] rounded-full {{ $style['dot'] }}"></span>{{ $style['label'] }}
                                        </span>
                                    </div>
                                    <div class="w-[210px] flex-none font-mono text-[11px]/[1.55] text-subtle dark:text-slate-400">{{ $case->created_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y · H.i') }}</div>
                                </div>
                            @empty
                                <p class="border-t border-line p-6 text-center text-sm text-subtle dark:border-slate-700 dark:text-slate-400">Belum ada riwayat test case.</p>
                            @endforelse
                        </div>
                    </div>

                    @if ($testCases->total() > 0)
                        <div class="flex flex-col gap-3 min-[700px]:flex-row min-[700px]:items-center min-[700px]:justify-between">
                            <p class="text-xs/[1.55] text-subtle dark:text-slate-400">Menampilkan {{ $testCases->firstItem() }}–{{ $testCases->lastItem() }} dari {{ $testCases->total() }} hasil</p>

                            <nav aria-label="Halaman riwayat" class="flex gap-2">
                                @if ($testCases->onFirstPage())
                                    <span class="{{ $pageOff }}" aria-disabled="true">‹</span>
                                @else
                                    <a href="{{ $testCases->previousPageUrl() }}" aria-label="Halaman sebelumnya" class="{{ $pageLink }}">‹</a>
                                @endif

                                @foreach ($pageUrls as $page => $url)
                                    @if ($page === $current)
                                        <span class="{{ $pageOn }}" aria-current="page">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="{{ $pageLink }}">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if ($testCases->hasMorePages())
                                    <a href="{{ $testCases->nextPageUrl() }}" aria-label="Halaman berikutnya" class="{{ $pageLink }}">›</a>
                                @else
                                    <span class="{{ $pageOff }}" aria-disabled="true">›</span>
                                @endif
                            </nav>
                        </div>
                    @endif
                </div>

                <div role="tabpanel" id="panel-settings" data-panel="settings" aria-labelledby="tab-settings" class="flex flex-col gap-7 p-8" hidden>
                    @php
                        $inputClass = 'h-11 w-full rounded-md border bg-white px-3 text-[13px] text-body outline-none focus:border-accent focus:ring-2 focus:ring-accent/25 dark:bg-abyss dark:text-white';

                        $accountFields = [
                            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'autocomplete' => 'name', 'max' => 256],
                            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'max' => 100],
                        ];

                        // UI saja: belum ada data yang menyimpannya (menunggu jawaban tim)
                        $notifications = [
                            ['id' => 'notify-success', 'title' => 'Skrip berhasil dibuat', 'text' => 'Pemberitahuan saat hasil siap direview.', 'on' => true],
                            ['id' => 'notify-failed', 'title' => 'Generate gagal', 'text' => 'Pemberitahuan jika proses tidak selesai.', 'on' => false],
                        ];
                    @endphp

                    @if (session('status'))
                        <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[13px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
                    @endif

                    {{-- Account Information --}}
                    <div class="flex flex-col gap-[18px] border-b border-line pb-7 dark:border-slate-700">
                        <h2 class="text-xl/6 text-body dark:text-white">Account Information</h2>

                        <form id="account-form" method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-[18px]">
                            @csrf
                            @method('PATCH')
                            <div class="flex flex-col gap-5 min-[700px]:flex-row">
                                @foreach ($accountFields as $field)
                                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                                        <label for="{{ $field['name'] }}" class="text-sm/[1.55] font-semibold text-body dark:text-slate-100">{{ $field['label'] }}</label>
                                        <input
                                            id="{{ $field['name'] }}"
                                            name="{{ $field['name'] }}"
                                            type="{{ $field['type'] }}"
                                            value="{{ old($field['name'], $user->{$field['name']}) }}"
                                            autocomplete="{{ $field['autocomplete'] }}"
                                            maxlength="{{ $field['max'] }}"
                                            required
                                            class="{{ $inputClass }} @error($field['name']) border-red-500 @else border-line dark:border-slate-600 @enderror"
                                        >
                                        @error($field['name'])
                                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <button type="submit" class="h-11 w-full rounded-md bg-accent px-5 text-sm font-semibold text-white hover:opacity-95 min-[700px]:w-[200px]">Simpan perubahan</button>
                        </form>
                    </div>

                    {{-- Notifications --}}
                    <div class="flex flex-col gap-5 border-b border-line pb-7 dark:border-slate-700">
                        <h2 class="text-xl/6 text-body dark:text-white">Notifications</h2>

                        @foreach ($notifications as $item)
                            <div class="flex items-center gap-4">
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    <label for="{{ $item['id'] }}" class="text-sm/[1.55] font-semibold text-body dark:text-slate-100">{{ $item['title'] }}</label>
                                    <span class="text-xs/[1.55] text-subtle dark:text-slate-400">{{ $item['text'] }}</span>
                                </div>
                                <label class="relative inline-flex h-7 w-11 flex-none cursor-pointer">
                                    <input id="{{ $item['id'] }}" type="checkbox" role="switch" class="peer sr-only" @checked($item['on'])>
                                    <span class="absolute inset-0 rounded-full bg-[#A7AEBD] transition peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40 after:absolute after:top-[3px] after:left-[3px] after:size-[22px] after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4 dark:bg-slate-600 dark:peer-checked:bg-accent"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    {{-- API / Integrations --}}
                    <div class="flex flex-col gap-4 border-b border-line pb-7 dark:border-slate-700">
                        <h2 class="text-xl/6 text-body dark:text-white">API / Integrations</h2>

                        <div class="flex flex-col gap-2">
                            <label for="script-format" class="text-sm/[1.55] font-semibold text-body dark:text-slate-100">Format skrip</label>
                            <input id="script-format" type="text" value="Selenium 4 · Python 3" readonly
                                class="h-11 w-full rounded-md border border-line bg-surface px-3 text-[13px] text-body outline-none dark:border-slate-600 dark:bg-abyss dark:text-slate-200">
                            <p class="text-xs/[1.55] text-subtle dark:text-slate-400">Unduh skrip dari Console untuk dijalankan di lingkungan pengujian Anda.</p>
                        </div>
                    </div>

                    {{-- Delete Account --}}
                    <div class="flex flex-col gap-4">
                        <h2 class="text-xl/6 text-body dark:text-white">Delete Account</h2>

                        <div class="flex flex-col gap-5 rounded-md border border-[#EBC1C7] bg-[#FFF1F2] p-5 min-[700px]:flex-row min-[700px]:items-center dark:border-red-900/60 dark:bg-red-950/30">
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <p class="text-sm/[1.55] font-semibold text-[#BA3D4A] dark:text-red-300">Tindakan ini permanen.</p>
                                <p class="text-xs/[1.55] text-subtle dark:text-slate-400">Akun, profil auth, dan seluruh riwayat test case akan dihapus. Data tidak dapat dipulihkan.</p>
                            </div>
                            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Hapus akun secara permanen? Seluruh data akan hilang dan tidak dapat dipulihkan.')" class="w-full flex-none min-[700px]:w-[180px]">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="h-11 w-full rounded-md border border-[#BA3D4A] bg-white text-sm font-semibold text-[#BA3D4A] hover:bg-[#FFF1F2] dark:border-red-400 dark:bg-transparent dark:text-red-300 dark:hover:bg-red-950/40">Delete Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
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

            // /profile#settings langsung membuka tab Settings
            var initial = location.hash.slice(1);
            @if (session('status') || $errors->any())
                initial = 'settings'; // setelah simpan atau error validasi, tetap di tab Settings
            @endif
          
        })();
    </script>
@endsection