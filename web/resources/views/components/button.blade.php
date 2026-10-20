@props(['variant' => 'primary', 'href' => '#'])

<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex h-11 items-center justify-center rounded-md border text-sm font-semibold',
    'w-[190px] border-transparent bg-brand text-white' => $variant === 'primary',
    'w-[180px] border-line bg-white text-[#236913] dark:border-slate-600 dark:bg-transparent dark:text-brand' => $variant === 'secondary',
    'w-full border-transparent bg-accent text-white' => $variant === 'accent',
    'w-[180px] border-transparent bg-accent text-white' => $variant === 'cta-primary',
    'w-[180px] border-transparent bg-white text-accent' => $variant === 'cta-secondary',
]) }}>{{ $slot }}</a>
