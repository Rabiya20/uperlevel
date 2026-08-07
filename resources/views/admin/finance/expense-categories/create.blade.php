@extends('layouts.admin')

@section('title', 'Add Expense Category — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Expense Category</h2>
        <p><a href="{{ route('admin.finance.expense-categories.index') }}" style="color:var(--primary-dark);">← Back to Categories</a></p>
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('admin.finance.expense-categories.store') }}">
        @csrf
        @php $category = null; @endphp
        @include('admin.finance.expense-categories._form', ['category' => $category, 'accounts' => $accounts])
    </form>
</div>
@endsection
