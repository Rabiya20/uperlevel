{{-- $expense (null on create), $vendors, $categories, $projects, $departments, $clients, $paymentAccounts, $settings --}}
@php
    $s = fn ($field, $default = '') => old($field, $expense->{$field} ?? $default);
    $existingLines = old('lines', $expense?->lines->map(fn ($l) => [
        'expense_category_id' => $l->expense_category_id, 'description' => $l->description,
        'client_id' => $l->client_id, 'project_id' => $l->project_id, 'department_id' => $l->department_id,
        'class_tag' => $l->class_tag, 'amount' => $l->amount,
    ])->all() ?? []);
@endphp

<div style="padding:20px;">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label class="f-label">Vendor / Payee</label>
            <select class="f-input" name="vendor_id">
                <option value="">None</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected($s('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Expense Date</label>
            <input class="f-input" type="date" name="expense_date" value="{{ $s('expense_date', now()->format('Y-m-d')) }}" required>
        </div>
        <div>
            <label class="f-label">Reference Number</label>
            <input class="f-input" type="text" name="reference_number" value="{{ $s('reference_number') }}" maxlength="100">
        </div>

        <div>
            <label class="f-label">Payment Account</label>
            <select class="f-input" name="payment_account_id" required>
                <option value="">Select…</option>
                @foreach ($paymentAccounts as $account)
                    <option value="{{ $account->id }}" @selected($s('payment_account_id') == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Payment Method</label>
            <select class="f-input" name="payment_method">
                <option value="">Select…</option>
                @foreach (\App\Models\Expense::PAYMENT_METHODS as $method)
                    <option value="{{ $method }}" @selected($s('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f-label">Payment Status</label>
            <select class="f-input" name="payment_status" required>
                @foreach (\App\Models\Expense::PAYMENT_STATUSES as $status)
                    <option value="{{ $status }}" @selected($s('payment_status', 'unpaid') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>

        <div style="grid-column:1 / -1;">
            <label class="f-label">Memo / Description</label>
            <textarea class="f-input" name="description" rows="2">{{ $s('description') }}</textarea>
        </div>

        <div>
            <label class="f-check">
                <input type="checkbox" name="is_recurring" value="1" id="isRecurring" @checked($s('is_recurring'))>
                Recurring expense
            </label>
        </div>
        <div id="recurrenceIntervalField" style="display:none;">
            <label class="f-label">Repeats</label>
            <select class="f-input" name="recurrence_interval">
                @foreach (\App\Models\Expense::RECURRENCE_INTERVALS as $interval)
                    <option value="{{ $interval }}" @selected($s('recurrence_interval', 'monthly') === $interval)>{{ ucfirst($interval) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="panel-head" style="padding:0 0 12px;"><h3 style="margin:0;">Line Items</h3></div>
    <div id="lineRows" style="display:flex;flex-direction:column;gap:10px;"></div>
    <button type="button" class="btn btn-ghost" id="addLineRow" style="margin-top:10px;padding:7px 12px;font-size:12.5px;">+ Add line</button>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:20px;max-width:640px;margin-left:auto;">
        <div>
            <label class="f-label">Tax</label>
            <input class="f-input" type="number" step="0.01" min="0" name="tax_amount" id="taxAmount" value="{{ $s('tax_amount', 0) }}">
        </div>
        <div>
            <label class="f-label">Discount</label>
            <input class="f-input" type="number" step="0.01" min="0" name="discount_amount" id="discountAmount" value="{{ $s('discount_amount', 0) }}">
        </div>
        <div>
            <label class="f-label">Total Amount</label>
            <div class="f-preview" id="totalPreview">0.00</div>
        </div>
    </div>

    <div class="panel-head" style="padding:20px 0 12px;"><h3 style="margin:0;">Attachments</h3></div>
    <input class="f-input" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png">
    <p class="f-hint">Upload receipts, invoices or scanned copies (PDF/Image).</p>
    @if ($expense && $expense->attachments->isNotEmpty())
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px;">
            @foreach ($expense->attachments as $attachment)
                <a href="{{ route('admin.finance.expenses.attachments.download', [$expense, $attachment]) }}" style="font-size:12.5px;color:var(--primary-dark);">📎 {{ $attachment->original_filename }}</a>
            @endforeach
        </div>
    @endif

    @if ($expense)
        <div style="margin-top:20px;">
            <label class="f-label">Reason for Edit</label>
            <input class="f-input" type="text" name="edit_reason" value="{{ old('edit_reason') }}" placeholder="Why is this expense being changed?" required>
        </div>
    @endif

    <div style="margin-top:20px;display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">{{ $expense ? 'Save Changes' : 'Save as Draft' }}</button>
        <a href="{{ $expense ? route('admin.finance.expenses.show', $expense) : route('admin.finance.expenses.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
</div>

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
    .f-hint{font-size:11px;color:var(--ink-soft);margin:6px 0 0;}
    .f-check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;color:var(--ink);}
    .f-preview{padding:9px 11px;border-radius:8px;background:var(--primary-soft);color:var(--primary-dark);font-weight:700;font-size:13.5px;}
    .line-row{display:grid;grid-template-columns:1.4fr 1.4fr 1fr 1fr 1fr 0.8fr 0.9fr auto;gap:8px;align-items:end;border:1px solid var(--line);border-radius:8px;padding:10px;background:var(--bg);}
</style>

<script>
    const CATEGORIES = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
    const CLIENTS = @json($clients->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
    const PROJECTS = @json($projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]));
    const DEPARTMENTS = @json($departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]));
    const EXISTING_LINES = @json($existingLines);

    function opts(list, selected) {
        return '<option value="">—</option>' + list.map(i => `<option value="${i.id}" ${selected == i.id ? 'selected' : ''}>${i.name}</option>`).join('');
    }

    const lineRows = document.getElementById('lineRows');
    let lineIndex = 0;

    function recalcTotal() {
        let subtotal = 0;
        document.querySelectorAll('input[name$="[amount]"]').forEach(i => subtotal += parseFloat(i.value) || 0);
        const tax = parseFloat(document.getElementById('taxAmount').value) || 0;
        const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
        document.getElementById('totalPreview').textContent = (subtotal + tax - discount).toFixed(2);
    }

    function addLineRow(data) {
        data = data || {};
        const i = lineIndex++;
        const row = document.createElement('div');
        row.className = 'line-row';
        row.innerHTML = `
            <div><label class="f-label">Category</label><select class="f-input" name="lines[${i}][expense_category_id]" required>${opts(CATEGORIES, data.expense_category_id)}</select></div>
            <div><label class="f-label">Description</label><input class="f-input" type="text" name="lines[${i}][description]" value="${data.description || ''}"></div>
            <div><label class="f-label">Customer</label><select class="f-input" name="lines[${i}][client_id]">${opts(CLIENTS, data.client_id)}</select></div>
            <div><label class="f-label">Project</label><select class="f-input" name="lines[${i}][project_id]">${opts(PROJECTS, data.project_id)}</select></div>
            <div><label class="f-label">Department</label><select class="f-input" name="lines[${i}][department_id]">${opts(DEPARTMENTS, data.department_id)}</select></div>
            <div><label class="f-label">Class/Tag</label><input class="f-input" type="text" name="lines[${i}][class_tag]" value="${data.class_tag || ''}"></div>
            <div><label class="f-label">Amount</label><input class="f-input" type="number" step="0.01" min="0.01" name="lines[${i}][amount]" value="${data.amount || ''}" required></div>
            <button type="button" class="btn btn-ghost" style="padding:9px 12px;">✕</button>
        `;
        row.querySelectorAll('input').forEach(input => input.addEventListener('input', recalcTotal));
        row.querySelector('button').addEventListener('click', () => { row.remove(); recalcTotal(); });
        lineRows.appendChild(row);
        recalcTotal();
    }

    document.getElementById('addLineRow').addEventListener('click', () => addLineRow());
    document.getElementById('taxAmount').addEventListener('input', recalcTotal);
    document.getElementById('discountAmount').addEventListener('input', recalcTotal);

    if (EXISTING_LINES.length) {
        EXISTING_LINES.forEach(line => addLineRow(line));
    } else {
        addLineRow();
    }

    const isRecurring = document.getElementById('isRecurring');
    const recurrenceField = document.getElementById('recurrenceIntervalField');
    function syncRecurrence() { recurrenceField.style.display = isRecurring.checked ? 'block' : 'none'; }
    isRecurring.addEventListener('change', syncRecurrence);
    syncRecurrence();
</script>
