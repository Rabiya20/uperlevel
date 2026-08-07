@extends('layouts.admin')

@section('title', 'Finance Reports — UperLevel')

@section('content-body')
<div class="page-head">
    <div>
        <h2>Finance Reports</h2>
        <p>Every report can be downloaded as PDF or Excel, or sent straight to print.</p>
    </div>
</div>

<div class="reports-grid">
    <a href="{{ route('admin.finance.reports.profit-loss') }}" class="report-card">
        <div class="report-card-icon">📊</div>
        <div class="report-card-title">Profit & Loss</div>
        <p class="report-card-desc">Income and expense accounts for a date range, with total income, total expenses and net profit.</p>
    </a>

    <a href="{{ route('admin.finance.reports.expenses') }}" class="report-card">
        <div class="report-card-icon">🧾</div>
        <div class="report-card-title">Expense Report</div>
        <p class="report-card-desc">Every expense in a date range — vendor, subtotal, tax, total and status — optionally filtered by vendor.</p>
    </a>

    <a href="{{ route('admin.finance.reports.revenue') }}" class="report-card">
        <div class="report-card-icon">💵</div>
        <div class="report-card-title">Revenue & AR Aging</div>
        <p class="report-card-desc">Every non-draft invoice with amount paid, balance due, and an aging bucket for anything overdue.</p>
    </a>

    <a href="{{ route('admin.finance.reports.trial-balance') }}" class="report-card">
        <div class="report-card-icon">⚖️</div>
        <div class="report-card-title">Trial Balance</div>
        <p class="report-card-desc">Every account's current balance in its normal debit/credit column, proving total debits equal total credits.</p>
    </a>
</div>

<style>
    .reports-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
    .report-card{display:block;background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;text-decoration:none;transition:border-color .15s,box-shadow .15s;}
    .report-card:hover{border-color:var(--primary);box-shadow:0 4px 16px rgba(0,0,0,.06);}
    .report-card-icon{font-size:22px;margin-bottom:8px;}
    .report-card-title{font-size:14.5px;font-weight:700;color:var(--ink);margin-bottom:6px;}
    .report-card-desc{font-size:12px;color:var(--ink-soft);margin:0;line-height:1.5;}
</style>
@endsection
