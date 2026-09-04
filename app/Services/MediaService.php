<?php

declare(strict_types=1);

namespace App\Services;

class MediaService
{
    private string $uploadDir;
    private string $urlPrefix;
    private int $maxSize;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    private array $allowedMimes = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    public function __construct(?string $uploadDir = null, ?string $urlPrefix = null, ?int $maxSize = null)
    {
        $config = [];
        $configFile = dirname(__DIR__, 2) . '/config/app.php';
        if (file_exists($configFile)) {
            $loaded = require $configFile;
            if (is_array($loaded)) {
                $config = $loaded;
            }
        }

        $this->uploadDir = $uploadDir ?? (string) ($config['storage_path'] ?? dirname(__DIR__, 2) . '/public/uploads');
        $this->urlPrefix = $urlPrefix ?? (string) ($config['storage_url'] ?? '/uploads');
        $this->maxSize   = $maxSize ?? (int) ($config['max_upload_size'] ?? 5242880); // 5 MB

        $this->ensureDirectoryExists();
    }

    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Valida un archivo subido sin guardarlo.
     *
     * @throws \InvalidArgumentException Si el archivo es inválido, excede el tamaño o tiene un formato no permitido.
     */
    public function validate(array $file): void
    {
        if (empty($file) || !isset($file['error'])) {
            throw new \InvalidArgumentException('No se ha proporcionado un archivo válido.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_PARTIAL   => 'El archivo fue subido solo parcialmente.',
                UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR=> 'Falta la carpeta temporal en el servidor.',
                UPLOAD_ERR_CANT_WRITE=> 'No se pudo escribir el archivo en el disco.',
                default              => 'Error desconocido al subir el archivo.',
            };
            throw new \InvalidArgumentException($message);
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > $this->maxSize) {
            $maxMb = round($this->maxSize / (1024 * 1024), 2);
            throw new \InvalidArgumentException("El archivo excede el tamaño máximo permitido de {$maxMb}MB.");
        }

        $origName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($ext === '' || !in_array($ext, $this->allowedExtensions, true)) {
            $allowedStr = implode(', ', $this->allowedExtensions);
            throw new \InvalidArgumentException("Formato de archivo no permitido (.{$ext}). Extensiones aceptadas: {$allowedStr}.");
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath !== '' && file_exists($tmpPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                if ($mime && !in_array($mime, $this->allowedMimes, true)) {
                    // Fallback para SVG que a veces se detecta como text/plain o text/xml
                    if (!($ext === 'svg' && (str_contains($mime, 'xml') || str_contains($mime, 'svg') || str_contains($mime, 'plain')))) {
                        throw new \InvalidArgumentException("Tipo MIME no permitido ({$mime}). Solo se permiten imágenes.");
                    }
                }
            }
        }
    }

    /**
     * Sube y almacena un archivo proveniente de $_FILES.
     *
     * @param array $file Estructura $_FILES['campo']
     * @param string $prefix Prefijo para el nombre de archivo (ej. 'staff_', 'news_', 'project_')
     * @return string Nombre del archivo guardado (ej. 'staff_1725000000_abcd1234.jpg')
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function upload(array $file, string $prefix = 'media_'): string
    {
        $this->validate($file);
        $this->ensureDirectoryExists();

        $origName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $sanitizedPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'media_';
        $newFileName = $sanitizedPrefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath  = rtrim($this->uploadDir, '/') . '/' . $newFileName;

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (is_uploaded_file($tmpName)) {
            $success = move_uploaded_file($tmpName, $targetPath);
        } else {
            // Para pruebas unitarias con archivos temporales locales
            $success = @copy($tmpName, $targetPath);
        }

        if (!$success) {
            throw new \RuntimeException('No se pudo mover el archivo subido al directorio de destino.');
        }

        return $newFileName;
    }

    /**
     * Retorna la URL pública y accesible para una imagen o su fallback.
     */
    public function getUrl(?string $path, string $fallbackType = ''): string
    {
        $fallbacks = [
            'proyecto' => '/assets/images/proyecto-1.jpg',
            'staff'    => '/assets/images/staff-1.jpg',
            'noticia'  => '/assets/images/noticia-1.jpg',
            'servicio' => '/assets/images/proyecto-1.jpg',
        ];

        if ($path === null || trim($path) === '') {
            return $fallbacks[$fallbackType] ?? '/assets/images/proyecto-1.jpg';
        }

        $path = trim($path);

        // URLs absolutas externas o rutas directas a assets
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/assets/')) {
            return $path;
        }

        // Si ya incluye /uploads/ o /storage/uploads/, normalizar a urlPrefix
        if (str_starts_with($path, '/uploads/')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/uploads/')) {
            $cleanName = basename($path);
            return rtrim($this->urlPrefix, '/') . '/' . $cleanName;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->urlPrefix, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Elimina un archivo del directorio de subidas si existe.
     */
    public function delete(string $filename): bool
    {
        $clean = basename($filename);
        $target = rtrim($this->uploadDir, '/') . '/' . $clean;
        if (file_exists($target) && is_file($target)) {
            return @unlink($target);
        }
        return false;
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }
    }
}
