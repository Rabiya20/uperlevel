@extends('layouts.admin')

@section('title', 'Edit Account — Chart of Accounts — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit Account</h2>
        <p><a href="{{ route('admin.finance.coa.index') }}" style="color:var(--primary-dark);">← Back to Chart of Accounts</a></p>
    </div>
</div>

<form method="POST" action="{{ route('admin.finance.coa.update', $account) }}">
    @csrf
    @method('PUT')
    @include('admin.finance.coa._form', ['account' => $account, 'parents' => $parents])
</form>
@endsection
