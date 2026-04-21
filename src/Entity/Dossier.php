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
 * Dossier métier principal.
 *
 * Responsabilités :
 * - gérer le cycle de vie du dossier
 * - contrôler la complétude des documents
 * - déléguer les actions véhicule au DossierType
 *
 * IMPORTANT :
 * Le status ne doit jamais être modifié directement.
 * Utiliser uniquement :
 * - submit()
 * - validate()
 * - reject()
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    use TimestampableTrait;

    // =========================================================
    // IDENTIFIANT
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================
    // RELATIONS
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne]
    private ?User $validatedBy = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    // =========================================================
    // TYPE DOSSIER
    // =========================================================

    #[ORM\Column(enumType: DossierType::class)]
    private ?DossierType $type = null;

    // =========================================================
    // IDENTIFIANT MÉTIER
    // =========================================================

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $dossierCode = null;

    // =========================================================
    // STATUT WORKFLOW
    // =========================================================

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_DRAFT;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    // =========================================================
    // MÉTIER
    // =========================================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $financingType = null;

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
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DossierDocument::class, cascade: ['persist'])]
    private Collection $documents;

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

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDossierCode(): ?string
    {
        return $this->dossierCode;
    }

    public function setDossierCode(string $code): self
    {
        $this->dossierCode = $code;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }
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
    // MÉTIER - DOCUMENTS
    // =========================================================

    public function isComplete(array $requiredTypes): bool
    {
        $uploaded = array_map(
            fn($doc) => $doc->getType(),
            $this->documents->toArray()
        );

        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploaded, true)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================
    // SUBMIT
    // =========================================================

    public function submit(array $requiredTypes, User $manager): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \LogicException('Seul un brouillon peut être soumis');
        }

        if (!$this->type) {
            throw new \LogicException('Type de dossier manquant');
        }

        if (!$this->isComplete($requiredTypes)) {
            throw new \LogicException('Dossier incomplet');
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->assignedTo = $manager;

        if ($this->vehicle) {
            $this->type->applyVehicleOnSubmit($this->vehicle);
        }
    }

    // =========================================================
    // VALIDATE
    // =========================================================

    public function validate(User $manager): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \LogicException('Validation impossible');
        }

        if (!$this->type) {
            throw new \LogicException('Type de dossier manquant');
        }

        $this->status = self::STATUS_VALIDATED;
        $this->validatedBy = $manager;
        $this->completedAt = new \DateTimeImmutable();

        if ($this->vehicle) {
            $this->type->applyVehicleValidation($this->vehicle);
        }
    }

    // =========================================================
    // REJECT
    // =========================================================

    public function reject(User $manager): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \LogicException('Refus impossible');
        }

        if (!$this->type) {
            throw new \LogicException('Type de dossier manquant');
        }

        $this->status = self::STATUS_REJECTED;
        $this->validatedBy = $manager;
        $this->cancelledAt = new \DateTimeImmutable();

        if ($this->vehicle) {
            $this->type->applyVehicleRejection($this->vehicle);
        }
    }

    // =========================================================
    // UI
    // =========================================================

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

    /**
     * Set the value of status
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
