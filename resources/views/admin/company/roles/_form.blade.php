@php
    $s = fn ($field, $default = '') => old($field, $role->{$field} ?? $default);
@endphp

<div class="panel" style="max-width:480px;margin-bottom:18px;">
    <div class="panel-head"><h3>Role Details</h3></div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
        <div>
            <label class="f-label">Name</label>
            <input class="f-input" type="text" name="name" id="roleName" value="{{ $s('name') }}" maxlength="80" placeholder="e.g. Sales Rep, Front Desk" required>
        </div>
        <div>
            <label class="f-label">Portal</label>
            <select class="f-input" name="portal" id="rolePortal" required>
                <option value="admin" @selected($s('portal', 'admin') === 'admin')>Admin Portal (owner / admin / manager)</option>
                <option value="employee" @selected($s('portal') === 'employee')>Employee Portal</option>
            </select>
            <p class="f-hint">Determines which set of modules below this role's permissions apply to — and which users it can be assigned to.</p>
        </div>
        <label class="f-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
            Active
        </label>
        @if ($copyTemplates->isNotEmpty())
            <div>
                <label class="f-label">Copy permissions from</label>
                <select class="f-input" id="copyFromRole">
                    <option value="">— Start from scratch —</option>
                    @foreach ($copyTemplates as $template)
                        <option value="{{ $template['id'] }}">{{ $template['name'] }} ({{ $template['portal'] === 'admin' ? 'Admin' : 'Employee' }} Portal)</option>
                    @endforeach
                </select>
                <p class="f-hint">Pre-fills the matrix below with another role's saved permissions — you can still adjust anything before saving.</p>
            </div>
        @endif
    </div>
</div>

<div class="panel" id="adminPermissionsPanel">
    <div class="panel-head"><h3>Admin Portal Permissions</h3></div>
    @include('admin.company.roles._permission-matrix', ['modules' => $adminModules, 'permissions' => $permissions])
</div>

<div class="panel" id="employeePermissionsPanel" style="margin-top:0;">
    <div class="panel-head"><h3>Employee Portal Permissions</h3></div>
    @include('admin.company.roles._permission-matrix', ['modules' => $employeeModules, 'permissions' => $permissions])
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
    <button type="submit" class="btn btn-primary">Save Role</button>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>

<script>
    const rolePortal = document.getElementById('rolePortal');
    const adminPanel = document.getElementById('adminPermissionsPanel');
    const employeePanel = document.getElementById('employeePermissionsPanel');

    function syncPermissionPanels() {
        const isAdmin = rolePortal.value === 'admin';
        adminPanel.style.display = isAdmin ? 'block' : 'none';
        employeePanel.style.display = isAdmin ? 'none' : 'block';
    }

    rolePortal.addEventListener('change', syncPermissionPanels);
    syncPermissionPanels();

    const copyTemplates = @json($copyTemplates->keyBy('id'));
    const copyFromRole = document.getElementById('copyFromRole');
    const roleNameInput = document.getElementById('roleName');

    if (copyFromRole) {
        copyFromRole.addEventListener('change', function () {
            const template = copyTemplates[this.value];

            // Clear every checkbox first — copying always replaces the
            // current selection rather than merging into it.
            document.querySelectorAll('.perm-matrix input[type="checkbox"][data-module-id]').forEach(function (box) {
                box.checked = false;
            });

            if (! template) {
                return;
            }

            rolePortal.value = template.portal;
            syncPermissionPanels();

            Object.keys(template.permissions).forEach(function (moduleId) {
                const levels = template.permissions[moduleId];
                ['view', 'create', 'edit', 'delete'].forEach(function (level) {
                    if (! levels[level]) {
                        return;
                    }
                    const box = document.querySelector('.perm-matrix input[type="checkbox"][data-module-id="' + moduleId + '"][name="permissions[' + moduleId + '][' + level + ']"]');
                    if (box && ! box.disabled) {
                        box.checked = true;
                    }
                });
            });

            if (! roleNameInput.value.trim()) {
                roleNameInput.value = 'Copy of ' + template.name;
            }
        });
    }
</script>
