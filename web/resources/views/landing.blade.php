@extends('layouts.app')

@section('content')
    @include('partials.hero-art')

    <main>
        <div class="relative z-10 flex flex-col items-start gap-10 px-6 py-14 min-[1100px]:min-h-[678px] min-[1100px]:flex-row min-[1100px]:items-center min-[1100px]:justify-between min-[1100px]:gap-12 min-[1100px]:py-0 min-[1100px]:pr-[119px] min-[1100px]:pl-[122px]">
            <div class="flex max-w-[646px] flex-col gap-6">
                <p class="font-typewriter text-xs/[1.55] font-semibold text-brand">FROM USER STORIES TO AUTOMATED TESTING</p>
                <h1 class="font-display text-[34px]/[1.2] font-extrabold tracking-[-.02em] text-ink min-[1100px]:text-[44px]/[1.2] dark:text-white">Turn User Stories<br>into Automated Test Scripts</h1>
                <p class="font-iosevka text-[17px]/[1.55] text-muted dark:text-slate-300">Transform your user stories and Gherkin-based acceptance criteria into Selenium test scripts with the assistance of an LLM.</p>
                <div class="flex flex-wrap gap-3">
                    <x-button href="#signup">Get Started</x-button>
                    <x-button variant="secondary" href="#process">Explore Process</x-button>
                </div>
            </div>

            <div class="flex w-full flex-none flex-col gap-5 rounded-[50px] border border-[#E0EBDD] bg-[#EEF3FC] p-7 shadow-[0_5px_20px_rgba(0,0,0,.25)] min-[1100px]:w-[410px] dark:border-slate-700 dark:bg-night">
                <div class="flex items-center gap-2.5 font-typewriter text-lg text-ink dark:text-white">
                    <x-logo class="block h-[21px] w-auto" />ATLAS
                </div>
                <div class="flex flex-col gap-4 rounded-[25px] bg-white p-5 dark:bg-abyss">
                    <div class="text-xs/[1.55] font-semibold text-accent dark:text-indigo-300">USER STORY</div>
                    <h3 class="text-xl/6 tracking-[-.02em]">Generate Test Case Script</h3>
                    <p class="font-mono text-xs/[1.55]">Scenario: Successful Script Generation<br>Given I am logged in and on the "Test Case" page<br>And I fill in the User Story / Acceptance Criteria and optional Base URL<br>When I press the "Generate" button<br>Then I should see a confirmation that the script was successfully generated<br>And I should still in “Test Case” page</p>
                    <x-button variant="accent" href="#signup">Generate Test</x-button>
                </div>
            </div>
        </div>

        <section id="process" class="relative z-10 flex flex-col gap-7 px-6 py-12 min-[1100px]:px-[104px] min-[1100px]:py-16">
            <div class="font-typewriter text-sm/[1.55] font-semibold text-brand">01 / HOW IT WORKS</div>
            <h2 class="font-display text-[28px]/[1.2] font-bold tracking-[-.02em] text-body min-[1100px]:text-4xl/[1.2] dark:text-white">From Requirements to Automated Testing</h2>
            <p class="text-base/[1.55] text-subtle dark:text-slate-400">ATLAS streamlines the test automation workflow, helping you transform written requirements into executable Selenium test scripts.</p>
            <div class="grid grid-cols-1 gap-8 rounded-[14px] border border-line bg-surface p-10 min-[1100px]:grid-cols-3 dark:border-slate-700 dark:bg-night">
                @foreach ($steps as $step)
                    <div class="flex min-w-0 flex-col gap-4">
                        <div class="text-center text-sm/[22px] font-semibold text-accent dark:text-indigo-300">
                            {{ $step['label'] }}
                        </div>
                        <div class="flex-1 rounded-md border border-line bg-white p-5 font-mono text-[13px]/[1.55] dark:border-slate-700 dark:bg-abyss">{{ $step['body'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="relative z-10 flex flex-col gap-7 px-6 py-12 min-[1100px]:px-[104px] min-[1100px]:py-16">
            <div class="text-xs/[1.55] font-semibold text-accent dark:text-indigo-300">02 / LENGKAPI DAN REVIEW</div>
            <h2 class="text-[28px]/[1.2] text-body min-[1100px]:text-4xl/[1.2] dark:text-white">Masukkan URL</h2>
            <p class="text-base/[1.55] text-subtle dark:text-slate-400">Lengkapi URL awal yang diminta untuk setiap skenario. Lalu review kode Selenium sebelum mengunduh dan menjalankannya di lingkungan Anda.</p>
            <div class="flex flex-col gap-6 min-[1100px]:flex-row">
                <div class="flex min-w-0 flex-col gap-6 rounded-[10px] border border-line bg-surface p-7 min-[1100px]:flex-1 dark:border-slate-700 dark:bg-night">
                    <div class="text-sm/[1.55] font-semibold text-accent dark:text-indigo-300">Scenario URL</div>
                    @foreach ($scenarios as $scenario)
                        <div class="flex flex-col gap-2.5">
                            <strong class="text-sm/[1.55] font-semibold">{{ $scenario['title'] }}</strong>
                            <span class="font-mono text-xs/[1.55] break-words text-subtle dark:text-slate-400">{{ $scenario['url'] }}</span>
                        </div>
                    @endforeach
                    <x-button variant="accent" href="#signup">Lanjutkan Generate</x-button>
                </div>
                <div class="flex min-w-0 flex-col overflow-hidden rounded-lg bg-night min-[1100px]:flex-1">
                    <div class="flex justify-between border-b border-[#34405A] px-3 py-2 font-mono text-[11px] text-[#DCE4F2]">
                        <span>test_keranjang.py</span>
                        <span class="text-[10px] text-[#94A4BE]">Python · Selenium</span>
                    </div>
                    <ol start="18" class="list-decimal space-y-0.5 py-3 pr-3 pl-10 font-mono text-[11px]/[1.5] text-[#9AD9BD] marker:text-[#94A4BE]">
                        <li class="text-[#94A4BE]"># Scenario: {{ $scenarios[0]['title'] }}</li>
                        <li class="pl-4">driver.get("{{ $scenarios[0]['url'] }}")</li>
                        <li class="pl-4">wait.until(EC.element_to_be_clickable((By.ID, "add-to-cart"))).click()</li>
                        <li class="pl-4">driver.get("{{ $scenarios[1]['url'] }}")</li>
                        <li class="pl-4"><span class="text-[#B9A7FF]">assert</span> "Tas Kanvas" in wait.until(</li>
                        <li class="pl-8">EC.visibility_of_element_located((By.ID, "cart-items"))).text</li>
                        <li class="text-[#94A4BE]"># Scenario: {{ $scenarios[1]['title'] }}</li>
                        <li class="pl-4">driver.find_element(By.CSS_SELECTOR, "[data-action=remove]").click()</li>
                        <li class="pl-4"><span class="text-[#B9A7FF]">assert</span> "Keranjang kosong" in wait.until(</li>
                        <li class="pl-8">EC.visibility_of_element_located((By.ID, "cart-empty"))).text</li>
                    </ol>
                </div>
            </div>
        </section>

        <div id="signup" class="flex flex-col items-center gap-6 bg-night px-6 py-20 text-center">
            <h2 class="max-w-[760px] text-[28px]/[1.25] text-white min-[1100px]:text-[38px]/[1.25]">Ubah User-Story Anda menjadi Automated Test Case</h2>
            <p class="text-base/[1.6] text-[#DCE4F2]">Buat akun dan ubah user story anda menjadi Automated Test Case.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <x-button variant="cta-primary" :href="route('register')">Register</x-button>
                <x-button variant="cta-secondary" :href="$menu['docs']['href']">Baca Docs</x-button>
            </div>
        </div>
    </main>
@endsection
