@props(['testCase'])

@php
    $authProfile = $testCase->authProfile;
    $credentials = $authProfile?->credentials ?? [];
    if ($credentials === []) {
        $credentials = ['email' => ''];
    }
@endphp

<section class="flex flex-col gap-4 rounded-lg border border-line bg-white p-5 dark:border-slate-700 dark:bg-night">
    <div class="flex flex-col gap-1">
        <h2 class="text-sm/[1.55] font-semibold text-body dark:text-slate-100">Auth Profile</h2>
        <p class="text-xs/[1.55] text-subtle dark:text-slate-400">Hanya dipakai untuk membuka halaman target yang terlindungi. Kredensial tidak masuk ke script.</p>
    </div>

    <form method="POST" action="{{ route('auth-profiles.upsert', $testCase) }}" data-auth-profile-form
        data-upsert-url="{{ route('auth-profiles.upsert', $testCase) }}"
        data-destroy-url="{{ route('auth-profiles.destroy', $testCase) }}"
        class="flex flex-col gap-4">
        @csrf

        <div class="flex flex-wrap gap-5 text-[13px] text-body dark:text-slate-200">
            <label class="flex items-center gap-2">
                <input type="radio" name="use_auth" value="0" @checked($authProfile === null)>
                No auth
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="use_auth" value="1" @checked($authProfile !== null)>
                Auth
            </label>
        </div>

        <div data-auth-fields @if ($authProfile === null) hidden @endif class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="login-url" class="text-sm font-semibold text-body dark:text-slate-100">Login URL</label>
                <input id="login-url" name="login_url" type="url" value="{{ $authProfile?->login_url }}"
                    placeholder="https://app.example.com/login"
                    class="rounded-md border border-line bg-white px-3 py-2 text-sm text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-body dark:text-slate-100">Credential</span>
                <p class="text-xs text-subtle dark:text-slate-400">Selector boleh memakai <code>id:</code>, <code>name:</code>, <code>css:</code>, atau <code>xpath:</code>; tanpa prefix dicari lewat name lalu id.</p>

                <div data-credential-rows class="flex flex-col gap-2">
                    @foreach ($credentials as $selector => $value)
                        <div data-credential-row class="flex flex-col gap-2 min-[620px]:flex-row">
                            <input data-credential-selector value="{{ $selector }}" placeholder="email"
                                aria-label="Selector kredensial"
                                class="h-9 min-w-0 flex-1 rounded-md border border-line bg-white px-3 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                            <input data-credential-value value="{{ $value }}" placeholder="nilai kredensial"
                                aria-label="Nilai kredensial"
                                class="h-9 min-w-0 flex-1 rounded-md border border-line bg-white px-3 text-xs text-body dark:border-slate-600 dark:bg-abyss dark:text-slate-100">
                            <button type="button" data-remove-credential
                                class="h-9 rounded-md border border-line px-3 text-xs font-semibold text-subtle hover:bg-surface dark:border-slate-600 dark:text-slate-300 dark:hover:bg-white/5">Hapus</button>
                        </div>
                    @endforeach
                </div>

                <div>
                    <button type="button" data-add-credential
                        class="text-xs font-semibold text-accent hover:underline dark:text-indigo-300">Tambah kredensial</button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="h-9 rounded-md bg-accent px-4 text-xs font-semibold text-white">Simpan pengaturan</button>
            <p data-auth-status class="text-xs text-subtle dark:text-slate-400" role="status"></p>
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
            setStatus('Menyimpan …');

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
                    setStatus('Login URL wajib diisi.', true);
                    return;
                }

                if (Object.keys(credentials).length === 0) {
                    setStatus('Isi minimal satu selector dan nilai kredensial.', true);
                    return;
                }

                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify({ login_url: loginUrl, credentials: credentials });
            }

            try {
                var response = await fetch(url, options);
                var body = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok) {
                    setStatus(body.message || 'Gagal menyimpan Auth Profile.', true);
                    return;
                }

                setStatus(body.message || 'Pengaturan Auth Profile disimpan.');
            } catch (error) {
                setStatus('Gagal menyimpan Auth Profile.', true);
            }
        });
    })();
</script>
