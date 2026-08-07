@php
    $s = fn ($field, $default = '') => old($field, $leaveType->{$field} ?? $default);
@endphp

<div class="grid-2" style="align-items:start;">
    <div>
        <div class="panel">
            <div class="panel-head"><h3>Leave Type Details</h3></div>
            <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="f-label">Name</label>
                    <input class="f-input" type="text" name="name" value="{{ $s('name') }}" maxlength="60" placeholder="e.g. Annual, Sick, Casual" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label class="f-label">Days per year</label>
                        <input class="f-input" type="number" min="0" max="365" name="days_per_year" value="{{ $s('days_per_year', 0) }}" required>
                    </div>
                    <div>
                        <label class="f-label">Max per month</label>
                        <input class="f-input" type="number" min="1" max="31" name="max_per_month" value="{{ $s('max_per_month') }}" placeholder="No cap">
                        <p class="f-hint">Most days of this type takeable within one calendar month.</p>
                    </div>
                </div>

                <div style="border-top:1px solid var(--line);margin-top:4px;padding-top:14px;display:flex;flex-direction:column;gap:14px;">
                    <label class="f-check">
                        <input type="hidden" name="carry_forward" value="0">
                        <input type="checkbox" name="carry_forward" value="1" @checked($s('carry_forward', false))>
                        Carry forward unused days to next year
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label class="f-label">Max days carried forward</label>
                            <input class="f-input" type="number" min="0" max="365" name="max_carry_forward_days" value="{{ $s('max_carry_forward_days') }}" placeholder="Unlimited">
                            <p class="f-hint">Caps how much of the unused balance rolls into next year.</p>
                        </div>
                        <div>
                            <label class="f-label">Max total accumulation</label>
                            <input class="f-input" type="number" min="0" max="1000" name="max_accumulation_days" value="{{ $s('max_accumulation_days') }}" placeholder="Unlimited">
                            <p class="f-hint">Ceiling on the total balance an employee can bank at once — for future use or FNF payout.</p>
                        </div>
                    </div>
                </div>

                <div style="border-top:1px solid var(--line);margin-top:4px;padding-top:14px;display:flex;gap:20px;">
                    <label class="f-check">
                        <input type="hidden" name="is_encashable" value="0">
                        <input type="checkbox" name="is_encashable" value="1" @checked($s('is_encashable', false))>
                        Encashable at Full & Final settlement
                    </label>
                    <label class="f-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($s('is_active', true))>
                        Active
                    </label>
                </div>
                <p class="f-hint" style="margin:0;">When encashable, an employee's unused balance of this type is expected to be paid out in cash when they leave, instead of forfeited.</p>
            </div>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
            <button type="submit" class="btn btn-primary">Save Leave Type</button>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h3>Already Added Leave Types</h3></div>
        @if ($existingTypes->isEmpty())
            <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13px;">None yet — this will be the first.</div>
        @else
            <table>
                <tr><th>Name</th><th>Days/Yr</th><th>Monthly Cap</th><th>Carry Fwd</th></tr>
                @foreach ($existingTypes as $existing)
                    <tr>
                        <td><strong>{{ $existing->name }}</strong></td>
                        <td>{{ $existing->days_per_year }}</td>
                        <td>{{ $existing->max_per_month ?? '—' }}</td>
                        <td>{{ $existing->carry_forward ? 'Yes'.($existing->max_carry_forward_days ? ' (max '.$existing->max_carry_forward_days.')' : '') : 'No' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-input:focus{outline:none;border-color:var(--primary);background:#fff;}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
</style>
