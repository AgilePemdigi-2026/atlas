@extends('layouts.app')

@section('title', 'ATLAS')

@section('content')
    @php
        // Logo huruf "A" yang sama dengan halaman login/register
        $logo = '<path d="M3.5 20 11 4h2l7.5 16"/><path d="M7.5 14h9"/>';

        $features = [
            ['title' => 'AI-Assisted Generation', 'text' => 'Turn user stories into Selenium scripts.', 'tone' => 'green',
             'icon' => '<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>'],
            ['title' => 'Flexible Configuration', 'text' => 'Set your target URL and login details.', 'tone' => 'indigo',
             'icon' => '<path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/>'],
            ['title' => 'Automated Execution', 'text' => 'Run your scripts and see pass or fail.', 'tone' => 'green',
             'icon' => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4Z"/>'],
            ['title' => 'Review & History', 'text' => 'Review, download, and revisit every test.', 'tone' => 'indigo',
             'icon' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8"/>'],
        ];

        $script = [
            ['def test_add_product_to_cart():', 'text-[#AEB2FF]'],
            ['    driver, wait = setup_driver()', 'text-[#CBD2E1]'],
            ['try:', 'text-[#AEB2FF]'],
            ['    driver.get("https://shop.example/products/canvas-bag")', 'text-[#CBD2E1]'],
            ['    wait.until(EC.element_to_be_clickable((By.ID, "add-to-cart"))).click()', 'text-[#CBD2E1]'],
            ['    assert "Canvas Bag" in wait.until(', 'text-[#AEB2FF]'],
            ['        EC.visibility_of_element_located((By.ID, "cart-items"))', 'text-[#CBD2E1]'],
            ['    ).text', 'text-[#CBD2E1]'],
            ['finally:', 'text-[#AEB2FF]'],
            ['    driver.quit()', 'text-[#CBD2E1]'],
        ];
    @endphp

    <div class="relative">
    @include('partials.hero-art')

    <main class="font-dm">
        {{--  HERO  --}}
        <div class="relative z-10 flex flex-col items-start gap-10 px-6 py-14 min-[1100px]:min-h-[678px] min-[1100px]:flex-row min-[1100px]:items-center min-[1100px]:justify-between min-[1100px]:gap-12 min-[1100px]:py-0 min-[1100px]:pr-[119px] min-[1100px]:pl-[122px]">
            <div class="flex max-w-[646px] flex-col gap-6">
                <p class="font-typewriter text-xs/[1.55] font-semibold text-[#5FBB49]">FROM USER STORIES TO AUTOMATED TESTING</p>
                <h1 class="font-display text-[34px]/[1.2] font-extrabold tracking-[-.02em] text-[#0E2D07] min-[1100px]:text-[44px]/[1.2] dark:text-white">Turn User Stories<br>into Automated Test Scripts</h1>
                <p class="font-iosevka text-[17px]/[1.55] text-[#595959] dark:text-slate-300">Transform your user stories and Gherkin-based acceptance criteria into Selenium test scripts with the assistance of an LLM.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="#signup" class="grid h-11 w-[190px] place-items-center rounded-md bg-[#5FBB49] text-sm font-semibold text-white transition hover:brightness-95">Get Started</a>
                    <a href="#process" class="grid h-11 w-[180px] place-items-center rounded-md border border-[#DDE2EB] bg-white text-sm font-semibold text-[#236913] transition hover:bg-[#F8FAF7] dark:border-slate-700 dark:bg-night dark:text-emerald-300">Explore Process</a>
                </div>
            </div>

            <div class="flex w-full flex-none flex-col gap-5 rounded-[50px] border border-[#E0EBDD] bg-[#EEF3FC] p-7 shadow-[0_5px_20px_rgba(0,0,0,.25)] min-[1100px]:w-[410px] dark:border-slate-700 dark:bg-night">
                <div class="flex items-center gap-2.5 font-typewriter text-lg text-[#0E2D07] dark:text-white">
                    <svg class="size-6 text-[#4CAF41]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $logo !!}</svg>
                    ATLAS
                </div>
                <div class="flex flex-col gap-4 rounded-[25px] bg-white p-5 dark:bg-abyss">
                    <div class="text-xs/[1.55] font-semibold text-[#4958BB] dark:text-indigo-300">USER STORY</div>
                    <h3 class="text-xl/6 tracking-[-.02em] text-[#202638] dark:text-white">Generate Test Case Script</h3>
                    <p class="font-mono text-xs/[1.55] text-[#202638] dark:text-slate-200">Scenario: Successful Script Generation<br>Given I am logged in and on the "Test Case" page<br>And I fill in the User Story / Acceptance Criteria and optional Base URL<br>When I press the "Generate" button<br>Then I should see a confirmation that the script was successfully generated<br>And I should still in “Test Case” page</p>
                    <a href="#signup" class="grid h-11 place-items-center rounded-md bg-[#4958BB] text-sm font-semibold text-white shadow-[inset_0_4px_10px_rgba(0,0,0,.23)] transition hover:brightness-110">Generate Test</a>
                </div>
            </div>
        </div>

        {{--  01 / HOW IT WORKS  --}}
        <section id="process" class="relative z-10 flex flex-col gap-7 px-6 py-12 min-[1100px]:px-[104px] min-[1100px]:py-16">
            <div class="font-typewriter text-sm/[1.55] font-semibold text-[#5FBB49]">01 / HOW IT WORKS</div>
            <h2 class="font-display text-[28px]/[1.2] font-bold tracking-[-.02em] text-[#202638] min-[1100px]:text-4xl/[1.2] dark:text-white">From Requirements to Automated Testing</h2>
            <p class="text-base/[1.55] text-[#626C80] dark:text-slate-400">ATLAS streamlines the test automation workflow, helping you transform written requirements into executable Selenium test scripts.</p>

            <div class="grid grid-cols-1 gap-10 rounded-[14px] border border-[#DDE2EB] bg-[#F8FAF7] p-6 min-[1100px]:grid-cols-3 min-[1100px]:gap-8 min-[1100px]:p-10 dark:border-slate-700 dark:bg-night">
                @foreach ($steps as $step)
                    @php $tone = $loop->odd ? 'text-[#4B50C8] dark:text-indigo-300' : 'text-[#4CAF41] dark:text-emerald-300'; @endphp
                    <div class="flex min-w-0 flex-col items-center gap-3">
                        <div class="text-center text-sm/[22px] font-semibold {{ $tone }}">{{ $step['label'] }}</div>
                        {{-- Nomor di atas kartu (tidak lagi menimpa teks) --}}
                        <div class="font-display text-[64px]/none font-extrabold {{ $tone }}">{{ sprintf('%02d', $loop->iteration) }}</div>
                        <div class="w-full flex-1 rounded-md border border-[#DDE2EB] bg-white p-5 text-center font-mono text-[13px]/[1.55] text-[#202638] shadow-[0_-3px_8px_rgba(0,0,0,.08)] dark:border-slate-700 dark:bg-abyss dark:text-slate-200">{{ $step['body'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        {{--  02 / WHAT ATLAS OFFERS  --}}
        <section id="features" class="relative z-10 border-y border-[#ECEEF4] bg-[radial-gradient(ellipse_at_8%_18%,rgba(85,197,74,.08),transparent_30%),radial-gradient(ellipse_at_92%_82%,rgba(77,79,210,.08),transparent_32%)] bg-[#F8FAF8] px-6 py-20 min-[1100px]:py-[100px] dark:border-slate-800 dark:bg-night dark:bg-none">
            <div class="mx-auto flex max-w-[1180px] flex-col items-center gap-14">
                <div class="flex max-w-[700px] flex-col items-center gap-4 text-center">
                    <div class="font-typewriter text-sm font-semibold tracking-[.04em] text-[#3CAE36]">02 / WHAT ATLAS OFFERS</div>
                    <h2 class="font-display text-[30px]/[1.25] font-bold tracking-[-.02em] text-[#1E2534] min-[1100px]:text-[46px]/[1.25] dark:text-white">Everything You Need for Test Automation</h2>
                    <p class="text-[13px]/[1.6] text-[#6F7788] dark:text-slate-400">From user story to test result, all in one place.</p>
                </div>

                <div class="relative grid w-full grid-cols-1 gap-10 min-[700px]:grid-cols-2 min-[1100px]:grid-cols-4 min-[1100px]:gap-0">
                    <div aria-hidden="true" class="absolute top-[47px] right-[12.5%] left-[12.5%] hidden h-px bg-[linear-gradient(90deg,#9DDD99,#AAAAF0_50%,#9DDD99)] min-[1100px]:block"></div>
                    @foreach ($features as $feature)
                        @php $green = $feature['tone'] === 'green'; @endphp
                        <div class="relative flex flex-col items-center gap-6 px-4 text-center">
                            <div class="relative">
                                <div class="grid size-[95px] place-items-center rounded-full bg-white shadow-[0_14px_33px_rgba(70,73,144,.10)] ring-2 ring-inset {{ $green ? 'text-[#3CAE36] ring-[#D7EAD5]' : 'text-[#4D4FD2] ring-[#E1E3F3]' }} dark:bg-abyss">
                                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $feature['icon'] !!}</svg>
                                </div>
                                <span class="absolute top-2 -right-6 font-cousine text-[10px] {{ $green ? 'text-[#65AD62]' : 'text-[#9CA1B1]' }}">{{ sprintf('%02d', $loop->iteration) }}</span>
                            </div>
                            <h3 class="font-display text-[18px]/6 font-semibold text-[#1E2534] dark:text-white">{{ $feature['title'] }}</h3>
                            <p class="text-[13px]/[1.7] text-[#6F7788] dark:text-slate-400">{{ $feature['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{--  03 / SEE IT IN ACTION      --}}
        <section id="preview" class="relative z-10 overflow-x-clip px-6 py-16 min-[1100px]:py-[88px]">
    @include('partials.preview-art')
            <div class="relative z-10 mx-auto flex max-w-[1180px] flex-col gap-10">
                <div class="flex flex-col gap-4">
                    <div class="text-[10px]/[15px] font-bold tracking-[.05em] text-[#3CAE36]">03 / SEE IT IN ACTION</div>
                    <h2 class="font-display text-[28px]/[1.3] font-bold tracking-[-.02em] text-[#1E2534] min-[1100px]:text-4xl/[1.3] dark:text-white">From a Simple Story to a Test Script</h2>
                    <p class="text-[13px]/[1.7] text-[#6F7788] dark:text-slate-400">Turn your testing requirements into structured Selenium scripts without writing every step manually.</p>
                </div>

                <div class="relative grid grid-cols-1 gap-6 rounded-[15px] border border-[#E5E7EC] bg-white p-3 shadow-[0_19px_58px_rgba(30,38,57,.08)] min-[1100px]:grid-cols-2 dark:border-slate-700 dark:bg-night">
                    {{-- Kiri: user story --}}
                    <div class="overflow-hidden rounded-[7px] border border-[#E3E7E4] bg-[radial-gradient(ellipse_at_0%_100%,rgba(85,197,74,.08),transparent_40%)] bg-[#FBFCFB] dark:border-slate-700 dark:bg-abyss dark:bg-none">
                        <div class="flex items-center justify-between border-b border-[#E8EAF0] px-5 py-4 dark:border-slate-700">
                            <span class="text-[9.5px] font-bold tracking-[.05em] text-[#4D4FD2] dark:text-indigo-300">YOUR USER STORY</span>
                            <span class="text-[8.5px] text-[#9399A6]">Gherkin input</span>
                        </div>
                        <div class="flex flex-col gap-8 px-6 py-7">
                            <p class="text-sm/[1.65] text-[#1E2534] dark:text-slate-200">As a customer, I want to add a product to my cart so that I can purchase it later.</p>
                            <pre class="overflow-x-auto border-l-2 border-[#4D4FD2] bg-[#F8F8FB] p-5 font-cousine text-[11px]/[1.75] text-[#4F5665] dark:bg-night dark:text-slate-300">Scenario: Add product to cart
  Given I am on the product page
  When I click the "Add to Cart" button
  Then the cart should contain the product</pre>
                        </div>
                    </div>

                    {{-- Panah di tengah --}}
                    <div aria-hidden="true" class="absolute top-1/2 left-1/2 z-10 hidden size-11 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-[linear-gradient(145deg,#55C54A,#4D4FD2)] text-[22px] text-white ring-[5px] ring-white min-[1100px]:grid dark:ring-night">→</div>

                    {{-- Kanan: script hasil generate --}}
                    <div class="min-w-0 overflow-hidden rounded-[7px] border border-[#2B354C] bg-[#161F34]">
                        <div class="flex items-center justify-between border-b border-[#30394E] px-5 py-4">
                            <span class="text-[9.5px] font-bold tracking-[.05em] text-[#ADB1FF]">GENERATED SELENIUM SCRIPT</span>
                            <span class="text-[8.5px] text-[#9399A6]">Python · Selenium</span>
                        </div>
                        <div class="overflow-x-auto py-6">
                            @foreach ($script as [$code, $color])
                                <div class="flex min-w-max font-cousine text-[11px]/[1.85]">
                                    <span class="w-12 shrink-0 pr-4 text-right text-[#596379]">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <span class="whitespace-pre pr-6 {{ $color }}">{{ $code }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{--  CTA  --}}
        <div id="signup" class="relative overflow-hidden bg-[radial-gradient(ellipse_at_15%_20%,rgba(85,197,74,.28),transparent_35%),radial-gradient(ellipse_at_88%_75%,rgba(100,102,235,.34),transparent_38%),linear-gradient(125deg,#152134,#17243A_52%,#252452)] px-6 py-20 text-center min-[1100px]:py-[88px]">
            <div aria-hidden="true" class="absolute -bottom-32 -left-16 size-[330px] rounded-full border border-[#77DA6F]/25"></div>
            <div class="relative mx-auto flex max-w-[780px] flex-col items-center gap-5">
                <div class="font-typewriter text-lg font-semibold tracking-[.04em] text-[#78D570] min-[1100px]:text-[21px]">START WITH ATLAS</div>
                <h2 class="font-display text-[30px]/[1.3] font-medium tracking-[-.02em] text-white min-[1100px]:text-[48px]/[1.3]">Ready to Simplify Test Automation?</h2>
                <p class="text-[13px]/[1.5] text-[#B7BFCE]">Start transforming your user stories into Selenium test scripts with ATLAS.</p>
                <div class="mt-3 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register') }}" class="grid h-11 place-items-center rounded-[5px] bg-[#4D4FD2] px-[30px] text-[11px] font-semibold text-white shadow-[0_6px_18px_rgba(77,79,210,.28)] transition hover:brightness-110">Create Your Account</a>
                    <a href="{{ $menu['docs']['href'] ?? '#' }}" class="grid h-11 place-items-center rounded-[5px] bg-white px-[30px] text-[11px] font-semibold text-[#161F34] transition hover:bg-[#F1F3F8]">Read the Documentation</a>
                </div>
            </div>
        </div>
    </main>
    </div>

    {{--  FOOTER  --}}
    <footer class="flex flex-col items-center gap-3 bg-[#F7F8FA] px-6 py-8 text-center font-sans text-xs text-[#626C80] dark:bg-abyss dark:text-slate-400">
        <p>© {{ date('Y') }} ATLAS. Semua hak dilindungi.</p>
        <p>ATLAS: Automated Test case generation via LLM-Assisted Selenium scripting.</p>
    </footer>
@endsection