@extends('layouts.admin')

@section('title', 'New Project — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>New Project</h2>
        <p><a href="{{ route('admin.projects.index') }}" style="color:var(--primary-dark);">← Back to Projects</a></p>
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
    <form method="POST" action="{{ route('admin.projects.store') }}">
        @csrf
        @php $project = null; @endphp
        @include('admin.projects._form', ['project' => $project, 'clients' => $clients, 'managers' => $managers])
    </form>
</div>
@endsection
