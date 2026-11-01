@props(['collections'])

<aside class="flex w-full flex-none flex-col gap-6 border-b border-line bg-surface p-5 min-[900px]:w-[236px] min-[900px]:border-r min-[900px]:border-b-0 dark:border-slate-700 dark:bg-night">
    <h2 class="text-sm/[1.55] font-semibold text-body dark:text-slate-100">Test Scenario</h2>

    @if ($errors->updateCollection->any())
        <p class="text-xs text-red-600 dark:text-red-400">{{ $errors->updateCollection->first('name') }}</p>
    @endif

    <div class="flex flex-col gap-4">
        @forelse ($collections as $collection)
            <details class="group flex flex-col gap-2" {{ $loop->first ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center gap-2 text-[13px]/[1.55] font-semibold text-body dark:text-slate-100">
                    <span class="inline-block transition-transform group-open:rotate-90" aria-hidden="true">›</span>
                    {{ $collection->name }}
                </summary>

                <div class="flex flex-col gap-2">
                    @forelse ($collection->testCases as $testCase)
                        <div class="flex flex-col gap-[7px] rounded-md bg-white p-3 dark:bg-abyss">
                            <span class="text-xs/[1.55] text-body dark:text-slate-200">{{ $testCase->name }}</span>
                            <x-console.status-badge :status="$testCase->status->value" />
                        </div>
                    @empty
                        <p class="text-[11px]/[1.55] text-subtle dark:text-slate-400">Belum ada test case.</p>
                    @endforelse

                    <div class="flex flex-col gap-2 text-[11px]">
                        <details class="flex flex-col gap-2">
                            <summary class="cursor-pointer font-semibold text-accent dark:text-indigo-300">Ubah nama</summary>
                            <form method="POST" action="{{ route('collections.update', $collection) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <input name="name" type="text" value="{{ $collection->name }}" required maxlength="256" aria-label="Nama collection"
                                    class="h-8 min-w-0 flex-1 rounded-md border border-line bg-white px-2 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                                <button type="submit" class="h-8 rounded-md bg-accent px-3 text-xs font-semibold text-white">Simpan</button>
                            </form>
                        </details>

                        <form method="POST" action="{{ route('collections.destroy', $collection) }}"
                            onsubmit="return confirm('Hapus collection ini beserta seluruh test case di dalamnya?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-[#BA3D4A] dark:text-red-300">Hapus collection</button>
                        </form>
                    </div>
                </div>
            </details>
        @empty
            <p class="text-[11px]/[1.55] text-subtle dark:text-slate-400">Belum ada collection. Buat yang pertama di bawah.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('collections.store') }}" class="flex flex-col gap-2">
        @csrf
        <label for="collection-name" class="text-xs font-semibold text-body dark:text-slate-200">Collection baru</label>
        <input id="collection-name" name="name" type="text" value="{{ old('name') }}" required maxlength="256" placeholder="Nama collection"
            class="h-9 rounded-md border border-line bg-white px-3 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
        @error('name')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <button type="submit" class="h-9 rounded-md bg-accent text-xs font-semibold text-white">Tambah collection</button>
    </form>

    <p class="text-[11px]/[1.55] text-subtle dark:text-slate-400">Pilih test case untuk melihat cerita dan hasil sebelumnya.</p>
</aside>
