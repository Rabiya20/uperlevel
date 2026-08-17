<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'role',
        'role_id',
        'shift_id',
        'custom_start_time',
        'custom_end_time',
        'avatar',
        'name',
        'email',
        'password',
        'employee_code',
        'phone',
        'department_id',
        'designation_id',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'employment_type',
        'employment_status',
        'cnic',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'reporting_manager_id',
        'basic_salary',
        'machine_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    // ---- Role constants -------------------------------------------------
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';

    // Roles that use the "Admin Portal" (header nav) layout
    public const ADMIN_PORTAL_ROLES = [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MANAGER];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
        'intern' => 'Intern',
    ];

    public const EMPLOYMENT_STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'terminated' => 'Terminated',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /** Null when no shift is assigned; the shift's own range when this employee uses standard hours; a labelled custom range when overridden. */
    public function workingHoursLabel(): ?string
    {
        if (! $this->shift) {
            return null;
        }

        if ($this->custom_start_time && $this->custom_end_time) {
            return Carbon::parse($this->custom_start_time)->format('g:i A').' – '.Carbon::parse($this->custom_end_time)->format('g:i A').' (custom)';
        }

        return $this->shift->formattedRange();
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Named "customRole", not "role" — the "role" column (owner/admin/
     * manager/employee) already means something else and drives the base
     * portal/access boundary; this is the optional finer-grained layer.
     */
    public function customRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Which portal this user's base role belongs to — used only to validate
     * a custom Role (see customRole()) is being assigned to a matching-portal
     * user. Not a general-purpose "current portal" helper (that's
     * SetTenantContext's job, which also accounts for superadmin impersonation).
     */
    public function basePortal(): string
    {
        return $this->isEmployee() ? 'employee' : 'admin';
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'reporting_manager_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function usesAdminPortal(): bool
    {
        return in_array($this->role, self::ADMIN_PORTAL_ROLES, true);
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function isOwnerOrAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->name));
        $letters = array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: 'U';
    }

    /**
     * Suggests the next employee code for this tenant, e.g. "EMP-104" — one
     * past the highest existing number under the configured prefix. Purely
     * a suggestion for the create form to pre-fill (still editable); the
     * actual uniqueness guarantee comes from the "employee_code" unique
     * validation rule, not from this computation.
     */
    public static function nextEmployeeCode(Tenant $tenant): string
    {
        $prefix = HrSettings::forTenant($tenant)->employee_code_prefix ?? '';

        $maxNumber = self::where('tenant_id', $tenant->id)
            ->where('employee_code', 'like', $prefix.'%')
            ->pluck('employee_code')
            ->map(fn ($code) => (int) preg_replace('/\D/', '', substr($code, strlen($prefix))))
            ->max();

        return $prefix.(($maxNumber ?? 0) + 1);
    }

    // ---- Bulk import (Employees) ----------------------------------------
    // No batch/approval workflow like Leads has — everyone with HR access
    // (owner/admin/manager) is already trusted to manage the roster
    // directly, so each row is validated and created in one pass.

    public const MAX_IMPORT_ROWS = 1000;

    private const IMPORT_FIELD_ALIASES = [
        'name' => ['name'],
        'email' => ['email'],
        'phone' => ['phone', 'phone number'],
        'employee_code' => ['employee code', 'employee_code', 'code'],
        'role' => ['role'],
        'designation' => ['designation', 'job title'],
        'department' => ['department'],
        'gender' => ['gender'],
        'date_of_birth' => ['date of birth', 'dob'],
        'date_of_joining' => ['date of joining', 'joining date', 'doj'],
        'employment_type' => ['employment type'],
        'cnic' => ['cnic', 'national id'],
        'address' => ['address'],
        'emergency_contact_name' => ['emergency contact name'],
        'emergency_contact_phone' => ['emergency contact phone'],
        'basic_salary' => ['basic salary', 'salary'],
    ];

    /** @return array{created: array, errors: array, total: int} */
    public static function importFromSpreadsheet(UploadedFile $file, Tenant $tenant): array
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
            foreach (self::IMPORT_FIELD_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $columnForField[$field] = $i;
                    break;
                }
            }
        }

        if (! isset($columnForField['name']) || ! isset($columnForField['email'])) {
            throw new \RuntimeException('Couldn\'t find "Name" and "Email" columns — download the sample template and match its headers.');
        }

        $rows = array_slice($rows, 0, self::MAX_IMPORT_ROWS);

        $created = [];
        $errors = [];
        $seenEmail = [];
        $seenCode = [];
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
                continue; // silently skip fully-blank rows
            }

            $result = self::evaluateImportRow($data, $rowNumber, $tenant->id, $seenEmail, $seenCode);

            if ($result['error']) {
                $errors[] = ['row' => $rowNumber, 'name' => $data['name'] ?? '—', 'message' => $result['error']];

                continue;
            }

            $password = Str::password(12, symbols: false);
            $employee = self::create([
                ...$result['data'],
                'tenant_id' => $tenant->id,
                'password' => Hash::make($password),
            ]);

            $created[] = ['row' => $rowNumber, 'employee' => $employee, 'password' => $password];
        }

        return ['created' => $created, 'errors' => $errors, 'total' => count($rows)];
    }

    /** @return array{error: ?string, data: array} */
    private static function evaluateImportRow(array $data, int $rowNumber, int $tenantId, array &$seenEmail, array &$seenCode): array
    {
        $fail = fn (string $message) => ['error' => $message, 'data' => []];

        if (blank($data['name'] ?? null)) {
            return $fail('Name is required.');
        }
        if (blank($data['email'] ?? null) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $fail('A valid email is required.');
        }

        $email = mb_strtolower($data['email']);
        if (isset($seenEmail[$email])) {
            return $fail('Duplicate of row '.$seenEmail[$email].' in this file (email).');
        }
        if (self::where('email', $email)->exists()) {
            return $fail('This email is already registered.');
        }
        $seenEmail[$email] = $rowNumber;

        $code = filled($data['employee_code'] ?? null) ? $data['employee_code'] : null;
        if ($code !== null) {
            if (isset($seenCode[$code])) {
                return $fail('Duplicate of row '.$seenCode[$code].' in this file (employee code).');
            }
            if (self::where('tenant_id', $tenantId)->where('employee_code', $code)->exists()) {
                return $fail('Employee code "'.$code.'" is already in use.');
            }
            $seenCode[$code] = $rowNumber;
        }

        $role = 'employee';
        if (filled($data['role'] ?? null)) {
            $normalizedRole = strtolower($data['role']);
            if (! in_array($normalizedRole, ['employee', 'manager'], true)) {
                return $fail('Role must be "Employee" or "Manager".');
            }
            $role = $normalizedRole;
        }

        $employmentType = 'full_time';
        if (filled($data['employment_type'] ?? null)) {
            $match = collect(self::EMPLOYMENT_TYPES)->search(fn ($label) => strtolower($label) === strtolower($data['employment_type']));
            if ($match === false && ! array_key_exists($data['employment_type'], self::EMPLOYMENT_TYPES)) {
                return $fail('Employment type must be one of: '.implode(', ', self::EMPLOYMENT_TYPES).'.');
            }
            $employmentType = $match !== false ? $match : $data['employment_type'];
        }

        foreach (['date_of_birth', 'date_of_joining'] as $dateField) {
            if (filled($data[$dateField] ?? null)) {
                try {
                    $data[$dateField] = \Carbon\Carbon::parse($data[$dateField])->toDateString();
                } catch (\Throwable $e) {
                    return $fail(ucfirst(str_replace('_', ' ', $dateField)).' isn\'t a valid date.');
                }
            }
        }

        if (filled($data['basic_salary'] ?? null) && ! is_numeric($data['basic_salary'])) {
            return $fail('Basic salary must be a number.');
        }

        if (filled($data['gender'] ?? null) && ! in_array(strtolower($data['gender']), ['male', 'female', 'other'], true)) {
            return $fail('Gender must be Male, Female or Other.');
        }

        return [
            'error' => null,
            'data' => [
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'employee_code' => $code,
                'role' => $role,
                'designation_id' => filled($data['designation'] ?? null)
                    ? Designation::firstOrCreate(['tenant_id' => $tenantId, 'name' => $data['designation']])->id
                    : null,
                'department_id' => filled($data['department'] ?? null)
                    ? Department::firstOrCreate(['tenant_id' => $tenantId, 'name' => $data['department']])->id
                    : null,
                'gender' => filled($data['gender'] ?? null) ? strtolower($data['gender']) : null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'date_of_joining' => $data['date_of_joining'] ?? null,
                'employment_type' => $employmentType,
                'cnic' => $data['cnic'] ?? null,
                'address' => $data['address'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'basic_salary' => filled($data['basic_salary'] ?? null) ? (float) $data['basic_salary'] : null,
            ],
        ];
    }

    /** The downloadable "fill this in" template — single source of truth for the column contract. */
    public static function sampleImportSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        $headers = [
            'Name', 'Email', 'Phone', 'Employee Code', 'Role', 'Designation', 'Department', 'Gender',
            'Date of Birth', 'Date of Joining', 'Employment Type', 'CNIC', 'Address',
            'Emergency Contact Name', 'Emergency Contact Phone', 'Basic Salary',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        $example = [
            'Jane Doe', 'jane@example.com', '+92 300 1234567', 'EMP-101', 'Employee', 'Video Editor', 'Creative',
            'Female', '1998-04-12', '2024-02-01', 'Full-time', '12345-1234567-1', '123 Main St, Lahore',
            'John Doe', '+92 300 7654321', 85000,
        ];
        $sheet->fromArray($example, null, 'A2');

        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
