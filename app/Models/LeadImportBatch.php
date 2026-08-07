<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class LeadImportBatch extends Model
{
    public const MAX_ROWS = 2000;

    // Header cell (lowercased, trimmed) => which Lead field it feeds.
    private const FIELD_ALIASES = [
        'name' => ['name'],
        'company_name' => ['company name', 'company'],
        'email' => ['email'],
        'phone' => ['phone', 'phone number'],
        'source' => ['source'],
        'country' => ['country'],
        'budget' => ['budget'],
        'description' => ['description', 'notes'],
    ];

    protected $fillable = [
        'tenant_id',
        'uploaded_by',
        'original_filename',
        'stored_path',
        'status',
        'requires_approval',
        'total_rows',
        'valid_rows',
        'duplicate_rows',
        'error_rows',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(LeadImportRow::class, 'batch_id')->orderBy('row_number');
    }

    public function authorizeAccess(User $user): void
    {
        abort_unless($this->tenant_id === $user->tenant_id, 404);

        if (! $user->isOwnerOrAdmin() && $this->uploaded_by !== $user->id) {
            abort(404);
        }
    }

    /**
     * Parses an uploaded spreadsheet against the sample template's headers,
     * validates every row, and stores the batch + rows. Owner/admin uploads
     * import immediately; employee uploads stop at "pending_review".
     *
     * @throws \RuntimeException on an unreadable file or a missing "Name" column
     */
    public static function createFromUpload(UploadedFile $file, Tenant $tenant, User $uploader): self
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not read this file — make sure it\'s a valid .xlsx, .xls or .csv export.');
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        if (empty($rows)) {
            throw new \RuntimeException('That file is empty.');
        }

        $header = array_shift($rows);
        $columnForField = [];
        foreach ($header as $i => $cell) {
            $normalized = strtolower(trim((string) $cell));
            foreach (self::FIELD_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $columnForField[$field] = $i;
                    break;
                }
            }
        }

        if (! isset($columnForField['name'])) {
            throw new \RuntimeException('Couldn\'t find a "Name" column — download the sample template and match its headers.');
        }

        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $storedPath = $file->store("lead-imports/{$tenant->id}", 'local');

        return DB::transaction(function () use ($rows, $columnForField, $tenant, $uploader, $file, $storedPath) {
            $requiresApproval = ! $uploader->isOwnerOrAdmin();

            $batch = self::create([
                'tenant_id' => $tenant->id,
                'uploaded_by' => $uploader->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'status' => 'processing',
                'requires_approval' => $requiresApproval,
            ]);

            $counts = ['valid' => 0, 'duplicate' => 0, 'error' => 0];
            $seenEmail = $seenPhone = $seenCompany = [];
            $rowNumber = 1; // row 1 was the header

            foreach ($rows as $raw) {
                $rowNumber++;

                $data = [];
                foreach ($columnForField as $field => $colIndex) {
                    $value = $raw[$colIndex] ?? null;
                    $value = is_string($value) ? trim($value) : $value;
                    $data[$field] = $value === '' ? null : $value;
                }

                if (empty(array_filter($data, fn ($v) => $v !== null))) {
                    continue; // silently skip fully-blank rows (common at sheet's end)
                }

                $result = self::evaluateRow($data, $rowNumber, $tenant->id, $seenEmail, $seenPhone, $seenCompany);
                $counts[$result['status']]++;

                LeadImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    ...$result['data'],
                    'status' => $result['status'],
                    'error_message' => $result['error_message'],
                    'duplicate_lead_id' => $result['duplicate_lead_id'],
                ]);
            }

            $batch->update([
                'total_rows' => array_sum($counts),
                'valid_rows' => $counts['valid'],
                'duplicate_rows' => $counts['duplicate'],
                'error_rows' => $counts['error'],
                'status' => $requiresApproval ? 'pending_review' : 'processing',
            ]);

            if (! $requiresApproval) {
                $batch->import();
            }

            return $batch->fresh();
        });
    }

    /** @return array{status: string, error_message: ?string, duplicate_lead_id: ?int, data: array} */
    private static function evaluateRow(array $data, int $rowNumber, int $tenantId, array &$seenEmail, array &$seenPhone, array &$seenCompany): array
    {
        $fail = fn (string $message) => ['status' => 'error', 'error_message' => $message, 'duplicate_lead_id' => null, 'data' => $data];

        if (blank($data['name'] ?? null)) {
            return $fail('Name is required.');
        }

        if (filled($data['email'] ?? null) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $fail('Invalid email format.');
        }

        if (filled($data['budget'] ?? null)) {
            if (! is_numeric($data['budget'])) {
                // Null the unparseable value out before returning — it's
                // going straight into a decimal DB column either way, and
                // $fail below captured $data before this point so it can't
                // see this row's own mutations.
                return [
                    'status' => 'error',
                    'error_message' => 'Budget must be a number.',
                    'duplicate_lead_id' => null,
                    'data' => [...$data, 'budget' => null],
                ];
            }
            $data['budget'] = (float) $data['budget'];
        }

        $email = filled($data['email'] ?? null) ? mb_strtolower($data['email']) : null;
        $phone = $data['phone'] ?? null;
        $company = filled($data['company_name'] ?? null) ? mb_strtolower($data['company_name']) : null;

        if ($email !== null && isset($seenEmail[$email])) {
            return $fail('Duplicate of row '.$seenEmail[$email].' in this file (email).');
        }
        if ($phone !== null && isset($seenPhone[$phone])) {
            return $fail('Duplicate of row '.$seenPhone[$phone].' in this file (phone).');
        }
        if ($company !== null && isset($seenCompany[$company])) {
            return $fail('Duplicate of row '.$seenCompany[$company].' in this file (company name).');
        }
        if ($email !== null) {
            $seenEmail[$email] = $rowNumber;
        }
        if ($phone !== null) {
            $seenPhone[$phone] = $rowNumber;
        }
        if ($company !== null) {
            $seenCompany[$company] = $rowNumber;
        }

        $duplicate = Lead::findDuplicate($tenantId, $data['email'] ?? null, $data['phone'] ?? null, $data['company_name'] ?? null);
        if ($duplicate) {
            return ['status' => 'duplicate', 'error_message' => null, 'duplicate_lead_id' => $duplicate->id, 'data' => $data];
        }

        return ['status' => 'valid', 'error_message' => null, 'duplicate_lead_id' => null, 'data' => $data];
    }

    /**
     * Turns every importable row (valid, plus duplicates too when the
     * tenant's CRM settings allow duplicates) into a real Lead.
     */
    public function import(): void
    {
        $settings = CrmSettings::forTenant($this->tenant);
        $uploader = $this->uploader;
        $importDuplicates = $settings->duplicate_handling === 'allow';

        $rows = $this->rows()
            ->whereNull('imported_lead_id')
            ->where(fn ($q) => $q->where('status', 'valid')->when($importDuplicates, fn ($q2) => $q2->orWhere('status', 'duplicate')))
            ->get();

        foreach ($rows as $row) {
            $lead = Lead::create([
                'tenant_id' => $this->tenant_id,
                'name' => $row->name,
                'company_name' => $row->company_name,
                'email' => $row->email,
                'phone' => $row->phone,
                'source' => $row->source,
                'country' => $row->country,
                'budget' => $row->budget,
                'description' => $row->description,
                'status' => $settings->firstStageKey(),
                'created_by' => $uploader->id,
                'assigned_employee_id' => $uploader->isOwnerOrAdmin() ? null : $uploader->id,
            ]);
            $lead->logActivity('created', $uploader, 'Imported from "'.$this->original_filename.'".');
            $row->update(['imported_lead_id' => $lead->id]);
        }

        $this->update(['status' => 'completed']);
    }

    public function approve(User $admin): void
    {
        abort_unless($this->status === 'pending_review', 422, 'Nothing to approve.');

        $this->import();
        $this->update(['reviewed_by' => $admin->id, 'reviewed_at' => now()]);

        ActivityLog::record('import_approved', "Approved import batch \"{$this->original_filename}\" — {$this->valid_rows} leads created.", $this);
    }

    public function reject(User $admin): void
    {
        abort_unless($this->status === 'pending_review', 422, 'Nothing to reject.');

        $this->update(['status' => 'rejected', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);

        ActivityLog::record('import_rejected', "Rejected import batch \"{$this->original_filename}\".", $this);
    }

    /** The downloadable "fill this in" template — single source of truth for the column contract. */
    public static function sampleSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leads');

        $headers = ['Name', 'Company Name', 'Email', 'Phone', 'Source', 'Country', 'Budget', 'Description'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $example = ['Jane Doe', 'Acme Corp', 'jane@acme.com', '+1 555 0100', 'Website', 'USA', 5000, 'Interested in the Pro plan.'];
        $sheet->fromArray($example, null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
