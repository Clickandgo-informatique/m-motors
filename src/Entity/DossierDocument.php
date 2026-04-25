<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierDocumentStatus;
use App\Enum\DossierDocumentType;
use App\Repository\DossierDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierDocumentRepository::class)]
class DossierDocument
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    #[ORM\Column(enumType: DossierDocumentType::class)]
    #[Assert\NotNull]
    private DossierDocumentType $documentType = DossierDocumentType::UPLOAD;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 500)]
    private ?string $path = null;

    #[ORM\Column(enumType: DossierDocumentStatus::class)]
    private DossierDocumentStatus $status = DossierDocumentStatus::UPLOADED;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    // GETTERS / SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getDocumentType(): DossierDocumentType
    {
        return $this->documentType;
    }

    public function setDocumentType(DossierDocumentType $type): self
    {
        $this->documentType = $type;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $name): self
    {
        $this->originalName = $name;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getStatus(): DossierDocumentStatus
    {
        return $this->status;
    }

    public function setStatus(DossierDocumentStatus $status): self
    {
        $this->status = $status;

        if ($status === DossierDocumentStatus::VALIDATED) {
            $this->validatedAt = new \DateTimeImmutable();
        }

        return $this;
    }
}
