<?php

namespace App\Entity;

use App\Entity\Customer;
use App\Entity\Vehicle;
use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierStatus;
use App\Enum\DossierType;
use App\Enum\FinancingType;
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
    // RÉFÉRENCE UNIQUE MÉTIER
    // =========================================================

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\Length(max: 50)]
    private ?string $reference = null;

    // =========================================================
    // RELATIONS
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Vehicle $vehicle = null;

    /**
     * Documents liés au dossier (pièces justificatives, etc.)
     *
     * @var Collection<int, DossierDocument>
     */
    #[ORM\OneToMany(
        mappedBy: 'dossier',
        targetEntity: DossierDocument::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $documents;

    // =========================================================
    // STATUT MÉTIER (ENUM)
    // =========================================================

    #[ORM\Column(enumType: DossierStatus::class)]
    private DossierStatus $status = DossierStatus::DRAFT;

    // =========================================================
    // WORKFLOW SYMFONY (SOURCE DE VÉRITÉ)
    // =========================================================

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['draft', 'in_progress', 'completed', 'cancelled'])]
    private string $workflowStatus = 'draft';

    // =========================================================
    // FINANCEMENT
    // =========================================================

    #[ORM\Column(enumType: FinancingType::class, nullable: true)]
    private ?FinancingType $financingType = null;

    // =========================================================
    // DATES MÉTIER
    // =========================================================

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    // =========================================================
    // TYPE DOSSIER
    // =========================================================

    #[ORM\Column(enumType: DossierType::class)]
    #[Assert\NotNull]
    private ?DossierType $type = null;

    // =========================================================
    // CONSTRUCTEUR
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
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

    // =========================================================
    // STATUS MÉTIER
    // =========================================================

    public function getStatus(): DossierStatus
    {
        return $this->status;
    }

    public function setStatus(DossierStatus $status): self
    {
        $this->status = $status;
        $this->workflowStatus = $status->value;

        return $this;
    }

    // =========================================================
    // WORKFLOW SYNC
    // =========================================================

    public function getWorkflowStatus(): string
    {
        return $this->workflowStatus;
    }

    public function setWorkflowStatus(string $workflowStatus): self
    {
        $this->workflowStatus = $workflowStatus;
        $this->status = DossierStatus::from($workflowStatus);

        return $this;
    }

    // =========================================================
    // FINANCEMENT
    // =========================================================

    public function getFinancingType(): ?FinancingType
    {
        return $this->financingType;
    }

    public function setFinancingType(?FinancingType $financingType): self
    {
        $this->financingType = $financingType;
        return $this;
    }

    // =========================================================
    // DATES MÉTIER
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
    // TYPE DOSSIER
    // =========================================================

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(?DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    // =========================================================
    // DOCUMENTS
    // =========================================================

    /**
     * @return Collection<int, DossierDocument>
     */
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

    // =========================================================
    // LIFECYCLE
    // =========================================================

    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if ($this->reference) {
            return;
        }

        $this->reference = sprintf(
            'DOS-%s-%s',
            date('Ymd'),
            strtoupper(bin2hex(random_bytes(3)))
        );
    }

    // =========================================================
    // UI HELPERS
    // =========================================================

    public function getStatusBadge(): string
    {
        return match ($this->workflowStatus) {
            'draft' => 'secondary',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'dark',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->workflowStatus) {
            'draft' => 'Brouillon',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }
}
