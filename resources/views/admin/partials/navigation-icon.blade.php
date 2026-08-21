@php
    $paths = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m13-12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm2 6.2a4 4 0 0 1 3 3.8V20"/><circle cx="9.5" cy="6.5" r="3.5" stroke-width="1.8"/>',
        'products' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/>',
        'orders' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h12l1 4H5l1-4Zm-1 4h14v14H5V7Zm4 4h6m-6 4h4"/>',
        'payments' => '<rect x="3" y="5" width="18" height="14" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M3 10h18M7 15h3"/>',
        'enrollments' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3 9 5-9 5-9-5 9-5Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 11 7 4 7-4M5 15l7 4 7-4"/>',
        'leads' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v11H8l-4 4V5Z"/><path stroke-linecap="round" stroke-width="1.8" d="M8 9h8m-8 3h5"/>',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.06.06-2.1 2.1-.06-.06a1.8 1.8 0 0 0-1.98-.36 1.8 1.8 0 0 0-1.1 1.65v.13h-3v-.13a1.8 1.8 0 0 0-1.1-1.65 1.8 1.8 0 0 0-1.98.36l-.06.06-2.1-2.1.06-.06A1.8 1.8 0 0 0 6.8 15a1.8 1.8 0 0 0-1.65-1.1H5v-3h.15A1.8 1.8 0 0 0 6.8 9.8a1.8 1.8 0 0 0-.36-1.98l-.06-.06 2.1-2.1.06.06a1.8 1.8 0 0 0 1.98.36 1.8 1.8 0 0 0 1.1-1.65V4.3h3v.13a1.8 1.8 0 0 0 1.1 1.65 1.8 1.8 0 0 0 1.98-.36l.06-.06 2.1 2.1-.06.06a1.8 1.8 0 0 0-.36 1.98 1.8 1.8 0 0 0 1.65 1.1h.13v3h-.13A1.8 1.8 0 0 0 19.4 15Z"/>',
        'analytics' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V10m5 10V4m5 16v-7m5 7V7"/>',
        'seo' => '<circle cx="10.5" cy="10.5" r="6.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4"/>',
        'website' => '<circle cx="12" cy="12" r="8.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M3.8 12h16.4M12 3.5c2.1 2.3 3.2 5.1 3.2 8.5S14.1 18.2 12 20.5C9.9 18.2 8.8 15.4 8.8 12S9.9 5.8 12 3.5Z"/>',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 5H5v14h5m4-10 4 3-4 3m4-3H9"/>',
    ];
@endphp
<svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">{!! $paths[$icon] ?? $paths['dashboard'] !!}</svg>
