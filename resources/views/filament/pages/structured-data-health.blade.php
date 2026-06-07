<x-filament-panels::page>
    <div style="display:grid;gap:12px;">
        @foreach($checks as $check)
            @php
                $color = match ($check['status']) {
                    'pass' => '#16a34a',
                    'warn' => '#d97706',
                    default => '#dc2626',
                };
            @endphp
            <div style="border:1px solid rgba(148,163,184,.24);border-radius:8px;padding:16px;background:rgba(15,23,42,.18);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <h2 style="margin:0;font-size:15px;font-weight:700;">{{ $check['name'] }}</h2>
                    <span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;background:{{ $color }};color:white;font-size:11px;font-weight:700;text-transform:uppercase;">{{ $check['status'] }}</span>
                </div>
                <p style="margin:8px 0 0;color:rgb(148,163,184);font-size:13px;">{{ $check['message'] }}</p>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
