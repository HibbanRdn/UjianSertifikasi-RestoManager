<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\CheckoutException;
use App\Services\MenuImageUploadService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MenuImageUploadServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/restomanager-upload-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public static function imageFormats(): array
    {
        return [['jpeg'], ['png'], ['webp']];
    }

    #[DataProvider('imageFormats')]
    public function testStoresValidatedImageWithRandomSafeName(string $format): void
    {
        $temporary = $this->makeImage($format);
        $service = $this->service();

        $path = $service->store(['error' => UPLOAD_ERR_OK, 'tmp_name' => $temporary, 'size' => filesize($temporary), 'name' => '../../attack.php']);

        self::assertMatchesRegularExpression('#^/uploads/menu/[a-f0-9]{32}\.(?:jpg|png|webp)$#', (string) $path);
        self::assertFileExists($this->directory . '/' . basename((string) $path));
        self::assertStringNotContainsString('attack', (string) $path);
    }

    public function testRejectsCorruptAndOversizedFiles(): void
    {
        $corrupt = $this->directory . '/corrupt.jpg';
        file_put_contents($corrupt, 'not-an-image');
        $service = $this->service();

        try {
            $service->store(['error' => UPLOAD_ERR_OK, 'tmp_name' => $corrupt, 'size' => filesize($corrupt)]);
            self::fail('Berkas rusak harus ditolak.');
        } catch (CheckoutException $exception) {
            self::assertStringContainsString('Format foto', $exception->getMessage());
        }

        $valid = $this->makeImage('png');
        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('maksimal 5 MB');
        $service->store(['error' => UPLOAD_ERR_OK, 'tmp_name' => $valid, 'size' => 5 * 1024 * 1024 + 1]);
    }

    public function testNoFileIsAnOptionalUpload(): void
    {
        self::assertNull($this->service()->store(['error' => UPLOAD_ERR_NO_FILE]));
    }

    private function service(): MenuImageUploadService
    {
        return new MenuImageUploadService(
            $this->directory,
            '/uploads/menu',
            5 * 1024 * 1024,
            6000,
            static fn (string $path): bool => is_file($path),
            static fn (string $from, string $to): bool => rename($from, $to)
        );
    }

    private function makeImage(string $format): string
    {
        if ($format === 'webp') {
            $path = $this->directory . '/source-' . bin2hex(random_bytes(3)) . '.webp';
            file_put_contents($path, base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEALmk0mk0iIiIiIgBoSygABc6zbAAA', true));

            return $path;
        }
        $image = imagecreatetruecolor(8, 8);
        imagefill($image, 0, 0, imagecolorallocate($image, 244, 97, 22));
        $path = $this->directory . '/source-' . bin2hex(random_bytes(3)) . '.' . ($format === 'jpeg' ? 'jpg' : $format);
        $function = 'image' . $format;
        $function($image, $path);
        imagedestroy($image);

        return $path;
    }
}
