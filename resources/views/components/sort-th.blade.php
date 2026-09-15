@props([
    'field',
    'label',
    'currentSort' => request('sort'),
    'currentDirection' => request('direction'),
    'defaultDirection' => 'asc',
    'align' => 'left',
])

@php
    $isActive = ($currentSort === $field);
    $direction = strtolower($currentDirection ?: $defaultDirection);
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = $defaultDirection;
    }
    
    // Invert direction if already active; otherwise start with defaultDirection
    $nextDirection = $isActive ? ($direction === 'asc' ? 'desc' : 'asc') : $defaultDirection;

    // Preserve all existing query parameters, change sort & direction, and reset to page 1
    $url = request()->fullUrlWithQuery([
        'sort' => $field,
        'direction' => $nextDirection,
        'page' => 1,
    ]);

    $alignmentClass = match($align) {
        'right' => 'text-right justify-end',
        'center' => 'text-center justify-center',
        default => 'text-left justify-start',
    };
@endphp

<th {{ $attributes->merge(['class' => 'py-3.5 px-4']) }}
    @if($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @else aria-sort="none" @endif>
    <a href="{{ $url }}" 
       class="group inline-flex items-center space-x-1.5 transition-colors cursor-pointer select-none {{ $alignmentClass }} {{ $isActive ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white' }}"
       title="Urutkan berdasarkan {{ $label }} ({{ $isActive ? ($direction === 'asc' ? 'Saat ini: A ke Z / Terlama. Klik untuk membalik urutan' : 'Saat ini: Z ke A / Terbaru. Klik untuk membalik urutan') : 'Klik untuk mengurutkan' }})">
        <span>{{ $label }}</span>
        <span class="inline-flex items-center">
            @if($isActive)
                @if($direction === 'asc')
                    <i class="fa-solid fa-arrow-up-short-wide text-indigo-600 dark:text-indigo-400 text-xs"></i>
                @else
                    <i class="fa-solid fa-arrow-down-wide-short text-indigo-600 dark:text-indigo-400 text-xs"></i>
                @endif
            @else
                <i class="fa-solid fa-sort text-slate-300 dark:text-gray-600 text-[10px] opacity-60 group-hover:opacity-100 group-hover:text-indigo-500 transition"></i>
            @endif
        </span>
    </a>
</th>
