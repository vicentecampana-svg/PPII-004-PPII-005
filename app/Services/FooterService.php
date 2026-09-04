<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FooterRepository;

class FooterService
{
    private FooterRepository $repo;
    private AuditService $audit;

    public function __construct(?FooterRepository $repo = null, ?AuditService $audit = null)
    {
        $this->repo = $repo ?? new FooterRepository();
        $this->audit = $audit ?? new AuditService();
    }

    public function getAll(): array
    {
        return [
            'links'     => $this->repo->findLinks(),
            'info'      => $this->repo->findInfo(),
            'contenido' => $this->repo->findContenido(),
        ];
    }

    public function getLinkById(int $id): ?array
    {
        return $this->repo->findLinkById($id);
    }

    public function createLink(array $data): array
    {
        if (empty($data['etiqueta']) || trim($data['etiqueta']) === '') {
            throw new \InvalidArgumentException('La etiqueta del enlace es obligatoria.');
        }
        if (empty($data['url']) || trim($data['url']) === '') {
            throw new \InvalidArgumentException('La URL del enlace es obligatoria.');
        }

        $id = $this->repo->createLink([
            'grupo'    => $data['grupo'] ?? 'Sitio',
            'etiqueta' => trim($data['etiqueta']),
            'url'      => trim($data['url']),
            'orden'    => (int) ($data['orden'] ?? 0),
        ]);

        $this->audit->log(null, 'crear', 'enlaces_footer', $id, 'Enlace de footer creado: ' . $data['etiqueta']);

        return $this->repo->findLinkById($id) ?? [];
    }

    public function updateLink(int $id, array $data): array
    {
        $existing = $this->repo->findLinkById($id);
        if (!$existing) {
            throw new \RuntimeException('Enlace del footer no encontrado.');
        }

        $fields = [];
        if (array_key_exists('grupo', $data))    $fields['grupo'] = trim($data['grupo'] ?? 'Sitio');
        if (array_key_exists('etiqueta', $data)) $fields['etiqueta'] = trim($data['etiqueta']);
        if (array_key_exists('url', $data))      $fields['url'] = trim($data['url']);
        if (array_key_exists('orden', $data))    $fields['orden'] = (int) ($data['orden'] ?? 0);

        if ($fields) {
            $this->repo->updateLink($id, $fields);
        }

        $this->audit->log(null, 'actualizar', 'enlaces_footer', $id, 'Enlace de footer actualizado: ' . ($data['etiqueta'] ?? $existing['etiqueta']));

        return $this->repo->findLinkById($id) ?? [];
    }

    public function deleteLink(int $id): void
    {
        $existing = $this->repo->findLinkById($id);
        if (!$existing) {
            throw new \RuntimeException('Enlace del footer no encontrado.');
        }

        $this->repo->deleteLink($id);
        $this->audit->log(null, 'eliminar', 'enlaces_footer', $id, 'Enlace de footer eliminado: ' . ($existing['etiqueta'] ?? ''));
    }

    public function updateInfo(array $data): array
    {
        $existing = $this->repo->findInfo();
        if (!$existing) {
            throw new \RuntimeException('No se encontró configuración del footer.');
        }

        $fields = [];
        $allowed = ['email', 'phone', 'address', 'copyright_text', 'social_facebook',
                     'social_linkedin', 'social_twitter', 'social_instagram', 'social_youtube'];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[$f] = $data[$f] ?: null;
            }
        }

        if ($fields) {
            $this->repo->updateInfo($existing['id'], $fields);
        }

        $this->audit->log(null, 'actualizar', 'footer_info', (int) $existing['id'], 'Información del footer actualizada');

        return $this->repo->findInfo();
    }

    public function updateContenido(array $data): array
    {
        $existing = $this->repo->findContenido();
        if (!$existing) {
            $id = dbInsert('contenido_sitio', [
                'clave'            => 'home',
                'sobre_titulo'     => $data['sobre_titulo'] ?? null,
                'sobre_texto'      => $data['sobre_texto'] ?? null,
                'mision_titulo'    => $data['mision_titulo'] ?? null,
                'mision_texto'     => $data['mision_texto'] ?? null,
                'vision_titulo'    => $data['vision_titulo'] ?? null,
                'vision_texto'     => $data['vision_texto'] ?? null,
                'objetivos_titulo' => $data['objetivos_titulo'] ?? null,
                'objetivos_texto'  => $data['objetivos_texto'] ?? null,
            ]);
            $this->audit->log(null, 'crear', 'contenido_sitio', $id, 'Contenido del sitio creado');
            return $this->repo->findContenido() ?? [];
        }

        $fields = ['updated_at' => date('Y-m-d H:i:s')];
        $allowed = ['sobre_titulo', 'sobre_texto', 'mision_titulo', 'mision_texto',
                    'vision_titulo', 'vision_texto', 'objetivos_titulo', 'objetivos_texto',
                    'politicas_titulo', 'politicas_texto'];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[$f] = $data[$f] ?: null;
            }
        }

        if ($fields) {
            $this->repo->updateContenido((int) $existing['id'], $fields);
        }

        $this->audit->log(null, 'actualizar', 'contenido_sitio', (int) $existing['id'], 'Contenido Sobre Nosotros actualizado');

        return $this->repo->findContenido() ?? [];
    }
}
