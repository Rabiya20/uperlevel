<?php

namespace App\Support\Reports;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared PDF/Excel/Print plumbing for report controllers. Expects the
 * using class to define a `tenant()` method returning the current Tenant.
 */
trait ExportsTabularReports
{
    private function export(string $title, string $subtitle, array $headers, array $rows, string $format): Response
    {
        abort_unless(in_array($format, ['pdf', 'excel', 'print'], true), 404);

        if ($format === 'excel') {
            return $this->toXlsx($title, $headers, $rows);
        }

        $document = [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $rows,
            'companyName' => $this->tenant()->name,
            'generatedAt' => now()->format('M j, Y g:i A'),
            'generatedBy' => auth()->user()->name,
        ];

        if ($format === 'print') {
            return response(view('admin.hr.reports.document', [...$document, 'mode' => 'print']));
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('admin.hr.reports.document', [...$document, 'mode' => 'pdf']);

        return $pdf->download(Str::slug($title).'-'.now()->format('Y-m-d').'.pdf');
    }

    private function toXlsx(string $title, array $headers, array $rows): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit($title, 31, ''));

        $sheet->fromArray($headers, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->fromArray($rows, null, 'A2');

        foreach (range(1, count($headers)) as $i) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = Str::slug($title).'-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
