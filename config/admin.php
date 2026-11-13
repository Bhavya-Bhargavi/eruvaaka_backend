<?php

return [
    'pdf_max_kb' => (int) env('ADMIN_PDF_MAX_KB', 51200),
    'image_max_kb' => (int) env('ADMIN_IMAGE_MAX_KB', 5120),
    'notification_email' => env('ADMIN_NOTIFICATION_EMAIL', 'admin@eruvaaka.com'),
];
