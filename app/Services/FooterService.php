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
}
