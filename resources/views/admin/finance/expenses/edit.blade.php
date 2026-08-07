@extends('layouts.admin')

@section('title', 'Edit '.$expense->expense_number.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit {{ $expense->expense_number }}</h2>
        <p><a href="{{ route('admin.finance.expenses.show', $expense) }}" style="color:var(--primary-dark);">← Back to Expense</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="panel" style="padding:14px 20px;margin-bottom:18px;background:#FDEEEC;border-color:#FDEEEC;">
        @foreach ($errors->all() as $error)
            <div style="color:#C0392B;font-weight:600;font-size:13px;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="panel">
    <form method="POST" action="{{ route('admin.finance.expenses.update', $expense) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.finance.expenses._form')
    </form>
</div>
@endsection
