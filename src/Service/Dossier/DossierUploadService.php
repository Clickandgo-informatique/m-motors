<?php

namespace App\Service\Dossier;

use App\Entity\DossierDocument;
use App\Service\Utils\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DossierUploadService
{
    public function __construct(
        private string $uploadDir,
        private Filesystem $filesystem,
        private SluggerService $slugger
    ) {}

    // UPLOAD

    public function upload(UploadedFile $file, string $folder): array
    {
        $fullDir = $this->uploadDir . '/' . $folder;

        $this->filesystem->mkdir($fullDir);

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slugify($originalName);

        $extension = $file->guessExtension()
            ?: $file->getClientOriginalExtension()
            ?: 'bin';

        $newName = $safeName . '-' . bin2hex(random_bytes(16)) . '.' . $extension;

        $file->move($fullDir, $newName);

        return [
            'filename' => $newName,
            'path' => $folder . '/' . $newName,
            'originalName' => $file->getClientOriginalName(),
        ];
    }

    // DELETE FILE ONLY

    public function delete(string $path): void
    {
        $fullPath = $this->uploadDir . '/' . $path;

        if ($this->filesystem->exists($fullPath)) {
            $this->filesystem->remove($fullPath);
        }
    }

    public function safeDelete(?string $path): void
    {
        if (!$path) {
            return;
        }

        $this->delete($path);
    }

    public function deleteFromDocument(DossierDocument $document): void
    {
        $this->safeDelete($document->getPath());
    }

    // DELETE ENTITY + FILE

    public function deleteEntity(DossierDocument $document, EntityManagerInterface $em): void
    {
        $this->deleteFromDocument($document);
        $em->remove($document);
        $em->flush();
    }

    // REPLACE FILE 

    public function replace(
        DossierDocument $document,
        UploadedFile $file,
        string $folder,
        EntityManagerInterface $em
    ): DossierDocument {
        // suppression ancien fichier
        $this->deleteFromDocument($document);

        // upload nouveau fichier
        $upload = $this->upload($file, $folder);

        // mise à jour entité
        $document->setFileName($upload['filename']);
        $document->setOriginalName($upload['originalName']);
        $document->setPath($upload['path']);

        $em->persist($document);
        $em->flush();

        return $document;
    }
}
