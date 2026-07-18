<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'barberia',
        'user' => getenv('DB_USER') ?: 'barberia',
        'pass' => getenv('DB_PASS') ?: 'barberia123',
    ],
    'app' => [
        'name' => getenv('APP_NAME') ?: 'fioreee_barber',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/Argentina/Cordoba',
    ],
];
