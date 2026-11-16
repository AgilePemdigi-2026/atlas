@props([
    'collections',
    'selected' => null,
    'activeCollectionId' => null,
])

<aside class="flex w-full flex-none flex-col gap-4 border-b border-line bg-surface p-5 min-[900px]:w-[236px] min-[900px]:border-r min-[900px]:border-b-0 dark:border-slate-700 dark:bg-night">

    {{-- Header dan tombol tambah Collection --}}
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-body dark:text-slate-100">
            Test Scenario
        </h2>

        <details class="relative">
            <summary
                class="flex size-8 cursor-pointer list-none items-center justify-center rounded-md border border-line text-lg font-semibold text-accent hover:bg-white dark:border-slate-600 dark:text-indigo-300"
                aria-label="Tambah Collection"
                title="Tambah Collection">
                +
            </summary>

            <div class="absolute right-0 top-full z-30 mt-2 w-64 rounded-lg border border-line bg-white p-3 shadow-lg dark:border-slate-600 dark:bg-night">
                <form method="POST" action="{{ route('collections.store') }}" class="flex flex-col gap-3">
                    @csrf

                    <label for="collection-name"
                        class="text-xs font-semibold text-body dark:text-slate-100">
                        New Collection
                    </label>

                    <input id="collection-name" name="name" type="text"
                        value="{{ old('name') }}"
                        required maxlength="256"
                        placeholder="Nama Collection"
                        class="w-full rounded-md border border-line bg-white px-3 py-2 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">

                    <button type="submit"
                        class="rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white">
                        Buat Collection
                    </button>
                </form>
            </div>
        </details>
    </div>

    @error('name')
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror

    @if ($errors->updateCollection->any())
        <p class="text-xs text-red-600">
            {{ $errors->updateCollection->first('name') }}
        </p>
    @endif

    {{-- Collection List --}}
    <div class="flex flex-col gap-3">
        @forelse ($collections as $collection)
            <div class="flex flex-col gap-2">

                <div class="flex items-center gap-1">
                    {{-- Expand / Collapse Collection --}}
                    <details class="group min-w-0 flex-1"
                        @if ($activeCollectionId == $collection->getKey()) open @endif>

                        <summary class="flex cursor-pointer list-none items-center gap-2 text-xs font-semibold text-body dark:text-slate-100">
                            <span class="inline-block transition-transform group-open:rotate-90">
                                &#8250;
                            </span>
                            <span aria-hidden="true">📁</span>
                            <span class="min-w-0 truncate">
                                {{ $collection->name }}
                            </span>
                        </summary>

                        <div class="mt-3 flex flex-col gap-2 pl-4">

                            @forelse ($collection->testCases as $testCase)
                                <div class="flex items-start gap-1 rounded-md bg-white px-2 py-2 dark:bg-abyss">

                                    {{-- Link Test Case --}}
                                    <a href="{{ route('console', ['test_case' => $testCase->getKey()]) }}"
                                        @class([
                                            'flex min-w-0 flex-1 flex-col gap-1 rounded-md px-1 py-1 text-xs transition',
                                            'bg-[#EAEAFF] dark:bg-indigo-900/40' => $selected?->getKey() == $testCase->getKey(),
                                        ])>
                                        <span class="truncate font-medium text-body dark:text-slate-100">
                                            {{ $testCase->name }}
                                        </span>

                                        <x-console.status-badge :status="$testCase->status->value" />
                                    </a>

                                    {{-- Menu Test Case --}}
                                    <details class="relative flex-none">
                                        <summary
                                            class="flex size-7 cursor-pointer list-none items-center justify-center rounded-md text-lg text-subtle hover:bg-slate-100 dark:hover:bg-slate-800"
                                            aria-label="Menu Test Case {{ $testCase->name }}"
                                            title="Opsi Test Case">
                                            &#8943;
                                        </summary>

                                        <div class="absolute right-0 top-full z-30 mt-1 w-56 rounded-lg border border-line bg-white p-2 shadow-lg dark:border-slate-600 dark:bg-night">

                                            {{-- Rename Test Case --}}
                                            <details>
                                                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-xs text-body hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">
                                                    Rename Test Case
                                                </summary>

                                                <form method="POST"
                                                    action="{{ route('test-cases.update', $testCase) }}"
                                                    class="flex flex-col gap-2 p-2">
                                                    @csrf
                                                    @method('PATCH')

                                                    <input name="name" type="text"
                                                        value="{{ $testCase->name }}"
                                                        required maxlength="256"
                                                        aria-label="Nama Test Case"
                                                        class="w-full rounded-md border border-line bg-white px-2 py-2 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">

                                                    <button type="submit"
                                                        class="rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white">
                                                        Simpan
                                                    </button>
                                                </form>
                                            </details>

                                            {{-- Delete Test Case --}}
                                            <form method="POST"
                                                action="{{ route('test-cases.destroy', $testCase) }}"
                                                onsubmit="return confirm('Yakin ingin menghapus Test Case ini beserta data terkait?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="w-full rounded-md px-3 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                    Delete Test Case
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            @empty
                                <p class="text-[11px] text-subtle dark:text-slate-400">
                                    Belum ada Test Case.
                                </p>
                            @endforelse
                        </div>
                    </details>

                    {{-- Link untuk memilih Collection aktif --}}
                    <a href="{{ route('console', ['collection' => $collection->getKey()]) }}"
                        aria-label="Pilih Collection {{ $collection->name }}"
                        @if ($activeCollectionId == $collection->getKey()) aria-current="page" @endif
                        @class([
                            'rounded px-1 text-[11px] hover:text-accent',
                            'text-accent font-semibold' => $activeCollectionId == $collection->getKey(),
                            'text-subtle' => $activeCollectionId != $collection->getKey(),
                        ])>
                        Pilih
                    </a>

                    {{-- Menu Collection --}}
                    <details class="relative">
                        <summary
                            class="flex size-7 cursor-pointer list-none items-center justify-center rounded-md text-lg text-subtle hover:bg-white hover:text-accent dark:hover:bg-slate-800"
                            aria-label="Menu Collection {{ $collection->name }}"
                            title="Opsi Collection">
                            &#8943;
                        </summary>

                        <div class="absolute right-0 top-full z-30 mt-1 w-56 rounded-lg border border-line bg-white p-2 shadow-lg dark:border-slate-600 dark:bg-night">

                            {{-- New Test Case --}}
                            <a href="{{ route('console', ['collection' => $collection->getKey()]) }}#panel-story"
                                class="block rounded-md px-3 py-2 text-xs text-body hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">
                                + New Test Case
                            </a>

                            {{-- Rename Collection --}}
                            <details class="border-t border-line pt-1 dark:border-slate-700">
                                <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-xs text-body hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">
                                    Rename Collection
                                </summary>

                                <form method="POST"
                                    action="{{ route('collections.update', $collection) }}"
                                    class="flex flex-col gap-2 p-2">
                                    @csrf
                                    @method('PATCH')

                                    <input name="name" type="text"
                                        value="{{ $collection->name }}"
                                        required maxlength="256"
                                        aria-label="Nama Collection"
                                        class="w-full rounded-md border border-line bg-white px-2 py-2 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">

                                    <button type="submit"
                                        class="rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white">
                                        Simpan
                                    </button>
                                </form>
                            </details>

                            {{-- Delete Collection --}}
                            <form method="POST"
                                action="{{ route('collections.destroy', $collection) }}"
                                onsubmit="return confirm('Hapus Collection ini beserta seluruh Test Case di dalamnya?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="w-full rounded-md px-3 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                    Delete Collection
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        @empty
            <p class="text-xs text-subtle dark:text-slate-400">
                Belum ada Collection. Klik + untuk membuat Collection pertama.
            </p>
        @endforelse
    </div>

    <p class="mt-auto text-[11px] leading-relaxed text-subtle dark:text-slate-400">
        Pilih Collection unuk membuat Test Case, atau pilih Test Case untuk melihat hasil pengujian.
    </p>
</aside>