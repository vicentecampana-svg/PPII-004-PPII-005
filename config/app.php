<?php

declare(strict_types=1);

return [
    'app_name' => 'Software Factory Lab ULS',
    'app_env'  => getenv('APP_ENV') ?: 'development',

    'db' => [
        'host'     => getenv('PG_HOST') ?: 'localhost',
        'port'     => getenv('PG_PORT') ?: '5432',
        'database' => getenv('PG_DATABASE') ?: 'techhub',
        'username' => getenv('PG_USER') ?: 'techhub',
        'password' => getenv('PG_PASSWORD') ?: '',
    ],

    'storage_path'    => dirname(__DIR__) . '/public/uploads',
    'storage_url'     => '/uploads',
    'max_upload_size' => 5242880, // 5MB

    'base_url' => '/',

    // IU-002 / RNF-SEC-004: expiración de sesión del panel por inactividad.
    'session' => [
        'idle_timeout_seconds' => 900, // 15 minutos
    ],

    // SEC-01: IPs de proxies inversos de confianza (balanceadores, CDNs).
    // Solo si REMOTE_ADDR coincide con una de estas se considera
    // X-Forwarded-For; en cualquier otro caso se usa REMOTE_ADDR directo.
    'trusted_proxies' => array_filter(
        array_map('trim', explode(',', getenv('TRUSTED_PROXIES') ?: ''))
    ),

    'cors' => [
        'allowed_origins' => array_filter(
            array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:8080,http://127.0.0.1:8080,http://localhost:3000,http://localhost:5173'))
        ),
        'allow_credentials' => true,
    ],
];
