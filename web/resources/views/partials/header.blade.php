@php
    $logo = '<path d="M3.5 20 11 4h2l7.5 16"/><path d="M7.5 14h9"/>';
@endphp

<header class="sticky top-0 z-50 flex h-[72px] items-center justify-between gap-4 bg-white px-5 shadow-[0_4px_20px_rgba(0,0,0,.12)] min-[1100px]:h-[88px] min-[1100px]:px-10 dark:bg-night">
    <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="ATLAS - ke halaman utama">
        <svg class="size-9 text-[#4CAF41] min-[1100px]:size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $logo !!}</svg>
        <span class="flex flex-col gap-0.5">
            <span class="font-typewriter text-2xl/[1] font-semibold text-[#0E2D07] min-[1100px]:text-[33px]/[30px] dark:text-white">ATLAS</span>
            <span class="hidden font-iosevka text-[14px]/5 text-[#595959] min-[1100px]:block dark:text-slate-300">Automated Test case generation via LLM-Assisted Selenium scripting</span>
        </span>
    </a>

    <nav class="flex items-center gap-1 font-typewriter text-sm min-[1100px]:gap-3">
        @foreach ($menu as $item)
            <a href="{{ $item['href'] }}" class="rounded-md px-3 py-2 text-[#595959] transition hover:bg-[#E9FFE4] hover:text-[#0E2D07] min-[1100px]:px-5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">{{ $item['label'] }}</a>
        @endforeach
        <a href="{{ $loginHref }}" class="ml-1 rounded-md border border-[#E4FFDE] bg-[#E9FFE4] px-4 py-2 font-semibold text-[#0E2D07] shadow-[1px_2px_10px_rgba(0,0,0,.13)] transition hover:brightness-95 min-[1100px]:ml-2 min-[1100px]:px-6 dark:border-emerald-400/30 dark:bg-emerald-400/15 dark:text-emerald-200">Log in</a>
    </nav>
</header>   