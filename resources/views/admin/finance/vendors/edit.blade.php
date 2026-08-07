@extends('layouts.admin')

@section('title', 'Edit '.$vendor->name.' — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Edit {{ $vendor->name }}</h2>
        <p><a href="{{ route('admin.finance.vendors.index') }}" style="color:var(--primary-dark);">← Back to Vendors</a></p>
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('admin.finance.vendors.update', $vendor) }}">
        @csrf
        @method('PUT')
        @include('admin.finance.vendors._form', ['vendor' => $vendor])
    </form>
</div>
@endsection
