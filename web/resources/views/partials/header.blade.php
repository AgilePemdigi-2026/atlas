<header class="sticky top-0 z-20 bg-white shadow-[0_4px_20px_rgba(0,0,0,.2)] dark:bg-abyss dark:shadow-black/60">
    <div class="mx-auto flex h-22 max-w-[1440px] items-center justify-between gap-4 px-5 min-[1100px]:px-10">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-6" aria-label="ATLAS - ke halaman utama">
            <x-logo class="block h-[47px] w-auto flex-none" />
            <div>
                <div class="font-typewriter text-[26px]/[30px] font-semibold text-ink min-[1100px]:text-[33px]/[30px] dark:text-white">ATLAS</div>
                <div class="mt-[3px] hidden font-iosevka text-sm/5 text-muted min-[1100px]:block dark:text-slate-400">Automated Test case generation via LLM-Assisted Selenium scripting</div>
            </div>
        </a>
        <nav class="flex items-center gap-6 font-typewriter text-sm">
            @foreach ($menu as $item)
                <a href="{{ $item['href'] }}" class="hidden w-[81px] text-center text-muted hover:text-ink min-[1100px]:block dark:text-slate-400 dark:hover:text-white">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ $loginHref }}" class="w-[98px] rounded-md border border-[#E4FFDE] bg-[#E9FFE4] py-[3px] text-center font-semibold text-ink shadow-[1px_2px_10px_rgba(0,0,0,.13)] dark:border-brand/40 dark:bg-brand/20 dark:text-white">Log in</a>
        </nav>
    </div>
</header>
