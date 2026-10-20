<footer class="flex flex-col gap-[18px] bg-surface px-6 py-[27px] text-center text-xs/[1.55] text-subtle dark:bg-night dark:text-slate-400">
    <p>© {{ now()->year }} ATLAS. Semua hak dilindungi.</p>
    <nav class="flex justify-center gap-2">
        @foreach ($menu as $item)
            <a href="{{ $item['href'] }}" class="hover:text-ink dark:hover:text-white">{{ $item['label'] }}</a>
            @unless ($loop->last)
                <span aria-hidden="true">/</span>
            @endunless
        @endforeach
    </nav>
</footer>
