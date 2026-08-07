{{-- $modules: Collection<{top: Module, rows: Collection<Module>}>, $permissions: RolePermission[] keyed by module_id --}}
<table class="perm-matrix">
    <tr><th style="width:40%;">Module</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th></tr>
    @foreach ($modules as $group)
        @php
            $topId = $group['top']->id;
            $rows = $group['rows'];
            $isSingleRow = $rows->count() === 1 && $rows->first()->id === $topId;
        @endphp

        @unless ($isSingleRow)
            <tr class="perm-group-head" data-group-head="{{ $topId }}">
                <td colspan="5">
                    <label class="perm-check-all">
                        <input type="checkbox" onchange="togglePermGroup({{ $topId }}, this.checked)">
                        {{ $group['top']->name }}
                    </label>
                </td>
            </tr>
        @endunless

        @foreach ($rows as $m)
            @php
                $perm = $permissions[$m->id] ?? null;
                $allowed = \App\Support\Permissions\PermissionMatrix::levelsFor($m->key);
            @endphp
            <tr data-group="{{ $topId }}">
                <td style="{{ $isSingleRow ? '' : 'padding-left:32px;' }}">
                    @if ($isSingleRow)
                        <label class="perm-check-all">
                            <input type="checkbox" onchange="togglePermGroup({{ $topId }}, this.checked)">
                            {{ $m->name }}
                        </label>
                    @else
                        {{ $m->name }}
                    @endif
                </td>
                @foreach (['view', 'create', 'edit', 'delete'] as $level)
                    @php $enabled = in_array($level, $allowed, true); @endphp
                    <td style="text-align:center;">
                        <input type="hidden" name="permissions[{{ $m->id }}][{{ $level }}]" value="0">
                        <input
                            type="checkbox"
                            name="permissions[{{ $m->id }}][{{ $level }}]"
                            value="1"
                            data-module-id="{{ $m->id }}"
                            data-group="{{ $topId }}"
                            @checked(old("permissions.{$m->id}.{$level}", $perm?->{"can_{$level}"}))
                            @disabled(! $enabled)
                            title="{{ $enabled ? '' : 'No '.$level.' action exists for this module' }}"
                        >
                    </td>
                @endforeach
            </tr>
        @endforeach
    @endforeach
</table>

<style>
    .perm-matrix .perm-group-head td{background:var(--bg);border-top:1px solid var(--line);}
    .perm-check-all{display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer;}
    .perm-matrix input[type="checkbox"]:disabled{opacity:.3;cursor:not-allowed;}
</style>

<script>
    function togglePermGroup(groupId, checked) {
        document.querySelectorAll('input[type="checkbox"][data-group="' + groupId + '"]:not(:disabled)').forEach(function (box) {
            box.checked = checked;
        });
    }
</script>
