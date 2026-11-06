@props(['name' => 'leaf'])
@php
    $paths = [
        'leaf' => 'M20 4C12 2 4 5 4 12a7 7 0 0 0 7 7c7 0 9-8 9-15ZM4 21 15 10M7 17v-5m4 1h5',
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'report' => 'M7 3h10l4 4v14H3V3h4ZM7 8h5M7 12h10M7 16h7',
        'users' =>
            'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7a4 4 0 0 1 0 8',
        'help' => 'M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4m0 3h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'plus' => 'M12 5v14M5 12h14',
        'arrow' => 'M5 12h14m-5-5 5 5-5 5',
        'back' => 'M19 12H5m5-5-5 5 5 5',
        'search' => 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'clock' => 'M12 8v4l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'check' => 'm8 12 3 3 5-6M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'truck' => 'M1 3h14v13H1zM15 8h4l3 4v4h-7M7 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm14 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z',
        'pin' => 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0ZM15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        'upload' => 'M12 16V3m-5 5 5-5 5 5M3 16v5h18v-5',
        'bottle' => 'M9 3h6v4l3 4v9a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-9l3-4V3ZM9 6h6M6 13h12M6 17h12',
        'image' => 'M3 3h18v18H3zM3 17l6-6 4 4 3-3 5 5M16 7h.01',
        'logout' => 'M9 21H3V3h6m6 5 4 4-4 4m-8-4h12',
        'menu' => 'M3 6h18M3 12h18M3 18h18',
        'close' => 'm6 6 12 12M6 18 18 6',
        'edit' => 'm16 3 5 5-12 12-6 1 1-6L16 3ZM14 5l5 5',
        'trash' => 'M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7',
        'calendar' => 'M3 5h18v16H3zM16 3v4M8 3v4M3 11h18',
        'scan' => 'M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M7 12h10',
        'chevron' => 'm9 5 7 7-7 7',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'icon']) }} width="20" height="20" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['leaf'] }}" />
</svg>
