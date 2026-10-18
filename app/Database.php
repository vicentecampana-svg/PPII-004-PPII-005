<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;

/**
 * CAL-04: acceso a la base de datos en una clase pequeña y autocontenida.
 *
 * Reemplaza las funciones globales db()/dbQuery()/dbFetch*() de helpers.php,
 * que no se pueden mockear fácilmente en pruebas.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static bool $connectionFailed = false;

    public static function connection(): PDO
    {
        if (self::$connectionFailed) {
            throw new PDOException('Conexión a base de datos no disponible.');
        }

        if (self::$pdo === null) {
            $db = config('db');

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;connect_timeout=2',
                $db['host'],
                $db['port'],
                $db['database']
            );

            try {
                self::$pdo = new PDO($dsn, $db['username'], $db['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2,
                ]);
            } catch (PDOException $e) {
                self::$connectionFailed = true;
                throw $e;
            }
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $params = array_map(static fn($v): mixed => is_bool($v) ? ($v ? 'true' : 'false') : $v, $params);
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();

        return $row ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";

        return (int) self::query($sql, array_values($data))->fetchColumn();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        // PDO no admite mezclar placeholders "?" y ":nombre" en una misma
        // consulta, así que el SET usa el mismo estilo que el WHERE recibido.
        $params = [];
        $setParts = [];
        foreach ($data as $col => $val) {
            $placeholder = 'set_' . str_replace(['-', '.'], '_', $col);
            $setParts[] = "{$col} = :{$placeholder}";
            $params[$placeholder] = $val;
        }

        $whereIndex = 0;
        $positionalWhereParams = [];
        $where = preg_replace_callback('/\?/', static function () use (&$whereIndex, &$positionalWhereParams, $whereParams): string {
            $placeholder = 'where_' . $whereIndex;
            $positionalWhereParams[$placeholder] = array_values($whereParams)[$whereIndex] ?? null;
            $whereIndex++;

            return ':' . $placeholder;
        }, $where);

        if ($whereIndex > 0) {
            $params = array_merge($params, $positionalWhereParams);
        } else {
            $params = array_merge($params, $whereParams);
        }

        $set = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        return self::query($sql, $params)->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM {$table} WHERE {$where}", $params)->rowCount();
    }
}
