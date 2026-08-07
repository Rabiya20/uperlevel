@extends('layouts.admin')

@section('title', 'Edit '.$category->name.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit {{ $category->name }}</h2>
        <p><a href="{{ route('admin.finance.expense-categories.index') }}" style="color:var(--primary-dark);">← Back to Categories</a></p>
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('admin.finance.expense-categories.update', $category) }}">
        @csrf
        @method('PUT')
        @include('admin.finance.expense-categories._form', ['category' => $category, 'accounts' => $accounts])
    </form>
</div>
@endsection
