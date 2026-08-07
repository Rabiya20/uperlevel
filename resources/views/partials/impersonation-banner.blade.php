@if ($isImpersonating ?? false)
<div class="impersonation-banner">
    <span>You are viewing <b>{{ $currentTenant->name }}</b> as a Super Admin support session — no changes are attributed to a company user.</span>
    <form method="POST" action="{{ route('superadmin.exit-impersonation') }}">
        @csrf
        <button type="submit">Exit to Tenants list</button>
    </form>
</div>
@endif
