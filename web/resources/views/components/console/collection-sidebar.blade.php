@props(['collections', 'selected' => null, 'activeCollectionId' => null])

@php
    $sidebarError = $errors->createCollection->first('name')
        ?? $errors->createTestCase->first('name')
        ?? $errors->updateCollection->first('name')
        ?? $errors->renameTestCase->first('name');
@endphp

<div class="flex flex-none flex-col min-[900px]:self-stretch min-[900px]:border-r min-[900px]:border-edge min-[900px]:bg-white dark:min-[900px]:border-slate-800 dark:min-[900px]:bg-night" data-sidebar>
    <aside class="flex w-full flex-col gap-4 border-b border-edge bg-white p-4 min-[900px]:sticky min-[900px]:top-[72px] min-[900px]:max-h-[calc(100vh-72px)] min-[900px]:w-[280px] min-[900px]:overflow-y-auto min-[900px]:border-b-0 min-[1100px]:top-[88px] min-[1100px]:max-h-[calc(100vh-88px)] dark:border-slate-800 dark:bg-night">
    <div class="flex items-center justify-between gap-2">
        <h2 class="font-display text-sm font-semibold text-body dark:text-slate-100">Test Scenario</h2>
        <button type="button" data-new-collection aria-label="Collection baru"
            class="grid size-8 place-items-center rounded-lg border border-edge text-subtle transition hover:border-edge-strong hover:text-accent dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-indigo-300">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        </button>
    </div>

    <form method="POST" action="{{ route('collections.store') }}" data-collection-composer
        {{ $errors->createCollection->any() ? '' : 'hidden' }}
        class="flex items-center gap-1.5">
        @csrf
        <input name="name" type="text" value="{{ old('name') }}" required maxlength="256" placeholder="Nama collection" aria-label="Nama collection"
            class="h-8 min-w-0 flex-1 rounded-lg border border-edge bg-[#f8fafc] px-2.5 text-xs text-body outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-slate-700 dark:bg-abyss dark:text-slate-100">
    </form>

    @if ($sidebarError)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $sidebarError }}</p>
    @endif

    <div class="flex flex-col gap-1" data-collection-list>
        @forelse ($collections as $collection)
            @php
                $isOpen = $selected ? $selected->collection_id == $collection->getKey() : $loop->first;
                $isActive = $activeCollectionId === $collection->getKey();
            @endphp

            <div class="group/branch flex flex-col gap-1" data-branch>
                <div class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[13px]/[1.55] font-semibold text-body transition hover:bg-surface dark:text-slate-100 dark:hover:bg-white/5">
                    <button type="button" data-branch-toggle aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="branch-{{ $collection->getKey() }}" aria-label="Buka atau tutup {{ $collection->name }}"
                        class="grid size-5 flex-none place-items-center rounded text-subtle transition hover:text-accent dark:text-slate-400 dark:hover:text-indigo-300">
                        <svg data-chevron class="size-3.5 flex-none transition-transform {{ $isOpen ? 'rotate-90' : '' }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 4 4 4-4 4" />
                        </svg>
                    </button>
                    <svg class="size-3.5 flex-none stroke-accent dark:stroke-indigo-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M2.5 6.5A1.5 1.5 0 0 1 4 5h3.2l1.3 1.5H16a1.5 1.5 0 0 1 1.5 1.5v6A1.5 1.5 0 0 1 16 15.5H4A1.5 1.5 0 0 1 2.5 14V6.5Z" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                    <a href="{{ route('console', ['collection' => $collection->getKey()]) }}" data-collection-title
                        @class([
                            'min-w-0 flex-1 truncate rounded hover:text-accent dark:hover:text-indigo-300',
                            'text-accent dark:text-indigo-300' => $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif>{{ $collection->name }}</a>
                    <form method="POST" action="{{ route('collections.update', $collection) }}" data-rename hidden class="min-w-0 flex-1">
                        @csrf
                        @method('PATCH')
                        <input name="name" type="text" value="{{ $collection->name }}" required maxlength="256" aria-label="Nama collection"
                            class="h-8 w-full rounded-lg border border-edge bg-[#f8fafc] px-2.5 text-xs font-normal text-body outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-slate-700 dark:bg-abyss dark:text-slate-100">
                    </form>
                    <span class="flex-none text-[11px] font-medium text-subtle dark:text-slate-400">{{ $collection->testCases->count() }}</span>
                    <span class="relative flex-none" data-menu>
                        <button type="button" data-menu-trigger aria-haspopup="menu" aria-expanded="false" aria-label="Opsi {{ $collection->name }}"
                            class="grid size-6 place-items-center rounded-md text-subtle opacity-100 transition min-[900px]:opacity-0 min-[900px]:group-hover/branch:opacity-100 min-[900px]:group-focus-within/branch:opacity-100 hover:bg-surface hover:text-accent dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-indigo-300">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>
                        </button>
                        <span data-menu-panel role="menu" aria-label="Opsi {{ $collection->name }}" hidden
                            class="absolute right-0 z-30 mt-1 w-44 overflow-hidden rounded-lg border border-edge bg-white p-1 shadow-[0_8px_24px_-12px_rgba(16,24,40,.25)] dark:border-slate-700 dark:bg-night">
                            <button type="button" role="menuitem" data-action="rename"
                                class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-body transition hover:bg-surface dark:text-slate-200 dark:hover:bg-white/5">Ubah nama</button>
                            <button type="button" role="menuitem" data-action="new-test-case"
                                class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-body transition hover:bg-surface dark:text-slate-200 dark:hover:bg-white/5">Test case baru</button>
                            <div class="my-1 h-px bg-edge dark:bg-slate-700" role="separator"></div>
                            <button type="button" role="menuitem" data-action="delete"
                                data-delete-url="{{ route('collections.destroy', $collection) }}"
                                data-delete-name="{{ $collection->name }}"
                                data-delete-message="Collection ini beserta seluruh test case di dalamnya akan dihapus."
                                class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-[#BA3D4A] transition hover:bg-surface dark:text-red-300 dark:hover:bg-white/5">Hapus collection</button>
                        </span>
                    </span>
                </div>

                <div id="branch-{{ $collection->getKey() }}" data-branch-panel {{ $isOpen ? '' : 'hidden' }} class="flex flex-col gap-0.5 pl-4">
                    @foreach ($collection->testCases as $testCase)
                        @php
                            $dotClass = match ($testCase->status->value) {
                                'completed' => 'bg-[#23765B] dark:bg-emerald-300',
                                'failed' => 'bg-[#BA3D4A] dark:bg-red-300',
                                default => 'bg-[#9A6700] dark:bg-amber-300',
                            };
                        @endphp
                        <div class="group/leaf flex items-center gap-1 rounded-lg pr-1" data-leaf>
                            <a href="{{ route('console', ['test_case' => $testCase->getKey()]) }}" data-leaf-title
                                @class([
                                    'flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5 text-xs/[1.55] transition',
                                    'bg-[#eef0ff] text-accent dark:bg-indigo-900/40 dark:text-indigo-300' => $selected?->getKey() == $testCase->getKey(),
                                    'text-body hover:bg-surface dark:text-slate-200 dark:hover:bg-white/5' => $selected?->getKey() != $testCase->getKey(),
                                ])>
                                <span @class(['size-1.5 flex-none rounded-full', $dotClass])></span>
                                <span class="min-w-0 flex-1 truncate">{{ $testCase->name }}</span>
                            </a>
                            <form method="POST" action="{{ route('test-cases.update', $testCase) }}" data-rename hidden class="min-w-0 flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="rename" value="1">
                                <input name="name" type="text" value="{{ $testCase->name }}" required maxlength="256" aria-label="Nama test case"
                                    class="h-8 w-full rounded-lg border border-edge bg-[#f8fafc] px-2.5 text-xs text-body outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-slate-700 dark:bg-abyss dark:text-slate-100">
                            </form>
                            <span class="relative flex-none" data-menu>
                                <button type="button" data-menu-trigger aria-haspopup="menu" aria-expanded="false" aria-label="Opsi {{ $testCase->name }}"
                                    class="grid size-6 place-items-center rounded-md text-subtle opacity-100 transition min-[900px]:opacity-0 min-[900px]:group-hover/leaf:opacity-100 min-[900px]:group-focus-within/leaf:opacity-100 hover:bg-surface hover:text-accent dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-indigo-300">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>
                                </button>
                                <span data-menu-panel role="menu" aria-label="Opsi {{ $testCase->name }}" hidden
                                    class="absolute right-0 z-30 mt-1 w-44 overflow-hidden rounded-lg border border-edge bg-white p-1 shadow-[0_8px_24px_-12px_rgba(16,24,40,.25)] dark:border-slate-700 dark:bg-night">
                                    <button type="button" role="menuitem" data-action="rename"
                                        class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-body transition hover:bg-surface dark:text-slate-200 dark:hover:bg-white/5">Ubah nama</button>
                                    <div class="my-1 h-px bg-edge dark:bg-slate-700" role="separator"></div>
                                    <button type="button" role="menuitem" data-action="delete"
                                        data-delete-url="{{ route('test-cases.destroy', $testCase) }}"
                                        data-delete-name="{{ $testCase->name }}"
                                        data-delete-message="Test case ini beserta skenario, auth profile, dan hasil generate di dalamnya akan dihapus."
                                        class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-[#BA3D4A] transition hover:bg-surface dark:text-red-300 dark:hover:bg-white/5">Hapus test case</button>
                                </span>
                            </span>
                        </div>
                    @endforeach

                    @if ($collection->testCases->isEmpty())
                        <p class="px-2 py-1 text-[11px] text-subtle dark:text-slate-400">Belum ada test case.</p>
                    @endif

                    <form method="POST" action="{{ route('test-cases.drafts') }}" data-draft-composer hidden class="flex items-center gap-1.5 py-1">
                        @csrf
                        <input type="hidden" name="collection_id" value="{{ $collection->getKey() }}">
                        <input name="name" type="text" required maxlength="256" placeholder="Nama test case" aria-label="Nama test case"
                            class="h-8 min-w-0 flex-1 rounded-lg border border-edge bg-[#f8fafc] px-2.5 text-xs text-body outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-slate-700 dark:bg-abyss dark:text-slate-100">
                    </form>
                </div>
            </div>
        @empty
            <p class="px-1.5 py-1 text-[11px] text-subtle dark:text-slate-400">Belum ada collection.</p>
        @endforelse
    </div>

    <p class="text-[11px]/[1.55] text-subtle dark:text-slate-400">Pilih test case untuk melihat cerita dan hasil sebelumnya.</p>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        (function () {
            var sidebar = document.querySelector('[data-sidebar]');
            if (!sidebar) {
                return;
            }

            function openBranch(branch) {
                var panel = branch.querySelector('[data-branch-panel]');
                var toggle = branch.querySelector('[data-branch-toggle]');
                var chevron = branch.querySelector('[data-chevron]');

                if (panel) {
                    panel.hidden = false;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
                if (chevron) {
                    chevron.classList.add('rotate-90');
                }
            }

            function closeBranch(branch) {
                var panel = branch.querySelector('[data-branch-panel]');
                var toggle = branch.querySelector('[data-branch-toggle]');
                var chevron = branch.querySelector('[data-chevron]');

                if (panel) {
                    panel.hidden = true;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
                if (chevron) {
                    chevron.classList.remove('rotate-90');
                }
            }

            var branches = Array.prototype.slice.call(sidebar.querySelectorAll('[data-branch]'));

            branches.forEach(function (branch) {
                var toggle = branch.querySelector('[data-branch-toggle]');

                if (toggle) {
                    toggle.addEventListener('click', function () {
                        if (toggle.getAttribute('aria-expanded') === 'true') {
                            closeBranch(branch);
                        } else {
                            openBranch(branch);
                        }
                    });
                }
            });

            var triggers = Array.prototype.slice.call(sidebar.querySelectorAll('[data-menu-trigger]'));

            function panelFor(trigger) {
                return trigger.parentElement.querySelector('[data-menu-panel]');
            }

            function menuItems(panel) {
                return Array.prototype.slice.call(panel.querySelectorAll('[role="menuitem"]'));
            }

            function closeMenu(trigger, panel) {
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }

            function closeMenus(exceptPanel) {
                triggers.forEach(function (trigger) {
                    var panel = panelFor(trigger);
                    if (panel && panel !== exceptPanel) {
                        closeMenu(trigger, panel);
                    }
                });
            }

            function openMenu(trigger, index) {
                var panel = panelFor(trigger);
                if (!panel) {
                    return;
                }

                closeMenus(panel);
                panel.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');

                var items = menuItems(panel);
                if (items.length) {
                    items[typeof index === 'number' ? index : 0].focus();
                }
            }

            function rowFor(trigger) {
                return trigger.closest('[data-branch]') || trigger.closest('[data-leaf]');
            }

            function startRename(trigger) {
                var row = rowFor(trigger);
                if (!row) {
                    return;
                }

                var title = row.querySelector('[data-collection-title], [data-leaf-title]');
                var form = row.querySelector('[data-rename]');

                if (form) {
                    if (title) {
                        title.hidden = true;
                    }
                    form.hidden = false;

                    var input = form.querySelector('input[name="name"]');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }
            }

            function startDraft(trigger) {
                var row = rowFor(trigger);
                if (!row) {
                    return;
                }

                openBranch(row);

                var form = row.querySelector('[data-draft-composer]');
                if (form) {
                    form.hidden = false;

                    var input = form.querySelector('input[name="name"]');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }
            }

            var modal = sidebar.querySelector('[data-delete-modal]');
            var deleteForm = sidebar.querySelector('[data-delete-form]');
            var deleteCopy = modal ? modal.querySelector('[data-delete-copy]') : null;
            var deleteTitle = modal ? modal.querySelector('#delete-dialog-title') : null;
            var deleteCancel = modal ? modal.querySelector('[data-delete-cancel]') : null;
            var deleteConfirm = modal ? modal.querySelector('[data-delete-confirm]') : null;
            var deleteReturnFocus = null;

            function modalKeydown(event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeDelete();
                } else if (event.key === 'Tab' && modal && deleteCancel && deleteConfirm) {
                    var items = [deleteCancel, deleteConfirm];
                    var current = items.indexOf(document.activeElement);
                    event.preventDefault();
                    items[(current + (event.shiftKey ? -1 : 1) + items.length) % items.length].focus();
                }
            }

            function openDelete(item, trigger) {
                if (!modal || !deleteForm) {
                    return;
                }

                deleteTitle.textContent = 'Hapus "' + item.dataset.deleteName + '"?';
                deleteCopy.textContent = item.dataset.deleteMessage;
                deleteForm.dataset.action = item.dataset.deleteUrl;
                deleteReturnFocus = trigger;

                modal.hidden = false;
                deleteCancel.focus();
                document.addEventListener('keydown', modalKeydown);
            }

            function closeDelete() {
                if (!modal) {
                    return;
                }

                modal.hidden = true;
                document.removeEventListener('keydown', modalKeydown);

                if (deleteReturnFocus) {
                    deleteReturnFocus.focus();
                }
            }

            triggers.forEach(function (trigger) {
                var panel = panelFor(trigger);
                if (!panel) {
                    return;
                }

                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (panel.hidden) {
                        openMenu(trigger, 0);
                    } else {
                        closeMenu(trigger, panel);
                    }
                });

                trigger.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openMenu(trigger, 0);
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        var items = menuItems(panel);
                        openMenu(trigger, Math.max(items.length - 1, 0));
                    }
                });

                panel.addEventListener('keydown', function (event) {
                    var items = menuItems(panel);
                    if (!items.length) {
                        return;
                    }

                    var current = items.indexOf(document.activeElement);

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        items[(current + 1) % items.length].focus();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        items[(current - 1 + items.length) % items.length].focus();
                    } else if (event.key === 'Home') {
                        event.preventDefault();
                        items[0].focus();
                    } else if (event.key === 'End') {
                        event.preventDefault();
                        items[items.length - 1].focus();
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        closeMenu(trigger, panel);
                        trigger.focus();
                    } else if (event.key === 'Tab') {
                        closeMenu(trigger, panel);
                    }
                });

                panel.addEventListener('click', function (event) {
                    var item = event.target.closest('[role="menuitem"]');
                    if (!item) {
                        return;
                    }

                    event.preventDefault();
                    closeMenu(trigger, panel);
                    trigger.focus();

                    if (item.dataset.action === 'rename') {
                        startRename(trigger);
                    } else if (item.dataset.action === 'new-test-case') {
                        startDraft(trigger);
                    } else if (item.dataset.action === 'delete') {
                        openDelete(item, trigger);
                    }
                });
            });

            document.addEventListener('pointerdown', function (event) {
                if (!event.target.closest('[data-menu]')) {
                    closeMenus();
                }
            });

            function submitForm(form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }

            function cancelRename(form) {
                var row = form.closest('[data-branch], [data-leaf]');
                var title = row ? row.querySelector('[data-collection-title], [data-leaf-title]') : null;

                form.hidden = true;
                if (title) {
                    title.hidden = false;
                    title.focus();
                }
            }

            sidebar.querySelectorAll('[data-rename]').forEach(function (form) {
                var input = form.querySelector('input[name="name"]');
                if (!input) {
                    return;
                }

                var submitted = false;
                var original = input.value;

                function commit() {
                    if (submitted || !input.value.trim() || input.value === original) {
                        return;
                    }

                    submitted = true;
                    submitForm(form);
                }

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        commit();
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        cancelRename(form);
                    }
                });

                input.addEventListener('blur', function () {
                    setTimeout(commit, 0);
                });
            });

            sidebar.querySelectorAll('[data-draft-composer]').forEach(function (form) {
                var input = form.querySelector('input[name="name"]');
                if (!input) {
                    return;
                }

                var submitted = false;

                function commit() {
                    if (submitted || !input.value.trim()) {
                        return;
                    }

                    submitted = true;
                    submitForm(form);
                }

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        commit();
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        form.hidden = true;
                    }
                });

                input.addEventListener('blur', function () {
                    setTimeout(commit, 0);
                });
            });

            var collectionComposer = sidebar.querySelector('[data-collection-composer]');

            function openComposer() {
                if (!collectionComposer) {
                    return;
                }

                collectionComposer.hidden = false;

                var input = collectionComposer.querySelector('input[name="name"]');
                if (input) {
                    input.focus();
                    input.select();
                }
            }

            var newCollection = sidebar.querySelector('[data-new-collection]');

            if (newCollection) {
                newCollection.addEventListener('click', openComposer);
            }

            document.addEventListener('atlas:new-collection', openComposer);

            if (collectionComposer) {
                var composerInput = collectionComposer.querySelector('input[name="name"]');
                var composerSubmitted = false;

                if (composerInput) {
                    composerInput.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            if (!composerSubmitted && composerInput.value.trim()) {
                                composerSubmitted = true;
                                submitForm(collectionComposer);
                            }
                        } else if (event.key === 'Escape') {
                            event.preventDefault();
                            collectionComposer.hidden = true;
                        }
                    });

                    composerInput.addEventListener('blur', function () {
                        setTimeout(function () {
                            if (!composerSubmitted && composerInput.value.trim()) {
                                composerSubmitted = true;
                                submitForm(collectionComposer);
                            }
                        }, 0);
                    });
                }
            }

            if (deleteCancel) {
                deleteCancel.addEventListener('click', closeDelete);
            }

            if (deleteConfirm && deleteForm) {
                deleteConfirm.addEventListener('click', function () {
                    deleteForm.action = deleteForm.dataset.action;
                    deleteForm.submit();
                });
            }

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeDelete();
                    }
                });
            }
        })();
        });
    </script>
    </aside>

    <form method="POST" data-delete-form hidden>
        @csrf
        @method('DELETE')
    </form>

    <div data-delete-modal hidden class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
        <div role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title" aria-describedby="delete-dialog-message"
            class="w-full max-w-sm rounded-2xl border border-edge bg-white p-5 shadow-[0_16px_40px_-16px_rgba(16,24,40,.3)] dark:border-slate-700 dark:bg-night">
            <h2 id="delete-dialog-title" class="text-sm font-semibold text-body dark:text-white">Hapus item?</h2>
            <p id="delete-dialog-message" data-delete-copy class="mt-2 text-xs/[1.55] text-subtle dark:text-slate-400"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-delete-cancel
                    class="h-8 rounded-lg border border-edge bg-white px-3 text-xs font-semibold text-body transition hover:bg-surface dark:border-slate-700 dark:bg-transparent dark:text-slate-200 dark:hover:bg-white/5">Batal</button>
                <button type="button" data-delete-confirm
                    class="h-8 rounded-lg bg-[#BA3D4A] px-3 text-xs font-semibold text-white transition hover:opacity-90">Hapus</button>
            </div>
        </div>
    </div>
</div>
