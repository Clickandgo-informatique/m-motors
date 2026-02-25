<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageProcessor
{
    private string $tempDir;

    /** Extensions autorisées */
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    /** Types MIME autorisés */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct()
    {
        $this->tempDir = realpath(__DIR__ . '/../../var/tmp_uploads') ?: __DIR__ . '/../../var/tmp_uploads';
        $this->ensureDirectory($this->tempDir);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("Le dossier n'est pas accessible en écriture : $dir");
        }
    }

    /**
     * Génère automatiquement le filtre HTML pour <input accept="">
     * Exemple : ".jpg,.jpeg,.png,.webp"
     */
    public function getHtmlAcceptFilter(): string
    {
        return '.' . implode(',.', $this->allowedExtensions);
    }

    /**
     * Vérifie que l'image respecte les extensions et MIME autorisés
     */
    private function validateFile(UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if (!in_array($ext, $this->allowedExtensions, true)) {
            throw new \RuntimeException("Extension non autorisée : .$ext");
        }

        if (!in_array($mime, $this->allowedMimeTypes, true)) {
            throw new \RuntimeException("Type MIME non autorisé : $mime");
        }
    }

    /**
     * Traite une image : redimensionnement, compression, miniature, JPEG + WebP, aspect-ratio optionnel.
     * Le paramètre $destination est désormais OBLIGATOIRE.
     */
    public function process(
        UploadedFile $file,
        string $destination,          // ← obligatoire
        int $maxWidth = 1600,
        bool $forceAspectRatio = false,
        float $aspectRatio = 16 / 9
    ): array {
        // 1) Validation de l'image
        $this->validateFile($file);

        // 2) Préparation du dossier de destination
        $destination = trim($destination, '/');
        $uploadDir = realpath(__DIR__ . '/../../public/uploads/' . $destination)
            ?: __DIR__ . '/../../public/uploads/' . $destination;

        $this->ensureDirectory($uploadDir);

        // 3) Copie brute dans le dossier temporaire
        $extension = strtolower($file->guessExtension() ?? 'jpg');
        $tempName = uniqid('tmp_', true) . '.' . $extension;
        $file->move($this->tempDir, $tempName);
        $tempPath = $this->tempDir . '/' . $tempName;

        // 4) Charge l’image
        $content = file_get_contents($tempPath);
        $image = @imagecreatefromstring($content);

        if (!$image) {
            unlink($tempPath);
            throw new \RuntimeException("Impossible de lire l'image.");
        }

        unlink($tempPath);

        // 5) Fingerprint
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) ?: 'image';
        $hash = substr(md5(uniqid('', true)), 0, 8);

        $baseName = "{$safeName}_{$hash}";

        // 6) Aspect-ratio forcé (optionnel)
        if ($forceAspectRatio) {
            $image = $this->cropToAspectRatio($image, $aspectRatio);
        }

        // 7) Redimensionnement principal
        $resized = $this->resize($image, $maxWidth);

        // 8) Miniature
        $thumb = $this->resize($image, 300);

        // 9) Sauvegarde JPEG + WebP
        $jpg = $baseName . '.jpg';
        $webp = $baseName . '.webp';
        $jpgThumb = $baseName . '_thumb.jpg';
        $webpThumb = $baseName . '_thumb.webp';

        $this->saveJpeg($resized, $uploadDir . '/' . $jpg);
        $this->saveWebp($resized, $uploadDir . '/' . $webp);

        $this->saveJpeg($thumb, $uploadDir . '/' . $jpgThumb);
        $this->saveWebp($thumb, $uploadDir . '/' . $webpThumb);

        return [
            'jpg' => $jpg,
            'webp' => $webp,
            'jpg_thumb' => $jpgThumb,
            'webp_thumb' => $webpThumb,
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
            return imagecrop($image, ['x' => $x, 'y' => 0, 'width' => $newWidth, 'height' => $height]);
        }

        $newHeight = (int) ($width / $ratio);
        $y = (int) (($height - $newHeight) / 2);
        return imagecrop($image, ['x' => 0, 'y' => $y, 'width' => $width, 'height' => $newHeight]);
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

    private function saveJpeg($image, string $path): void
    {
        imagejpeg($image, $path, 75);
    }

    private function saveWebp($image, string $path): void
    {
        imagewebp($image, $path, 80);
    }
    
    // Suppression des images
    public function delete(string $filename, string $destination): void
    {
        $destination = trim($destination, '/');
        $uploadDir = realpath(__DIR__ . '/../../public/uploads/' . $destination)
            ?: __DIR__ . '/../../public/uploads/' . $destination;

        $this->ensureDirectory($uploadDir);

        // Base name sans extension
        $base = pathinfo($filename, PATHINFO_FILENAME);

        $filesToDelete = [
            $base . '.jpg',
            $base . '.webp',
            $base . '_thumb.jpg',
            $base . '_thumb.webp',
        ];

        foreach ($filesToDelete as $file) {
            $path = $uploadDir . '/' . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
