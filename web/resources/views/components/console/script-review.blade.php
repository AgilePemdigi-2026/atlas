@props(['testCase'])

@php
    $script = $testCase->generatedScript;
@endphp

@if ($script)
    @php
        $filename = \App\Http\Controllers\ConsoleController::scriptFilename($testCase);
        $lines = preg_split('/\R/', rtrim($script->content));
        $tones = [
            'completed' => ['Skrip berhasil dibuat', '✓', 'border-[#D4EEDB] bg-[#EDF9F0] text-[#248940] dark:border-green-800 dark:bg-green-900/30 dark:text-green-300'],
            'partial' => ['Skrip dibuat sebagian', '!', 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
            'failed' => ['Skrip gagal dibuat', '✕', 'border-red-200 bg-red-50 text-[#BA3D4A] dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'],
        ];
        [$title, $icon, $tone] = $tones[$script->status->value];
    @endphp

    <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3 rounded-[10px] border px-3.5 py-3 {{ $tone }}">
            <span class="grid size-[22px] flex-none place-items-center rounded-full border border-current text-sm" aria-hidden="true">{{ $icon }}</span>
            <div class="flex flex-col gap-0.5">
                <span class="text-[11px] font-bold">{{ $title }}</span>
                <span class="text-[11px] opacity-80">{{ $testCase->scenarios_count }} skenario · {{ $testCase->name }}</span>
                @if ($script->unresolved_step)
                    <span class="text-[11px] opacity-80">Langkah belum terselesaikan: {{ $script->unresolved_step }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-col gap-1">
                <span class="text-[13px] font-bold text-body dark:text-slate-100">{{ $filename }}</span>
                <span class="text-[11px] text-subtle dark:text-slate-400">Python 3 · Selenium 4 · Review sebelum dijalankan</span>
            </div>
            <a href="{{ route('test-cases.script.download', $testCase) }}"
                class="inline-flex h-9 items-center rounded-[7px] bg-accent px-4 text-[11px] font-semibold text-white hover:opacity-90">Download Script</a>
        </div>

        <div class="overflow-hidden rounded-[11px] border border-[#2D3449] bg-[#161B2E] shadow-lg">
            <div class="flex items-center gap-3 border-b border-[#30374B] bg-[#1D2338] px-4 py-3">
                <span class="flex gap-1.5" aria-hidden="true">
                    <i class="size-1.5 rounded-full bg-[#EF6C62]"></i>
                    <i class="size-1.5 rounded-full bg-[#E9BD55]"></i>
                    <i class="size-1.5 rounded-full bg-[#54C76E]"></i>
                </span>
                <span class="font-mono text-[11px] text-[#8B93A7]">{{ $filename }}</span>
                <span class="ml-auto font-mono text-[11px] text-[#8B93A7]">Python · Selenium</span>
            </div>

            <div class="max-h-[450px] overflow-auto py-5 font-mono text-xs/[1.8]">
                @foreach ($lines as $line)
                    @php $isKeyword = preg_match('/^(from|import|def|class)\b/', $line) === 1; @endphp
                    <div class="flex min-w-max">
                        <span class="w-[52px] flex-none pr-4 text-right text-[#555F76] select-none">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span @class(['pr-6 whitespace-pre', 'text-[#AEB1FF]' => $isKeyword, 'text-[#BDC6D8]' => ! $isKeyword])>{{ $line }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <p class="text-sm text-subtle dark:text-slate-400">Belum ada hasil test case.</p>
@endif