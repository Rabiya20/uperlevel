<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\LeadImportBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadImportController extends Controller
{
    public function template(): StreamedResponse
    {
        $writer = new Xlsx(LeadImportBatch::sampleSpreadsheet());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'leads-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.leads.import-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $batch = LeadImportBatch::createFromUpload($request->file('file'), $tenant, auth()->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return redirect()->route('admin.crm.imports.show', $batch)->with('status', $batch->requires_approval
            ? 'File received — awaiting your review below.'
            : 'Import complete.');
    }

    public function index(): View
    {
        $tenant = $this->tenant();
        $batches = LeadImportBatch::where('tenant_id', $tenant->id)->with('uploader')->latest()->paginate(20);

        return view('admin.crm.imports.index', compact('batches'));
    }

    public function show(LeadImportBatch $batch): View
    {
        $batch->authorizeAccess(auth()->user());
        $batch->load(['uploader', 'reviewer', 'rows.duplicateLead']);

        return view('admin.crm.imports.show', compact('batch'));
    }

    public function approve(LeadImportBatch $batch): RedirectResponse
    {
        $batch->authorizeAccess(auth()->user());
        $batch->approve(auth()->user());

        return redirect()->route('admin.crm.imports.show', $batch)->with('status', 'Import approved — leads created.');
    }

    public function reject(LeadImportBatch $batch): RedirectResponse
    {
        $batch->authorizeAccess(auth()->user());
        $batch->reject(auth()->user());

        return redirect()->route('admin.crm.imports.show', $batch)->with('status', 'Import rejected.');
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
