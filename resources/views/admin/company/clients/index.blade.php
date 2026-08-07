@extends('layouts.admin')

@section('title', 'Clients — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Clients</h2>
        <p>Every company that has been won from the Leads pipeline, one record per client — duplicate leads for the same client are matched here, not re-added.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px;">
    <form method="GET" style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:220px;">
            <label class="f-label">Search</label>
            <input class="f-input" type="text" name="q" value="{{ request('q') }}" placeholder="Name, company or email…">
        </div>
        <button type="submit" class="btn btn-ghost" style="padding:9px 16px;">Filter</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h3>{{ $clients->total() }} client{{ $clients->total() === 1 ? '' : 's' }}</h3></div>
    @if ($clients->isEmpty())
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No clients yet — clients are added automatically when a lead is won.</div>
    @else
        <table>
            <tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Source</th><th>Converted By</th><th>Date</th><th></th></tr>
            @foreach ($clients as $client)
                <tr>
                    <td><strong>{{ $client->name }}</strong></td>
                    <td>{{ $client->company_name ?? '—' }}</td>
                    <td>{{ $client->email ?? '—' }}</td>
                    <td>{{ $client->phone ?? '—' }}</td>
                    <td>{{ $client->source ?? '—' }}</td>
                    <td>{{ $client->convertedBy->name ?? '—' }}</td>
                    <td>{{ $client->created_at->format('j M Y') }}</td>
                    <td><a href="{{ route('admin.company.clients.show', $client) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@if ($clients->hasPages())
    <div style="margin-top:16px;">{{ $clients->links() }}</div>
@endif

<style>
    .f-label{display:block;font-size:11.5px;font-weight:700;color:var(--ink-soft);margin-bottom:6px;text-transform:uppercase;letter-spacing:.02em;}
    .f-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:var(--bg);color:var(--ink);}
</style>
@endsection
