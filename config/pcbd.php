<?php

return [
    /*
    | Application-specific, non-secret defaults. Business-editable values
    | (contact numbers, social links, analytics IDs) live in the `settings` table.
    */
    'currency' => 'BDT',
    'area_unit' => 'sqft',

    // Admin/Super Admin accounts must enrol in app-based 2FA (ADR-0008).
    // Preview-only switch (docker/staging): lets DemoSeeder run on a production-mode server. Must be false at launch.
    'allow_demo' => env('PCBD_ALLOW_DEMO', false),

    'admin_mfa_required' => env('PCBD_ADMIN_MFA_REQUIRED', true),

    // Lead first-response target in minutes (used from Phase 5).
    'lead_sla_minutes' => 30,
];
