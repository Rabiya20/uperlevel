@php
    // Some configuration screens are reached via a child page rather than
    // having their own nav entry (e.g. Shift and Leave Type management,
    // both linked from HR's Setup), and a few live under a route namespace
    // that doesn't match their "real" submodule (e.g. leads-import screens
    // under "admin.crm.leads.*" but conceptually part of "Imports") — both
    // cases are resolved centrally by Module::isChildActive() so the header
    // subnav and sidebar layouts always agree.
    $currentRoute = optional(request()->route())->getName();
    $isChildActive = fn ($child) => \App\Models\Module::isChildActive($child, $currentRoute);
@endphp

<div class="header-2" id="subNav" style="display:none;"></div>

<script>
    const subnavData = {
        @foreach ($modules as $module)
            "{{ $module->key }}": {
                items: [
                    @foreach ($module->children as $child)
                        { label: @json($child->name), href: "{{ $child->route_name ? route($child->route_name) : '#' }}", active: {{ $isChildActive($child) ? 'true' : 'false' }} },
                    @endforeach
                ]
            },
        @endforeach
    };

    const navBtns = document.querySelectorAll('#mainNav .nav-btn');
    const subNav = document.getElementById('subNav');

    function renderSubnav(key) {
        navBtns.forEach(b => b.classList.toggle('active', b.dataset.mod === key));
        const data = subnavData[key];
        subNav.innerHTML = '';
        if (!data || data.items.length === 0) {
            subNav.style.display = 'none';
            return;
        }
        subNav.style.display = 'flex';
        data.items.forEach((item) => {
            const el = document.createElement('a');
            el.href = item.href;
            el.className = 'subnav-btn' + (item.active ? ' active' : '');
            el.textContent = item.label;
            el.onclick = () => {
                subNav.querySelectorAll('.subnav-btn').forEach(x => x.classList.remove('active'));
                el.classList.add('active');
            };
            subNav.appendChild(el);
        });
    }

    navBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (subnavData[btn.dataset.mod] && subnavData[btn.dataset.mod].items.length) {
                renderSubnav(btn.dataset.mod);
            }
        });
    });

    // Open whichever module the server already marked active (based on
    // the current route) instead of always defaulting to the first one.
    const activeBtn = document.querySelector('#mainNav .nav-btn.active') || navBtns[0];
    if (activeBtn) renderSubnav(activeBtn.dataset.mod);
</script>
