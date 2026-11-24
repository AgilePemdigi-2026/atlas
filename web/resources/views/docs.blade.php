@extends('layouts.app')

@section('title', 'Documentation – ATLAS')

@section('bare', true)

@section('content')
    @php
        $logo = '<path d="M3.5 20 11 4h2l7.5 16"/><path d="M7.5 14h9"/>';

        $navigation = [
            [
                'label' => 'Getting Started',
                'links' => [
                    ['id' => 'introduction', 'title' => 'Introduction'],
                    ['id' => 'how-it-works', 'title' => 'How it works'],
                    ['id' => 'generating-a-script', 'title' => 'Generating a script'],
                ],
            ],
            [
                'label' => 'Configuration',
                'links' => [
                    ['id' => 'base-url', 'title' => 'Base URL & scenario URLs'],
                    ['id' => 'auth-profile', 'title' => 'Auth Profile'],
                ],
            ],
            [
                'label' => 'Reference',
                'links' => [
                    ['id' => 'supported-actions', 'title' => 'Supported actions'],
                    ['id' => 'tips', 'title' => 'Tips'],
                ],
            ],
        ];

        $toc = array_merge(...array_column($navigation, 'links'));
    @endphp

    <div class="min-h-screen bg-white font-dm text-body dark:bg-abyss dark:text-slate-200">
        <header class="sticky top-0 z-50 border-b border-line bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-night/95">
            <div class="mx-auto flex h-16 max-w-[1440px] items-center gap-4 px-5 min-[1100px]:px-8">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="ATLAS – Home">
                    <svg class="size-7 text-[#4CAF41]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $logo !!}</svg>
                    <span class="font-typewriter text-xl font-semibold text-[#0E2D07] dark:text-white">ATLAS</span>
                </a>

                <span class="h-6 w-px bg-line dark:bg-slate-700"></span>

                <a href="{{ route('docs') }}" class="hidden text-[13px] font-semibold text-[#236913] dark:text-emerald-300 min-[700px]:block">Documentation</a>

                <nav class="ml-auto flex items-center gap-1.5 min-[700px]:gap-3">
                    <a href="{{ route('home') }}" class="hidden rounded-md px-3 py-2 text-[13px] text-subtle transition hover:bg-surface hover:text-body dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white min-[700px]:block">Home</a>

                    @auth
                        <a href="{{ route('console') }}" class="hidden rounded-md px-3 py-2 text-[13px] text-subtle transition hover:bg-surface hover:text-body dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white min-[900px]:block">Console</a>
                        <a href="{{ route('profile') }}" class="hidden rounded-md px-3 py-2 text-[13px] text-subtle transition hover:bg-surface hover:text-body dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white min-[900px]:block">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md px-3 py-2 text-[13px] text-subtle transition hover:bg-surface hover:text-body dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white">Log out</button>
                        </form>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}" class="rounded-md border border-[#D7EAD5] bg-[#E9FFE4] px-4 py-2 text-[13px] font-semibold text-[#0E2D07] transition hover:brightness-95 dark:border-emerald-400/30 dark:bg-emerald-400/15 dark:text-emerald-200">Log in</a>
                    @endguest
                </nav>
            </div>
        </header>

        <div class="mx-auto flex max-w-[1440px]">
            <aside class="hidden w-[264px] shrink-0 border-r border-line dark:border-slate-800 min-[1100px]:block">
                <nav aria-label="Documentation" class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto px-6 py-8">
                    @foreach ($navigation as $group)
                        <div class="{{ $loop->first ? '' : 'mt-7' }}">
                            <p class="font-typewriter text-[10px] font-semibold uppercase tracking-[.12em] text-subtle dark:text-slate-500">{{ $group['label'] }}</p>
                            <ul class="mt-3 flex flex-col gap-0.5">
                                @foreach ($group['links'] as $link)
                                    <li>
                                        <a href="#{{ $link['id'] }}"
                                           @if ($loop->parent->first && $loop->first) aria-current="true" @endif
                                           class="-ml-px block border-l-2 py-1.5 pl-3 text-[13px]/[1.5] transition {{ $loop->parent->first && $loop->first ? 'border-[#3CAE36] font-semibold text-[#236913] dark:border-emerald-400 dark:text-emerald-300' : 'border-transparent text-subtle hover:border-[#B9DDB3] hover:text-[#236913] dark:text-slate-400 dark:hover:border-emerald-700 dark:hover:text-emerald-300' }}">
                                            {{ $link['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 flex-1 px-5 py-8 min-[1100px]:px-10 min-[1100px]:py-10">
                <details class="mb-6 rounded-md border border-line bg-surface px-4 py-3 dark:border-slate-700 dark:bg-night min-[1100px]:hidden">
                    <summary class="font-display text-sm font-semibold text-body dark:text-white">Documentation menu</summary>
                    <nav aria-label="Documentation" class="mt-4 flex flex-col gap-5 border-t border-line pt-4 dark:border-slate-700">
                        @foreach ($navigation as $group)
                            <div>
                                <p class="font-typewriter text-[10px] font-semibold uppercase tracking-[.12em] text-subtle dark:text-slate-500">{{ $group['label'] }}</p>
                                <ul class="mt-2 flex flex-col gap-1.5">
                                    @foreach ($group['links'] as $link)
                                        <li><a href="#{{ $link['id'] }}" class="text-[13px] text-subtle hover:text-[#236913] dark:text-slate-400 dark:hover:text-emerald-300">{{ $link['title'] }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </nav>
                </details>

                <div class="flex gap-10">
                    <article class="min-w-0 max-w-[820px] flex-1">
                        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-[12px] text-subtle dark:text-slate-400">
                            <a href="{{ route('docs') }}" class="hover:text-[#236913] dark:hover:text-emerald-300">Documentation</a>
                            <span aria-hidden="true">/</span>
                            <span class="text-body dark:text-slate-200">Introduction</span>
                        </nav>

                        <h1 class="mt-4 font-display text-[34px]/[1.2] font-extrabold tracking-[-.02em] text-[#1E2534] dark:text-white">Introduction</h1>
                        <p class="mt-4 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">ATLAS turns a user story and Gherkin acceptance criteria into a runnable Python Selenium test script. Write the story in plain language, point ATLAS at the target page, and review the result in the Console.</p>

                        <section id="introduction" class="mt-10 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                What is ATLAS?
                                <a href="#introduction" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to What is ATLAS?">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">ATLAS is a web application for testers. It extracts the interactive elements from the target page, maps each Gherkin step to a Selenium action, validates the result, and generates a script you can download and run.</p>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">No special markup is required. A story written for a ticket, with <code class="rounded bg-surface px-1.5 py-0.5 font-cousine text-[12px] text-[#4F5665] dark:bg-night dark:text-slate-300">Given</code>, <code class="rounded bg-surface px-1.5 py-0.5 font-cousine text-[12px] text-[#4F5665] dark:bg-night dark:text-slate-300">When</code>, and <code class="rounded bg-surface px-1.5 py-0.5 font-cousine text-[12px] text-[#4F5665] dark:bg-night dark:text-slate-300">Then</code> steps, is enough to start.</p>

                            <div class="mt-5 rounded-r-md border-l-[3px] border-[#4D4FD2] bg-[#F7F8FF] px-5 py-4 dark:border-indigo-500 dark:bg-indigo-950/30">
                                <p class="font-semibold text-[#3B3F9E] dark:text-indigo-200">Note</p>
                                <p class="mt-1 text-[13.5px]/[1.7] text-[#4F5665] dark:text-slate-300">Credentials are stored in an Auth Profile, never in the user story or the generated script. The script reads them from environment variables at run time.</p>
                            </div>
                        </section>

                        <section id="how-it-works" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                How it works
                                <a href="#how-it-works" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to How it works">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">ATLAS reads the story, extracts the page structure, and converts every step into a concrete Selenium action. The result is a script that follows the same order as your acceptance criteria.</p>

                            <div class="mt-5 grid grid-cols-1 gap-4 min-[900px]:grid-cols-2">
                                <div class="overflow-hidden rounded-md border border-line bg-surface dark:border-slate-700 dark:bg-night">
                                    <div class="flex items-center justify-between border-b border-line px-4 py-2.5 dark:border-slate-700">
                                        <span class="font-typewriter text-[10px] font-semibold tracking-[.08em] text-[#4D4FD2] dark:text-indigo-300">YOUR STORY</span>
                                        <span class="text-[10px] text-subtle dark:text-slate-500">Gherkin</span>
                                    </div>
                                    <pre class="overflow-x-auto px-4 py-4 font-cousine text-[11px]/[1.8] text-[#4F5665] dark:text-slate-300">Scenario: Add product to cart
  Given I am on the product page
  When I click the "Add to Cart" button
  Then the cart should contain the product</pre>
                                </div>

                                <div class="overflow-hidden rounded-md border border-slate-700 bg-[#0B1020]">
                                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                                        <span class="font-typewriter text-[10px] font-semibold tracking-[.08em] text-[#ADB1FF]">GENERATED SCRIPT</span>
                                        <span class="text-[10px] text-slate-500">Python</span>
                                    </div>
                                    <pre class="overflow-x-auto px-4 py-4 font-cousine text-[11px]/[1.8] text-slate-300">def add_product_to_cart(driver, urls):
    driver.get(urls[0])
    wait.until(
        EC.element_to_be_clickable(
            (By.ID, "add-to-cart")
        )
    ).click()</pre>
                                </div>
                            </div>
                        </section>

                        <section id="generating-a-script" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                Generating a script
                                <a href="#generating-a-script" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to Generating a script">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">The full flow happens in the Console.</p>

                            <ol class="mt-5 flex flex-col gap-4">
                                <li class="flex gap-4">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#E9FFE4] font-display text-[13px] font-bold text-[#236913] dark:bg-emerald-400/15 dark:text-emerald-300">1</span>
                                    <div>
                                        <p class="font-semibold text-body dark:text-white">Write the story</p>
                                        <p class="mt-1 text-[14px]/[1.7] text-[#5F6675] dark:text-slate-300">Add a title, the user story, and the Gherkin criteria. A Base URL is optional; it pre-fills the scenario URL table.</p>
                                    </div>
                                </li>
                                <li class="flex gap-4">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#EEF0FF] font-display text-[13px] font-bold text-[#3B3F9E] dark:bg-indigo-400/15 dark:text-indigo-300">2</span>
                                    <div>
                                        <p class="font-semibold text-body dark:text-white">Generate</p>
                                        <p class="mt-1 text-[14px]/[1.7] text-[#5F6675] dark:text-slate-300">Press Generate Test. ATLAS extracts the DOM, validates each target, and builds the script.</p>
                                    </div>
                                </li>
                                <li class="flex gap-4">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#E9FFE4] font-display text-[13px] font-bold text-[#236913] dark:bg-emerald-400/15 dark:text-emerald-300">3</span>
                                    <div>
                                        <p class="font-semibold text-body dark:text-white">Review and download</p>
                                        <p class="mt-1 text-[14px]/[1.7] text-[#5F6675] dark:text-slate-300">Check the script and scenario URLs, then download the file from the Console.</p>
                                    </div>
                                </li>
                            </ol>

                            <figure class="mt-6 overflow-hidden rounded-md border border-slate-700 bg-[#0B1020]">
                                <figcaption class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                                    <span class="font-cousine text-[10px] uppercase tracking-[.12em] text-slate-500">Terminal</span>
                                    <button type="button" data-copy-code class="rounded border border-white/10 px-2 py-1 font-cousine text-[10px] text-slate-400 transition hover:border-white/25 hover:text-white">Copy</button>
                                </figcaption>
                                <pre class="overflow-x-auto px-4 py-4 font-cousine text-[12px]/[1.8] text-slate-300"><code>python generated_test.py</code></pre>
                            </figure>
                        </section>

                        <section id="base-url" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                Base URL & scenario URLs
                                <a href="#base-url" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to Base URL and scenario URLs">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">The Base URL pre-fills the URL table for every scenario. If the story contains several <code class="rounded bg-surface px-1.5 py-0.5 font-cousine text-[12px] text-[#4F5665] dark:bg-night dark:text-slate-300">Scenario:</code> headings, each row can point at a different page.</p>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">Every row must contain a valid URL before generation continues. A blank or malformed URL keeps you on the table so it can be corrected.</p>
                        </section>

                        <section id="auth-profile" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                Auth Profile
                                <a href="#auth-profile" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to Auth Profile">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">Open the Config tab and choose <span class="font-semibold text-body dark:text-white">Auth</span> when the target application requires a login. Add the Login URL and one credential row per field.</p>

                            <div class="mt-5 rounded-r-md border-l-[3px] border-[#D69E2E] bg-[#FFFBEB] px-5 py-4 dark:border-amber-500 dark:bg-amber-950/30">
                                <p class="font-semibold text-[#8A5A00] dark:text-amber-200">Security warning</p>
                                <p class="mt-1 text-[13.5px]/[1.7] text-[#6B5A33] dark:text-amber-100/80">Never paste credentials into the user story. ATLAS stores them encrypted and the generated script reads them from environment variables such as <code class="font-cousine text-[12px]">ATLAS_AUTH_EMAIL</code>.</p>
                            </div>
                        </section>

                        <section id="supported-actions" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                Supported actions
                                <a href="#supported-actions" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to Supported actions">#</a>
                            </h2>
                            <p class="mt-3 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">Each Gherkin step maps to one of the actions below.</p>

                            <div class="mt-5 overflow-x-auto rounded-md border border-line dark:border-slate-700">
                                <table class="w-full border-collapse text-left text-[13.5px]">
                                    <thead class="bg-surface text-body dark:bg-night dark:text-slate-200">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 font-semibold">Action</th>
                                            <th scope="col" class="px-4 py-3 font-semibold">Selenium behavior</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-[#5F6675] dark:text-slate-300">
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">navigate</td><td class="px-4 py-3">Open the URL in the step value.</td></tr>
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">fill</td><td class="px-4 py-3">Clear the target field and enter the value.</td></tr>
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">click</td><td class="px-4 py-3">Click the target element.</td></tr>
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">select</td><td class="px-4 py-3">Choose an option in the target dropdown.</td></tr>
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">assert_url</td><td class="px-4 py-3">Verify that the current URL matches the value.</td></tr>
                                        <tr class="border-t border-line dark:border-slate-700"><td class="px-4 py-3 font-cousine text-[12px] text-body dark:text-slate-200">assert_text</td><td class="px-4 py-3">Verify that the value appears on the page.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section id="tips" class="mt-12 scroll-mt-24">
                            <h2 class="group font-display text-[24px]/[1.3] font-bold tracking-[-.01em] text-[#1E2534] dark:text-white">
                                Tips
                                <a href="#tips" class="ml-1 text-[#B9DDB3] opacity-0 transition group-hover:opacity-100 dark:text-emerald-700" aria-label="Link to Tips">#</a>
                            </h2>
                            <ul class="mt-4 flex list-disc flex-col gap-2 pl-5 text-[15px]/[1.75] text-[#5F6675] dark:text-slate-300">
                                <li>Use one <code class="rounded bg-surface px-1.5 py-0.5 font-cousine text-[12px] text-[#4F5665] dark:bg-night dark:text-slate-300">Scenario:</code> heading per flow so each script stays focused.</li>
                                <li>Name visible text, labels, or button text in your steps; ATLAS matches them against the DOM.</li>
                                <li>Store login details in the Auth Profile, not in the story.</li>
                                <li>Regenerate after the target page changes so the locators stay valid.</li>
                            </ul>
                        </section>

                        <nav aria-label="Previous and next" class="mt-16 flex flex-col gap-3 border-t border-line pt-6 dark:border-slate-800 min-[700px]:flex-row min-[700px]:justify-between">
                            <a href="#introduction" class="flex flex-col gap-1 rounded-md border border-line px-4 py-3 transition hover:border-[#B9DDB3] hover:bg-surface dark:border-slate-700 dark:hover:border-emerald-800 dark:hover:bg-night">
                                <span class="text-[11px] text-subtle dark:text-slate-500">Previous</span>
                                <span class="text-[13px] font-semibold text-[#236913] dark:text-emerald-300">← Introduction</span>
                            </a>
                            <a href="#generating-a-script" class="flex flex-col gap-1 rounded-md border border-line px-4 py-3 text-right transition hover:border-[#B9DDB3] hover:bg-surface dark:border-slate-700 dark:hover:border-emerald-800 dark:hover:bg-night">
                                <span class="text-[11px] text-subtle dark:text-slate-500">Next</span>
                                <span class="text-[13px] font-semibold text-[#236913] dark:text-emerald-300">Generating a script →</span>
                            </a>
                        </nav>

                        <footer class="mt-8 border-t border-line pt-5 text-[11px] text-subtle dark:border-slate-800 dark:text-slate-500">
                            © {{ now()->year }} ATLAS. Documentation.
                        </footer>
                    </article>

                    <aside class="hidden w-[210px] shrink-0 min-[1320px]:block">
                        <nav aria-label="On this page" class="sticky top-24">
                            <p class="font-typewriter text-[10px] font-semibold tracking-[.12em] text-subtle dark:text-slate-500">ON THIS PAGE</p>
                            <ul class="mt-3 flex flex-col gap-2 border-l border-line pl-4 dark:border-slate-700">
                                @foreach ($toc as $link)
                                    <li><a href="#{{ $link['id'] }}" class="text-[13px]/[1.5] text-subtle transition hover:text-[#236913] dark:text-slate-400 dark:hover:text-emerald-300">{{ $link['title'] }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy-code]');

            if (!button) {
                return;
            }

            var code = button.closest('figure').querySelector('code');

            if (!code || !navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(code.innerText).then(function () {
                var label = button.textContent;
                button.textContent = 'Copied';
                setTimeout(function () {
                    button.textContent = label;
                }, 1500);
            });
        });
    </script>
@endsection
