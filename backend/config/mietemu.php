<?php

return [
    'queue' => [
        // Estimasi sementara; nanti bisa diganti rata-rata historis.
        'minutes_per_ticket' => (int) env('QUEUE_MINUTES_PER_TICKET', 7),
        'max_party_size' => 20,
    ],
    'verification' => [
        'code_ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_cooldown_seconds' => 60,
    ],
];
