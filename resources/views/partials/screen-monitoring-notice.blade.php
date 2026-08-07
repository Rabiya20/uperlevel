@php
    $user = auth()->user();
    $captureSettings = ! $user->isSuperAdmin() && isset($currentTenant)
        ? \App\Models\ScreenCaptureSettings::forTenant($currentTenant)
        : null;
@endphp
@if ($captureSettings && $captureSettings->enabled && $captureSettings->notify_employees)
    <div class="monitoring-notice">
        🖥 This workspace periodically captures screen activity during working hours for productivity monitoring.
    </div>
    <style>
        .monitoring-notice{background:#FFF4E5;color:#B4690E;font-size:12.5px;font-weight:600;padding:10px 16px;border-radius:8px;margin-bottom:14px;}
    </style>
@endif
