<?php

declare(strict_types=1);

namespace App\Repositories;

class FooterRepository
{
    public function findLinks(): array
    {
        return dbFetchAll(
            "SELECT id, grupo, etiqueta, url, orden
             FROM enlaces_footer ORDER BY grupo, orden"
        );
    }

    public function findLinkById(int $id): ?array
    {
        return dbFetchOne(
            "SELECT id, grupo, etiqueta, url, orden
             FROM enlaces_footer WHERE id = :id",
            ['id' => $id]
        );
    }

    public function createLink(array $data): int
    {
        return dbInsert('enlaces_footer', $data);
    }

    public function updateLink(int $id, array $data): int
    {
        return dbUpdate('enlaces_footer', $data, 'id = :id', ['id' => $id]);
    }

    public function deleteLink(int $id): int
    {
        return dbDelete('enlaces_footer', 'id = :id', ['id' => $id]);
    }

    public function findInfo(): ?array
    {
        return dbFetchOne(
            "SELECT id, email, phone, address, copyright_text,
                    social_facebook, social_linkedin, social_twitter,
                    social_instagram, social_youtube
             FROM footer_info ORDER BY id LIMIT 1"
        );
    }

    public function updateInfo(int $id, array $data): int
    {
        return dbUpdate('footer_info', $data, 'id = :id', ['id' => $id]);
    }

    public function findContenido(): ?array
    {
        return dbFetchOne(
            "SELECT id, clave, sobre_titulo, sobre_texto,
                    mision_titulo, mision_texto, vision_titulo, vision_texto,
                    objetivos_titulo, objetivos_texto, politicas_titulo, politicas_texto
             FROM contenido_sitio WHERE clave = 'home'"
        );
    }

    public function updateContenido(int $id, array $data): int
    {
        return dbUpdate('contenido_sitio', $data, 'id = :id', ['id' => $id]);
    }
}
