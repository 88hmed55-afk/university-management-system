<?php

return [
    'definition_status' => [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'archived' => 'Archived',
    ],
    'dashboard_status' => [
        'active' => 'Active',
        'archived' => 'Archived',
    ],
    'export_status' => [
        'completed' => 'Completed',
        'refused' => 'Refused',
        'failed' => 'Failed',
    ],
    'format' => [
        'csv' => 'CSV',
        'pdf' => 'PDF (pending approved template)',
        'xlsx' => 'XLSX (pending approved template)',
    ],
    'freshness' => [
        'live' => 'Live',
        'periodic' => 'Scheduled by approved reference',
    ],
    'mask' => 'Hidden',
];
