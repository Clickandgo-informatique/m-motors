<?php

namespace App\Entity;

use App\Enum\DossierDocumentStatus;
use App\Enum\DossierDocumentType;
use App\Repository\DossierDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DossierDocument
{
    // =========================================================
    // IDENTIFIANT
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================
    // RELATION DOSSIER
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Dossier $dossier = null;

    // =========================================================
    // TYPE DE DOCUMENT (ex: CNI, permis, justificatif...)
    // =========================================================

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $type = null;

    // =========================================================
    // NOM ORIGINAL DU FICHIER
    // =========================================================

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $originalName = null;

    // =========================================================
    // NOM STOCKÉ (filesystem)
    // =========================================================

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $fileName = null;

    // =========================================================
    // CHEMIN RELATIF (uploads/...)
    // =========================================================

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $path = null;

    // =========================================================
    // STATUT DU DOCUMENT (UPLOAD / VALIDATION)
    // =========================================================

    #[ORM\Column(enumType: DossierDocumentStatus::class)]
    private DossierDocumentStatus $status = DossierDocumentStatus::UPLOADED;

    // Type de document traité
    #[ORM\Column(enumType: DossierDocumentType::class)]
    private DossierDocumentType $documentType;

    // =========================================================
    // TIMESTAMPS (optionnel si trait déjà global)
    // =========================================================

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    // =========================================================
    // GETTERS / SETTERS
    // =========================================================

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


    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    // =========================================================
    // LIFECYCLE
    // =========================================================

    #[ORM\PrePersist]
    public function initCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }






    /**
     * Get the value of documentType
     */
    public function getDocumentType(): DossierDocumentType
    {
        return $this->documentType;
    }

    /**
     * Set the value of documentType
     */
    public function setDocumentType(DossierDocumentType $documentType): self
    {
        $this->documentType = $documentType;

        return $this;
    }
}
