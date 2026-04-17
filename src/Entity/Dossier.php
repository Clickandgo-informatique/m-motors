<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierType;
use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant un dossier métier.
 *
 * Le statut est piloté par Symfony Workflow (state machine).
 * Le champ `status` contient l'état courant.
 *
 * Le champ `dossierCode` est l'identifiant métier unique :
 * Exemple : DUP001-0001
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    use TimestampableTrait;

    // =========================================================
    // IDENTIFIANT TECHNIQUE
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================
    // RELATIONS MÉTIER
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un client est obligatoire')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    // =========================================================
    // TYPE DE DOSSIER
    // =========================================================

    #[ORM\Column(enumType: DossierType::class)]
    private ?DossierType $type = null;

    // =========================================================
    // DOSSIER CODE (IDENTIFIANT MÉTIER UNIQUE)
    // =========================================================

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $dossierCode = null;

    // =========================================================
    // WORKFLOW STATUS
    // =========================================================

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private string $status = 'draft';

    // =========================================================
    // FINANCEMENT
    // =========================================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $financingType = null;

    // =========================================================
    // DATES MÉTIER
    // =========================================================

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    // =========================================================
    // DOCUMENTS
    // =========================================================

    /**
     * @var Collection<int, DossierDocument>
     */
    #[ORM\OneToMany(
        mappedBy: 'dossier',
        targetEntity: DossierDocument::class,
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $documents;

    // =========================================================
    // CONSTRUCTOR
    // =========================================================

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    // =========================================================
    // GETTERS / SETTERS
    // =========================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    // =========================================================
    // TYPE
    // =========================================================

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    // =========================================================
    // DOSSIER CODE
    // =========================================================

    public function getDossierCode(): ?string
    {
        return $this->dossierCode;
    }

    public function setDossierCode(string $dossierCode): self
    {
        $this->dossierCode = $dossierCode;
        return $this;
    }

    // =========================================================
    // STATUS WORKFLOW
    // =========================================================

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    // =========================================================
    // FINANCEMENT
    // =========================================================

    public function getFinancingType(): ?string
    {
        return $this->financingType;
    }

    public function setFinancingType(?string $financingType): self
    {
        $this->financingType = $financingType;
        return $this;
    }

    // =========================================================
    // DATES
    // =========================================================

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    // =========================================================
    // DOCUMENTS
    // =========================================================

    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(DossierDocument $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDossier($this);
        }

        return $this;
    }

    public function removeDocument(DossierDocument $document): self
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getDossier() === $this) {
                $document->setDossier(null);
            }
        }

        return $this;
    }
    // Gestion des badges
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_REJECTED => 'Refusé',
            default => $this->status,
        };
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_VALIDATED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
