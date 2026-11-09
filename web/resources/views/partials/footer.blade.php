<footer class="flex flex-col gap-[18px] bg-surface px-6 py-[27px] text-center text-xs/[1.55] text-subtle dark:bg-night dark:text-slate-400">
    <p>© {{ now()->year }} ATLAS. Semua hak dilindungi.</p>
    @auth
        <nav class="flex justify-center gap-2">
            <a href="{{ route('profile') }}" class="hover:text-ink dark:hover:text-white">Profile</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('console') }}" class="hover:text-ink dark:hover:text-white">Console</a>
        </nav>
    @endauth
</footer>
