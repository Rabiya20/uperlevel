<?php

namespace App\Http\Controllers\Admin\Company;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant();

        $clients = Client::where('tenant_id', $tenant->id)
            ->with(['lead', 'convertedBy'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.company.clients.index', compact('clients'));
    }

    public function show(Client $client): View
    {
        abort_unless($client->tenant_id === $this->tenant()->id, 404);

        $client->load(['lead', 'convertedBy', 'leads', 'invoices']);

        return view('admin.company.clients.show', compact('client'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
