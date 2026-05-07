<?php
/**
 * Configuration - TamalBank API
 */

define('DB_HOST', 'db');
define('DB_NAME', 'tamalbank-db');
define('DB_USER', 'tamalbank-user');
define('DB_PASS', 'tamalbank-password');

define('BANK_API_URL', 'http://host.docker.internal:8083');
define('API_VERSION', '1.0.0');

return [
    'db' => [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
    ],
    'bank_api' => BANK_API_URL,
    'version' => API_VERSION,
];