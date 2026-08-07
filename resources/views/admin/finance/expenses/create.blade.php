@extends('layouts.admin')

@section('title', 'Record Expense — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Record Expense</h2>
        <p><a href="{{ route('admin.finance.expenses.index') }}" style="color:var(--primary-dark);">← Back to Expenses</a> — will be issued as <strong>{{ $nextNumber }}</strong>.</p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($categories->isEmpty())
    <div class="panel" style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No expense categories yet. <a href="{{ route('admin.finance.expense-categories.create') }}" style="color:var(--primary-dark);">Add one →</a>
    </div>
@elseif ($paymentAccounts->isEmpty())
    <div class="panel" style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">
        No asset (Cash/Bank) accounts yet. <a href="{{ route('admin.finance.coa.create') }}" style="color:var(--primary-dark);">Add one to your Chart of Accounts →</a>
    </div>
@else
    <div class="panel">
        <form method="POST" action="{{ route('admin.finance.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            @php $expense = null; @endphp
            @include('admin.finance.expenses._form')
        </form>
    </div>
@endif
@endsection
