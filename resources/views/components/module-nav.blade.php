@props(['modules', 'type' => 'sidebar'])

@php
    // Small inline icon set keyed by the `icon` column — keeps modules
    // fully data-driven without needing an icon library dependency.
    $icons = [
        'grid' => 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z',
        'building' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1',
        'building-community' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1',
        'cash' => 'M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6',
        'users' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M13 7a4 4 0 11-8 0 4 4 0 018 0zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
        'layout-kanban' => 'M4 4h16v16H4zM9 8v8M15 8v5',
        'target' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 16a4 4 0 100-8 4 4 0 000 8z',
        'message-circle' => 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z',
        'file-invoice' => 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h4',
        'apps' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        'headset' => 'M21 11.5a8.4 8.4 0 00-.9-3.8A8.5 8.5 0 0012.5 3a8.4 8.4 0 00-3.8.9L3 21l1.9-5.7',
        'file-text' => 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z',
        'settings' => 'M12 15a3 3 0 100-6 3 3 0 000 6z',
        'calendar-stats' => 'M4 5h16v16H4zM4 9h16M9 13v4M13 15v2M17 12v5',
        'checklist' => 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11',
        'beach' => 'M2 22h20M17 15.5A6.5 6.5 0 0010.5 9M4 22c0-6 4-11 9-13',
        'speakerphone' => 'M4 4h16v14H4zM8 8h8M8 12h5',
        'calendar' => 'M3 4h18v16H3zM3 9h18M8 2v4M16 2v4',
        'alert-circle' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 8v5M12 16h.01',
    ];
    $iconPath = fn ($key) => $icons[$key] ?? $icons['grid'];

    // A module is "active" if the current route is its own page, or one
    // of its children's — not just whichever module happens to be first.
    // Pages reached via a child (e.g. Shift management, linked from HR's
    // Setup) share that child's route namespace ("admin.hr.*") even though
    // they have no nav entry of their own, so match on the shared
    // "portal.module" prefix too, not just an exact route name.
    $currentRoute = optional(request()->route())->getName();
    $routeGroup = fn (?string $route) => $route ? implode('.', array_slice(explode('.', $route), 0, 2)) : null;
    $currentGroup = $routeGroup($currentRoute);
    $isModuleActive = fn ($module) => $module->route_name === $currentRoute
        || ($currentGroup && $routeGroup($module->route_name) === $currentGroup)
        || $module->children->contains(fn ($child) => $child->route_name === $currentRoute
            || ($currentGroup && $routeGroup($child->route_name) === $currentGroup));
@endphp

@if ($type === 'sidebar')
    @foreach ($modules as $module)
        <div class="nav-item {{ $isModuleActive($module) ? 'active' : '' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="{{ $iconPath($module->icon) }}"/>
            </svg>
            <a href="{{ $module->route_name ? route($module->route_name) : '#' }}" style="color:inherit;">{{ $module->name }}</a>
        </div>
        @if ($module->children->isNotEmpty())
            <div class="nav-sub">
                @foreach ($module->children as $child)
                    <div class="nav-item {{ \App\Models\Module::isChildActive($child, $currentRoute) ? 'active' : '' }}">
                        <a href="{{ $child->route_name ? route($child->route_name) : '#' }}" style="color:inherit;">{{ $child->name }}</a>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

@else
    {{-- header type: just the top-row main-nav buttons. The matching
         contextual submodule bar (header-2) is rendered separately by
         partials/admin-subnav.blade.php so it can sit outside the
         flex header bar in the layout. --}}
    <nav class="main-nav" id="mainNav">
        @foreach ($modules as $module)
            <div class="nav-btn {{ $isModuleActive($module) ? 'active' : '' }}" data-mod="{{ $module->key }}">
                <a href="{{ $module->route_name ? route($module->route_name) : '#' }}" style="color:inherit;display:flex;align-items:center;gap:6px;" onclick="{{ $module->children->isNotEmpty() ? 'event.preventDefault();' : '' }}">
                    {{ $module->name }}
                    @if ($module->children->isNotEmpty())
                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
                    @endif
                </a>
            </div>
        @endforeach
    </nav>
@endif
