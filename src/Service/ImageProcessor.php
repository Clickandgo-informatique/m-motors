<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de traitement des images :
 * - validation
 * - resize
 * - crop optionnel
 * - conversion WebP
 * - génération thumbnail
 */
class ImageProcessor
{
    /**
     * Dossier temporaire (non utilisé pour move Symfony)
     */
    private string $tempDir;

    /**
     * Extensions autorisées
     */
    private array $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    /**
     * MIME types autorisés
     */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly string $uploadDir
    ) {
        $this->tempDir = realpath(__DIR__ . '/../../var/tmp_uploads')
            ?: __DIR__ . '/../../var/tmp_uploads';

        $this->ensureDirectory($this->tempDir);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("Dossier non accessible en écriture : $dir");
        }
    }

    public function getHtmlAcceptFilter(): string
    {
        return '.' . implode(',.', $this->allowedExtensions);
    }

    private function validateFile(UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if (!in_array($ext, $this->allowedExtensions, true)) {
            throw new \RuntimeException("Extension non autorisée : .$ext");
        }

        if (!in_array($mime, $this->allowedMimeTypes, true)) {
            throw new \RuntimeException("MIME non autorisé : $mime");
        }
    }

    /**
     * Traitement image
     */
    public function process(
        UploadedFile $file,
        string $destination,
        int $maxWidth = 1600,
        bool $forceAspectRatio = false,
        float $aspectRatio = 16 / 9
    ): array {
        $this->validateFile($file);

        $destination = trim($destination, '/');

        $uploadDir = sprintf(
            '%s/%s',
            rtrim($this->uploadDir, '/'),
            $destination
        );

        $this->ensureDirectory($uploadDir);

        $tempPath = $file->getPathname();

        $content = file_get_contents($tempPath);

        $image = @imagecreatefromstring($content);

        if (!$image) {
            throw new \RuntimeException("Image invalide ou illisible");
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) ?: 'image';

        $hash = substr(md5(uniqid('', true)), 0, 8);

        $baseName = $safeName . '_' . $hash;

        if ($forceAspectRatio) {
            $image = $this->cropToAspectRatio($image, $aspectRatio);
        }

        $resized = $this->resize($image, $maxWidth);
        $thumb = $this->resize($image, 300);

        $filename = $baseName . '.webp';
        $thumbnail = $baseName . '_thumb.webp';

        $this->saveWebp($resized, $uploadDir . '/' . $filename);
        $this->saveWebp($thumb, $uploadDir . '/' . $thumbnail);

        return [
            'filename' => $filename,
            'thumbnail' => $thumbnail,
            'path' => $destination . '/' . $filename
        ];
    }

    private function cropToAspectRatio($image, float $ratio)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $currentRatio = $width / $height;

        if ($currentRatio > $ratio) {
            $newWidth = (int) ($height * $ratio);
            $x = (int) (($width - $newWidth) / 2);

            $cropped = imagecrop($image, [
                'x' => $x,
                'y' => 0,
                'width' => $newWidth,
                'height' => $height
            ]);
        } else {
            $newHeight = (int) ($width / $ratio);
            $y = (int) (($height - $newHeight) / 2);

            $cropped = imagecrop($image, [
                'x' => 0,
                'y' => $y,
                'width' => $width,
                'height' => $newHeight
            ]);
        }

        if ($cropped === false) {
            throw new \RuntimeException("Crop impossible");
        }

        return $cropped;
    }

    private function resize($image, int $maxWidth)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $ratio = $height / $width;

        $newWidth = $maxWidth;
        $newHeight = (int) ($maxWidth * $ratio);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresampled(
            $newImage,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        return $newImage;
    }

    private function saveWebp($image, string $path): void
    {
        imagewebp($image, $path, 80);
    }

    public function delete(string $filename, string $destination): void
    {
        $destination = trim($destination, '/');

        $uploadDir = sprintf(
            '%s/%s',
            rtrim($this->uploadDir, '/'),
            $destination
        );

        $this->ensureDirectory($uploadDir);

        $base = pathinfo($filename, PATHINFO_FILENAME);

        $files = [
            $base . '.webp',
            $base . '_thumb.webp',
        ];

        foreach ($files as $file) {
            $path = $uploadDir . '/' . $file;

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
    public function processFromPath(
        string $filePath,
        string $destination,
        int $maxWidth = 1600,
        bool $forceAspectRatio = false,
        float $aspectRatio = 16 / 9
    ): array {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("fichier introuvable : $filePath");
        }

        $content = file_get_contents($filePath);

        $image = @imagecreatefromstring($content);

        if (!$image) {
            throw new \RuntimeException("image invalide ou illisible : $filePath");
        }

        $destination = trim($destination, '/');

        $uploadDir = sprintf(
            '%s/%s',
            rtrim($this->uploadDir, '/'),
            $destination
        );

        $this->ensureDirectory($uploadDir);

        $originalName = pathinfo($filePath, PATHINFO_FILENAME);

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) ?: 'image';

        $hash = substr(md5(uniqid('', true)), 0, 8);

        $baseName = $safeName . '_' . $hash;

        if ($forceAspectRatio) {
            $image = $this->cropToAspectRatio($image, $aspectRatio);
        }

        $resized = $this->resize($image, $maxWidth);
        $thumb = $this->resize($image, 300);

        $filename = $baseName . '.webp';
        $thumbnail = $baseName . '_thumb.webp';

        $this->saveWebp($resized, $uploadDir . '/' . $filename);
        $this->saveWebp($thumb, $uploadDir . '/' . $thumbnail);

        return [
            'filename' => $filename,
            'thumbnail' => $thumbnail,
            'path' => $destination . '/' . $filename
        ];
    }
}
