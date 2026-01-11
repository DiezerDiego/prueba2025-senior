<?php
declare(strict_types=1);
return [
    'payment' => [
        'base_uri' => $_ENV['PAYMENT_BASE_URI'],
        'timeout' => 3,
        'retries' => 3,
    ],
    'notification' => [
        'base_uri' => $_ENV['NOTIFICATION_BASE_URI'],
        'timeout' => 2,
    ],
];
