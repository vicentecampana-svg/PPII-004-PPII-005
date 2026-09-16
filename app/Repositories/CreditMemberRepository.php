<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repositorio de los integrantes del apartado Créditos (equipo Charlie).
 *
 * Los datos de créditos pasan de la constante hardcodeada CreditsService a la
 * tabla `credit_member`, para que el Super Admin pueda editarlos desde el panel.
 * El correo solo se incluye al editar (nunca en las respuestas públicas).
 */
class CreditMemberRepository
{
    public function findAll(int $limit = 20, int $offset = 0, bool $includeEmail = false): array
    {
        $cols = $includeEmail
            ? 'id, "key", name, role, email, orden'
            : 'id, "key", name, role, orden';

        return dbFetchAll(
            "SELECT {$cols}
               FROM credit_member
              ORDER BY orden, id
              LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    /**
     * Todos los integrantes para la vista pública, sin correo electrónico.
     */
    public function findAllPublic(): array
    {
        return dbFetchAll(
            'SELECT id, "key", name, role, orden
               FROM credit_member
              ORDER BY orden, id'
        );
    }

    public function findById(int $id, bool $includeEmail = false): ?array
    {
        $cols = $includeEmail
            ? 'id, "key", name, role, email, orden'
            : 'id, "key", name, role, orden';

        return dbFetchOne(
            "SELECT {$cols} FROM credit_member WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Busca un integrante por su clave (slug) usada por el formulario de contacto.
     */
    public function findByKey(string $key, bool $includeEmail = true): ?array
    {
        $cols = $includeEmail
            ? 'id, "key", name, role, email, orden'
            : 'id, "key", name, role, orden';

        return dbFetchOne(
            "SELECT {$cols} FROM credit_member WHERE \"key\" = :key",
            ['key' => $key]
        );
    }

    public function create(array $data): int
    {
        return (int) dbQuery(
            'INSERT INTO credit_member ("key", name, role, email, orden)
             VALUES (:key, :name, :role, :email, :orden)
             RETURNING id',
            [
                'key'   => $data['key'],
                'name'  => $data['name'],
                'role'  => $data['role'],
                'email' => $data['email'],
                'orden' => $data['orden'] ?? 0,
            ]
        )->fetchColumn();
    }

    public function update(int $id, array $data): int
    {
        return dbUpdate('credit_member', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int
    {
        return dbDelete('credit_member', 'id = :id', ['id' => $id]);
    }

    public function count(): int
    {
        $row = dbFetchOne('SELECT COUNT(*) AS t FROM credit_member');
        return (int) ($row['t'] ?? 0);
    }

    /**
     * Comprueba si una clave (slug) ya está en uso.
     */
    public function keyExists(string $key): bool
    {
        $row = dbFetchOne('SELECT id FROM credit_member WHERE "key" = :key', ['key' => $key]);
        return $row !== null;
    }

    /**
     * Próximo valor de `orden` para que los nuevos integrantes aparezcan al final.
     */
    public function nextOrden(): int
    {
        $row = dbFetchOne('SELECT COALESCE(MAX(orden), 0) + 1 AS next FROM credit_member');
        return (int) ($row['next'] ?? 1);
    }
}
