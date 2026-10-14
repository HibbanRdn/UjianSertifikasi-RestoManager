<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CheckoutException;
use Closure;

final class MenuImageUploadService
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly string $uploadDirectory,
        private readonly string $publicPrefix,
        private readonly int $maxBytes,
        private readonly int $maxDimension,
        private readonly ?Closure $isUploadedFile = null,
        private readonly ?Closure $moveUploadedFile = null
    ) {
    }

    /** @param array<string, mixed>|null $file */
    public function store(?array $file): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new CheckoutException('Unggah foto gagal. Pilih ulang gambar lalu coba lagi.');
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        if (!is_string($temporaryPath) || $temporaryPath === '' || !is_file($temporaryPath)) {
            throw new CheckoutException('Berkas foto tidak ditemukan pada unggahan.');
        }
        $isUploaded = $this->isUploadedFile ?? static fn (string $path): bool => is_uploaded_file($path);
        if (!$isUploaded($temporaryPath)) {
            throw new CheckoutException('Sumber unggahan foto tidak valid.');
        }

        $actualSize = filesize($temporaryPath);
        $reportedSize = (int) ($file['size'] ?? 0);
        if ($actualSize === false || max($actualSize, $reportedSize) > $this->maxBytes) {
            throw new CheckoutException('Ukuran foto maksimal 5 MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        if (!is_string($mime) || !isset(self::ALLOWED_MIME[$mime])) {
            throw new CheckoutException('Format foto harus JPEG, PNG, atau WebP.');
        }
        $dimensions = getimagesize($temporaryPath);
        if ($dimensions === false || ($dimensions['mime'] ?? '') !== $mime) {
            throw new CheckoutException('Isi berkas bukan gambar yang valid.');
        }
        if ((int) $dimensions[0] > $this->maxDimension || (int) $dimensions[1] > $this->maxDimension) {
            throw new CheckoutException('Dimensi foto terlalu besar. Maksimal 6000 × 6000 piksel.');
        }

        if (!is_dir($this->uploadDirectory) && !mkdir($this->uploadDirectory, 0755, true) && !is_dir($this->uploadDirectory)) {
            throw new CheckoutException('Folder penyimpanan foto belum dapat dibuat.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME[$mime];
        $target = rtrim($this->uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $move = $this->moveUploadedFile ?? static fn (string $from, string $to): bool => move_uploaded_file($from, $to);
        if (!$move($temporaryPath, $target)) {
            throw new CheckoutException('Foto belum dapat disimpan. Silakan coba kembali.');
        }
        @chmod($target, 0644);

        return rtrim($this->publicPrefix, '/') . '/' . $filename;
    }

    public function remove(string $publicPath): void
    {
        $prefix = rtrim($this->publicPrefix, '/') . '/';
        if (!str_starts_with($publicPath, $prefix)) {
            return;
        }
        $filename = basename($publicPath);
        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) {
            return;
        }
        $target = rtrim($this->uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (is_file($target)) {
            unlink($target);
        }
    }
}
