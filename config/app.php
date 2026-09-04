<?php

declare(strict_types=1);

return [
    'app_name' => 'TECH HUB ULS',
    'app_env'  => getenv('APP_ENV') ?: 'development',

    'db' => [
        'host'     => getenv('PG_HOST') ?: 'localhost',
        'port'     => getenv('PG_PORT') ?: '5432',
        'database' => getenv('PG_DATABASE') ?: 'techhub',
        'username' => getenv('PG_USER') ?: 'postgres',
        'password' => getenv('PG_PASSWORD') ?: '',
    ],

    'storage_path'    => dirname(__DIR__) . '/public/uploads',
    'storage_url'     => '/uploads',
    'max_upload_size' => 5242880, // 5MB

    'base_url' => '/',

    'cors' => [
        'allowed_origins' => array_filter(
            array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:8080,http://127.0.0.1:8080,http://localhost:3000,http://localhost:5173'))
        ),
        'allow_credentials' => true,
    ],
];
