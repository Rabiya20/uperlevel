@extends('layouts.admin')

@section('title', $client->name.' — Clients — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>{{ $client->name }}</h2>
        <p><a href="{{ route('admin.company.clients.index') }}" style="color:var(--primary-dark);">← Back to Clients</a></p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('admin.finance.invoices.create', ['client_id' => $client->id]) }}" class="btn btn-primary">+ Create Invoice</a>
    </div>
</div>

<div class="grid-2" style="align-items:start;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="panel">
            <div class="panel-head"><h3>Client Details</h3></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><span style="color:var(--ink-soft);">Company</span><div style="font-weight:600;">{{ $client->company_name ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Source</span><div style="font-weight:600;">{{ $client->source ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Email</span><div style="font-weight:600;">{{ $client->email ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Phone</span><div style="font-weight:600;">{{ $client->phone ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Country</span><div style="font-weight:600;">{{ $client->country ?? '—' }}</div></div>
                <div><span style="color:var(--ink-soft);">Client since</span><div style="font-weight:600;">{{ $client->created_at->format('j M Y') }}</div></div>
                <div><span style="color:var(--ink-soft);">Converted by</span><div style="font-weight:600;">{{ $client->convertedBy->name ?? '—' }}</div></div>
                @if ($client->lead)
                    <div><span style="color:var(--ink-soft);">Originating lead</span><div style="font-weight:600;"><a href="{{ route('admin.crm.leads.show', $client->lead) }}" style="color:var(--primary-dark);">{{ $client->lead->name }} →</a></div></div>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h3>Matched Leads ({{ $client->leads->count() }})</h3></div>
            @if ($client->leads->isEmpty())
                <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No leads linked yet.</div>
            @else
                <table>
                    <tr><th>Lead</th><th>Status</th><th>Created</th><th></th></tr>
                    @foreach ($client->leads as $lead)
                        <tr>
                            <td>{{ $lead->name }}</td>
                            <td>{{ ucfirst(str_replace('-', ' ', $lead->status)) }}</td>
                            <td>{{ $lead->created_at->format('j M Y') }}</td>
                            <td><a href="{{ route('admin.crm.leads.show', $lead) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>

    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><h3>Invoices ({{ $client->invoices->count() }})</h3></div>
            @if ($client->invoices->isEmpty())
                <div style="padding:24px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No invoices yet.</div>
            @else
                <table>
                    <tr><th>Number</th><th>Status</th><th>Total</th><th></th></tr>
                    @foreach ($client->invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                            <td>{{ number_format((float) $invoice->total, 2) }}</td>
                            <td><a href="{{ route('admin.finance.invoices.show', $invoice) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">View</a></td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
