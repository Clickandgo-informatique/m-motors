<?php

namespace App\Service;

use App\Service\Utils\SluggerService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DossierUploadService
{
    public function __construct(
        private string $uploadDir,
        private Filesystem $filesystem,
        private SluggerService $slugger
    ) {}

    /**
     * Upload un fichier lié à un dossier
     *
     * @param UploadedFile $file
     * @param string $folder ex: dossier_12/documents_identity
     * @return array{filename: string, path: string, originalName: string}
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        $this->filesystem->mkdir($this->uploadDir . '/' . $folder);

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slugify($originalName);

        $extension = $file->guessExtension()
            ?? $file->getClientOriginalExtension()
            ?? 'bin';

        $newName = $safeName . '-' . bin2hex(random_bytes(16)) . '.' . $extension;

        $file->move($this->uploadDir . '/' . $folder, $newName);

        return [
            'filename' => $newName,
            'path' => $folder . '/' . $newName,
            'originalName' => $file->getClientOriginalName(),
        ];
    }
}
