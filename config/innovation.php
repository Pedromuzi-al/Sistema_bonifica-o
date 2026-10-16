<?php

return [
    'attachments_enabled' => (bool) env('IDEA_ATTACHMENTS_ENABLED', false),
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),
    'privacy_contact' => env('PRIVACY_CONTACT'),
    'retention_days' => (int) env('READ_NOTIFICATIONS_RETENTION_DAYS', 0),
];
