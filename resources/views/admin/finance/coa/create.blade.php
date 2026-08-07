@extends('layouts.admin')

@section('title', 'Add Account — Chart of Accounts — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Add Account</h2>
        <p><a href="{{ route('admin.finance.coa.index') }}" style="color:var(--primary-dark);">← Back to Chart of Accounts</a></p>
    </div>
</div>

<form method="POST" action="{{ route('admin.finance.coa.store') }}">
    @csrf
    @include('admin.finance.coa._form', ['account' => null, 'parents' => $parents])
</form>
@endsection
