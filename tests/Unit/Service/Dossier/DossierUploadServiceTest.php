<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\DossierDocument;
use App\Service\Dossier\DossierUploadService;
use App\Service\Utils\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DossierUploadServiceTest extends TestCase
{
    public function testUploadWithGuessExtension(): void
    {
        // vérifie un upload standard avec extension détectée par guessExtension()

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('mkdir');

        $slugger = $this->createMock(SluggerService::class);
        $slugger->method('slugify')->willReturn('file');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('Mon fichier.pdf');
        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->expects($this->once())->method('move');

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $result = $service->upload($file, 'docs');

        $this->assertSame('Mon fichier.pdf', $result['originalName']);
        $this->assertStringContainsString('docs/file-', $result['path']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
    }

    public function testUploadFallbackToBinExtension(): void
    {
        // vérifie le fallback vers .bin lorsque aucune extension exploitable n'est fournie

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('mkdir');

        $slugger = $this->createMock(SluggerService::class);
        $slugger->method('slugify')->willReturn('file');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('Mon fichier');

        // on force l'absence d'extension exploitable
        $file->method('guessExtension')->willReturn(null);
        $file->method('getClientOriginalExtension')->willReturn('');

        $file->expects($this->once())->method('move');

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $result = $service->upload($file, 'docs');

        // vérifie que le fallback .bin est appliqué
        $this->assertStringEndsWith('.bin', $result['filename']);
    }

    public function testDeleteWhenFileExists(): void
    {
        // vérifie la suppression d'un fichier existant

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->expects($this->once())->method('remove');

        $slugger = $this->createMock(SluggerService::class);

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $service->delete('docs/file.pdf');
    }

    public function testDeleteWhenFileDoesNotExist(): void
    {
        // vérifie qu'aucune suppression n'est effectuée si le fichier n'existe pas

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(false);
        $filesystem->expects($this->never())->method('remove');

        $slugger = $this->createMock(SluggerService::class);

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $service->delete('docs/file.pdf');
    }

    public function testSafeDeleteWithNull(): void
    {
        // vérifie que safeDelete ignore une valeur null sans appel filesystem

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('exists');
        $filesystem->expects($this->never())->method('remove');

        $slugger = $this->createMock(SluggerService::class);

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $service->safeDelete(null);
    }

    public function testDeleteEntity(): void
    {
        // vérifie la suppression de l'entité et le flush Doctrine

        $document = new DossierDocument();
        $document->setPath('docs/file.pdf');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->expects($this->once())->method('remove');

        $slugger = $this->createMock(SluggerService::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove');
        $em->expects($this->once())->method('flush');

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $service->deleteEntity($document, $em);
    }

    public function testReplace(): void
    {
        // vérifie le remplacement d'un fichier avec mise à jour de l'entité

        $document = new DossierDocument();
        $document->setPath('docs/old.pdf');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->expects($this->once())->method('remove');

        $slugger = $this->createMock(SluggerService::class);
        $slugger->method('slugify')->willReturn('file');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('file.pdf');
        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->expects($this->once())->method('move');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $service = new DossierUploadService('/tmp', $filesystem, $slugger);

        $result = $service->replace($document, $file, 'docs', $em);

        $this->assertSame($document, $result);
    }
}
