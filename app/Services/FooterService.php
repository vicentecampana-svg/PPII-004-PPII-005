<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FooterRepository;

class FooterService
{
    private FooterRepository $repo;

    public function __construct()
    {
        $this->repo = new FooterRepository();
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

        return $this->repo->findInfo();
    }
}
