<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

/**
 * Conexión PDO única y perezosa a PostgreSQL.
 *
 * Si las variables de entorno no están definidas o la conexión falla,
 * connect() retorna null en vez de lanzar una excepción, para que las
 * vistas puedan seguir funcionando con contenido de respaldo.
 */
final class Database
{
    private static ?PDO $connection = null;
    private static bool $attempted = false;

    public static function connect(): ?PDO
    {
        if (self::$attempted) {
            return self::$connection;
        }

        self::$attempted = true;

        $host = getenv('PG_HOST');
        $port = getenv('PG_PORT');
        $database = getenv('PG_DATABASE');
        $user = getenv('PG_USER');
        $password = getenv('PG_PASSWORD');

        if (!$host || !$database || !$user) {
            return null;
        }

        try {
            self::$connection = new PDO(
                "pgsql:host={$host};port={$port};dbname={$database}",
                $user,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException) {
            self::$connection = null;
        }

        return self::$connection;
    }
}
