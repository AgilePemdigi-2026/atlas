@props(['status'])

<span @class([
    'inline-flex items-center gap-1.5 self-start rounded px-2 py-1 text-[11px] font-medium',
    'bg-[#EDF8F2] text-[#23765B] dark:bg-emerald-900/30 dark:text-emerald-300' => $status === 'completed',
    'bg-[#FFF1F2] text-[#BA3D4A] dark:bg-red-900/30 dark:text-red-300' => $status === 'failed',
    'bg-surface text-subtle dark:bg-slate-800 dark:text-slate-400' => ! in_array($status, ['completed', 'failed'], true),
])>
    <span class="size-[5px] rounded-full bg-current"></span>
    {{ $status }}
</span>