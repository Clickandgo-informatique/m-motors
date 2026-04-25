<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierType;
use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    // TYPE
    // =========================================================

    #[ORM\Column(enumType: DossierType::class)]
    private ?DossierType $type = null;

    // =========================================================
    // BUSINESS CODE
    // =========================================================

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $dossierCode = null;

    // =========================================================
    // WORKFLOW STATUS (SYMFONY OWNER)
    // =========================================================

    #[ORM\Column(length: 50)]
    private string $status = 'draft';

    // =========================================================
    // METIER
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
    // GETTERS
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

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
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

    public function getFinancingType(): ?string
    {
        return $this->financingType;
    }

    public function setFinancingType(?string $financingType): self
    {
        $this->financingType = $financingType;
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

    // =========================================================
    // UI HELPERS
    // =========================================================

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'vehicle_selected' => 'Véhicule sélectionné',
            'documents_pending' => 'Documents à fournir',
            'documents_review' => 'Documents en validation',
            'financing_review' => 'Financement en cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'vehicle_selected' => 'info',
            'documents_pending' => 'warning',
            'documents_review' => 'warning',
            'financing_review' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
