<?php

namespace App\Http\Controllers\Employee;

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
        return view('employee.leads.import-create');
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

        return redirect()->route('employee.leads.imports.show', $batch)
            ->with('status', 'File received — an admin will review it before these become leads.');
    }

    public function index(): View
    {
        $batches = LeadImportBatch::where('uploaded_by', auth()->id())->latest()->paginate(20);

        return view('employee.leads.imports.index', compact('batches'));
    }

    public function show(LeadImportBatch $batch): View
    {
        $batch->authorizeAccess(auth()->user());
        $batch->load('rows');

        return view('employee.leads.imports.show', compact('batch'));
    }

    private function tenant()
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        return $tenant;
    }
}
