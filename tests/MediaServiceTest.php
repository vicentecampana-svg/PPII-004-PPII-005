<?php

declare(strict_types=1);

namespace Tests;

use App\Services\MediaService;
use PHPUnit\Framework\TestCase;

final class MediaServiceTest extends TestCase
{
    private string $testUploadDir;
    private MediaService $mediaService;

    protected function setUp(): void
    {
        $this->testUploadDir = sys_get_temp_dir() . '/test_uploads_' . bin2hex(random_bytes(4));
        mkdir($this->testUploadDir, 0777, true);

        $this->mediaService = new MediaService(
            $this->testUploadDir,
            '/uploads',
            1024 * 1024 // 1 MB para tests
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testUploadDir)) {
            $files = glob($this->testUploadDir . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->testUploadDir);
        }
    }

    public function testUploadValidImage(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
        // Crear un PNG válido 1x1
        file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $fileData = [
            'name'     => 'avatar.png',
            'type'     => 'image/png',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpFile),
        ];

        $savedFilename = $this->mediaService->upload($fileData, 'staff_');

        $this->assertStringStartsWith('staff_', $savedFilename);
        $this->assertStringEndsWith('.png', $savedFilename);
        $this->assertFileExists($this->testUploadDir . '/' . $savedFilename);

        $url = $this->mediaService->getUrl($savedFilename);
        $this->assertSame('/uploads/' . $savedFilename, $url);
    }

    public function testUploadRejectsDisallowedExtension(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'script_');
        file_put_contents($tmpFile, '<?php echo "evil"; ?>');

        $fileData = [
            'name'     => 'shell.php',
            'type'     => 'text/plain',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpFile),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato de archivo no permitido');

        try {
            $this->mediaService->upload($fileData, 'danger_');
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testUploadRejectsOversizedFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'huge_');
        // Crear archivo de 2MB cuando el límite del test es 1MB
        $fp = fopen($tmpFile, 'w');
        fseek($fp, (2 * 1024 * 1024) - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $fileData = [
            'name'     => 'huge.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpFile),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('excede el tamaño máximo');

        try {
            $this->mediaService->upload($fileData, 'big_');
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testMediaUrlHelper(): void
    {
        $this->assertSame('/assets/images/proyecto-1.jpg', mediaUrl(null, 'proyecto'));
        $this->assertSame('/assets/images/staff-1.jpg', mediaUrl('', 'staff'));
        $this->assertSame('/assets/images/noticia-1.jpg', mediaUrl('', 'noticia'));
        $this->assertSame('https://externo.cl/foto.jpg', mediaUrl('https://externo.cl/foto.jpg'));
        $this->assertSame('/assets/images/logo.png', mediaUrl('/assets/images/logo.png'));
        $this->assertSame('/uploads/foto.png', mediaUrl('foto.png'));
        $this->assertSame('/uploads/foto.png', mediaUrl('/storage/uploads/foto.png'));
        $this->assertSame('/uploads/foto.png', mediaUrl('/uploads/foto.png'));
    }

    public function testDeleteFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'del_');
        file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $fileData = [
            'name'     => 'todelete.png',
            'type'     => 'image/png',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpFile),
        ];

        $saved = $this->mediaService->upload($fileData, 'test_');
        $this->assertFileExists($this->testUploadDir . '/' . $saved);

        $deleted = $this->mediaService->delete($saved);
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($this->testUploadDir . '/' . $saved);
    }
}
