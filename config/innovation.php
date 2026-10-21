<?php

return [
    'email_verification_required' => (bool) env('EMAIL_VERIFICATION_REQUIRED', false),
    'invitation_delivery' => env('ACCOUNT_INVITATION_DELIVERY', 'manual'),
    'cycle_timezone' => env('CYCLE_TIMEZONE', 'America/Sao_Paulo'),
    'attachments_enabled' => (bool) env('IDEA_ATTACHMENTS_ENABLED', false),
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),
    'privacy_contact' => env('PRIVACY_CONTACT'),
    'retention_days' => (int) env('READ_NOTIFICATIONS_RETENTION_DAYS', 0),
];
