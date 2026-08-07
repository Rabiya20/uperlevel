<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    public function template(): StreamedResponse
    {
        $writer = new Xlsx(User::sampleImportSpreadsheet());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'employees-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create(): View
    {
        return view('admin.hr.employees.import-create');
    }

    public function store(Request $request): View
    {
        $tenant = app('currentTenant');
        abort_unless($tenant, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $result = User::importFromSpreadsheet($request->file('file'), $tenant);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return view('admin.hr.employees.import-result', $result);
    }
}
