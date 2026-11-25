@props(['testCase'])

@php
    $authProfile = $testCase->authProfile;
    $credentials = $authProfile?->credentials ?? [];
    if ($credentials === []) {
        $credentials = ['email' => ''];
    }
@endphp

<section class="flex flex-col gap-4 rounded-xl border border-edge bg-white p-5 dark:border-slate-800 dark:bg-night">
    <div class="flex flex-col gap-1">
        <h2 class="font-display text-sm/[1.55] font-semibold text-body dark:text-slate-100">Auth Profile</h2>
    </div>

    <form method="POST" action="{{ route('auth-profiles.upsert', $testCase) }}" data-auth-profile-form
        data-upsert-url="{{ route('auth-profiles.upsert', $testCase) }}"
        data-destroy-url="{{ route('auth-profiles.destroy', $testCase) }}"
        class="flex flex-col gap-4">
        @csrf
        {{-- Authentication Mode --}}
        <div class="atlas-auth-mode-section">

            <div class="atlas-config-section-title">
                <div>
                    <h3>Authentication Mode</h3>
                    <p>
                        Choose how ATLAS accesses the page before testing begins.
                    </p>
                </div>

                <span class="atlas-config-optional">
                    Settings
                </span>
            </div>

            <div class="atlas-auth-mode-options">

                {{-- No Auth --}}
                <label class="atlas-auth-mode-option">
                    <input
                        type="radio"
                        name="use_auth"
                        value="0"
                        @checked($authProfile === null)
                    >

                    <span class="atlas-auth-mode-icon atlas-auth-mode-icon--public">
                        <svg width="19" height="19" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18"/>
                            <path d="M12 3a15 15 0 0 1 0 18"/>
                            <path d="M12 3a15 15 0 0 0 0 18"/>
                        </svg>
                    </span>

                    <span class="atlas-auth-mode-copy">
                        <strong>No Auth</strong>
                        <small>
                            Public page; no login is required.
                        </small>
                    </span>
                </label>

                {{-- Authentication --}}
                <label class="atlas-auth-mode-option">
                    <input
                        type="radio"
                        name="use_auth"
                        value="1"
                        @checked($authProfile !== null)
                    >

                    <span class="atlas-auth-mode-icon atlas-auth-mode-icon--private">
                        <svg width="19" height="19" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                    </span>

                    <span class="atlas-auth-mode-copy">
                        <strong>Authentication</strong>
                        <small>
                            A page that requires authentication.
                        </small>
                    </span>
                </label>

            </div>

            {{-- Information displayed for No Auth --}}
            <div class="atlas-no-auth-info">

                <div class="atlas-no-auth-info-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 11v5M12 8h.01"/>
                    </svg>
                </div>

                <div>
                    <strong>Does not require authentication</strong>
                    <p>
                        ATLAS can test public pages without storing credentials. If the scenario involves testing the login process, select this mode.
                    </p>
                    @if ($authProfile)
                        <p class="atlas-auth-delete-note">
                            Selecting the "No Auth" option will remove the previously saved Auth Profile.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div data-auth-fields @if ($authProfile === null) hidden @endif class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="login-url" class="console-label">Login URL</label>
                <input id="login-url" name="login_url" type="url" value="{{ $authProfile?->login_url }}"
                    placeholder="https://app.example.com/login" class="console-field">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="submit-selector" class="console-label">Submit Selector (opsional)</label>
                <input id="submit-selector" name="submit_selector" type="text" value="{{ $authProfile?->submit_selector }}"
                    placeholder="css:button[type=submit]" class="console-field">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="success-indicator" class="console-label">Success Indicator (opsional)</label>
                <input id="success-indicator" name="success_indicator" type="text" value="{{ $authProfile?->success_indicator }}"
                    placeholder="id:dashboard" class="console-field">
            </div>

            <div class="flex flex-col gap-2">
                <span class="console-label">Credentials</span>

                <div data-credential-rows class="flex flex-col gap-2">
                    @foreach ($credentials as $selector => $value)
                        <div data-credential-row class="flex flex-col gap-2 min-[620px]:flex-row">
                            <input data-credential-selector value="{{ $selector }}" placeholder="email"
                                aria-label="Credential selector"
                                class="console-field h-9 min-w-0 flex-1 text-xs">
                            <input data-credential-value type="text" value="{{ $value }}" autocomplete="off"
                                placeholder="Enter credential values" aria-label="Credential value"
                                class="console-field h-9 min-w-0 flex-1 text-xs">
                            <button type="button" data-remove-credential
                                class="console-btn-ghost h-9 px-3 text-xs">Delete</button>
                        </div>
                    @endforeach
                </div>

                <div>
                    <button
                        type="button"
                        data-add-credential
                        class="inline-flex w-fit items-center gap-1.5 rounded-md border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-[11px] font-semibold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                    >
                        <svg
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path d="M12 5v14M5 12h14"/>
                        </svg>

                        <span>Add Credentials</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="console-btn-primary h-9 px-4 text-xs">Save Settings</button>
            <p data-auth-status class="console-hint" role="status"></p>
        </div>
    </form>
</section>

<script>
    (function () {
        var form = document.querySelector('[data-auth-profile-form]');

        if (!form) {
            return;
        }

        var fields = form.querySelector('[data-auth-fields]');
        var status = form.querySelector('[data-auth-status]');
        var rows = form.querySelector('[data-credential-rows]');
        var token = form.querySelector('input[name="_token"]').value;

        function useAuth() {
            return form.querySelector('input[name="use_auth"]:checked').value === '1';
        }

        function setStatus(message, error) {
            status.textContent = message;
            status.classList.toggle('text-red-600', Boolean(error));
            status.classList.toggle('dark:text-red-400', Boolean(error));
        }

        form.querySelectorAll('input[name="use_auth"]').forEach(function (input) {
            input.addEventListener('change', function () {
                fields.hidden = !useAuth();
            });
        });

        rows.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove-credential]');

            if (!remove || rows.querySelectorAll('[data-credential-row]').length === 1) {
                return;
            }

            remove.closest('[data-credential-row]').remove();
        });

        form.querySelector('[data-add-credential]').addEventListener('click', function () {
            var template = rows.querySelector('[data-credential-row]');
            var clone = template.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            rows.appendChild(clone);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setStatus('Saving...');

            var method = useAuth() ? 'PUT' : 'DELETE';
            var url = useAuth() ? form.dataset.upsertUrl : form.dataset.destroyUrl;
            var options = {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            };

            if (useAuth()) {
                var loginUrl = form.querySelector('[name="login_url"]').value.trim();
                var credentials = {};

                rows.querySelectorAll('[data-credential-row]').forEach(function (row) {
                    var selector = row.querySelector('[data-credential-selector]').value.trim();
                    var value = row.querySelector('[data-credential-value]').value;

                    if (selector && value) {
                        credentials[selector] = value;
                    }
                });

                if (!loginUrl) {
                    setStatus('Login URL is required.', true);
                    return;
                }

                if (Object.keys(credentials).length === 0) {
                    setStatus('Provide at least one selector and a credential value.', true);
                    return;
                }

                var submitSelector = form.querySelector('[name="submit_selector"]').value.trim();
                var successIndicator = form.querySelector('[name="success_indicator"]').value.trim();

                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify({
                    login_url: loginUrl,
                    credentials: credentials,
                    submit_selector: submitSelector || null,
                    success_indicator: successIndicator || null,
                });
            }

            try {
                var response = await fetch(url, options);
                var body = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok) {
                    setStatus(body.message || 'Failed to save Auth Profile.', true);
                    return;
                }

                setStatus(body.message || 'Auth Profile settings saved.');
            } catch (error) {
                setStatus('Failed to save Auth Profile.', true);
            }
        });
    })();
</script>