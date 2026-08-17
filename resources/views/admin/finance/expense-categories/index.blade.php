@extends('layouts.admin')

@section('title', 'Expense Categories — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Expense Categories</h2>
        <p><a href="{{ route('admin.finance.expenses.index') }}" style="color:var(--primary-dark);">← Back to Expenses</a></p>
    </div>
    <a href="{{ route('admin.finance.expense-categories.create') }}" class="btn btn-primary">+ Add Category</a>
</div>

@if (session('status'))
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:var(--success-soft);border-color:var(--success-soft);">
        <span style="color:#0F7C50;font-weight:700;font-size:13px;">✓ {{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="panel">
    @if ($categories->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No categories yet.</div>
    @else
        <table>
            <tr><th>Name</th><th>Posts To</th><th>Expenses</th><th>Status</th><th></th></tr>
            @foreach ($categories as $category)
                <tr>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td>
                        @if ($category->chartOfAccount)
                            <a href="{{ route('admin.finance.ledger.accounts.show', $category->chartOfAccount) }}" style="color:var(--primary-dark);">{{ $category->chartOfAccount->code }} — {{ $category->chartOfAccount->name }}</a>
                        @else
                            <span style="color:#C0392B;">Not linked — edit this category to fix</span>
                        @endif
                    </td>
                    <td>{{ $category->expenses_count }}</td>
                    <td>
                        @if ($category->is_active)
                            <span class="badge-pill pill-active"><span class="mini-dot"></span>Active</span>
                        @else
                            <span class="badge-pill pill-suspended"><span class="mini-dot"></span>Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="{{ route('admin.finance.expense-categories.edit', $category) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <form method="POST" action="{{ route('admin.finance.expense-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;color:#c0392b;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
@endsection
