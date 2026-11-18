@props(['name', 'size' => 'w-9 h-9 text-sm'])
@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $palette = ['bg-brand-500/15 text-brand-300', 'bg-brand-600/15 text-brand-300', 'bg-brand-700/20 text-brand-200', 'bg-purple/15 text-purple', 'bg-violet/15 text-violet', 'bg-orange/15 text-orange'];
    $color = $palette[crc32($name) % count($palette)];
@endphp
<span {{ $attributes->merge(['class' => "inline-grid place-items-center rounded-full font-semibold shrink-0 $size $color"]) }}>{{ $initials ?: '?' }}</span>
