<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use App\Enum\DossierType;
use App\Enum\DossierStatus;
use App\Enum\FinancingType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    // ========================= IDENTIFIANT =========================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ========================= TYPE =========================
    // Achat ou financement

    #[ORM\Column(enumType: DossierType::class)]
    #[Assert\NotNull(message: 'Le type de dossier est obligatoire.')]
    private DossierType $type;

    // ========================= FINANCEMENT =========================
    // Crédit / LOA / LLD / comptant

    #[ORM\Column(enumType: FinancingType::class, nullable: true)]
    private ?FinancingType $financingType = null;

    // ========================= STATUT =========================

    #[ORM\Column(enumType: DossierStatus::class)]
    private DossierStatus $status = DossierStatus::DRAFT;

    // ========================= RELATIONS =========================

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le véhicule est obligatoire.')]
    private ?Vehicle $vehicle = null;

    // ========================= DATES =========================

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    // ========================= DONNEES FINANCEMENT =========================
    // Utilisées pour LLD / LOA

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La durée doit être positive.')]
    private ?int $duration = null; // en mois

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Le kilométrage doit être positif.')]
    private ?int $annualMileage = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La mensualité doit être positive.')]
    private ?float $monthlyPayment = null;

    // ========================= LIFECYCLE =========================

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ========================= GETTERS =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): DossierType
    {
        return $this->type;
    }

    public function getFinancingType(): ?FinancingType
    {
        return $this->financingType;
    }

    public function getStatus(): DossierStatus
    {
        return $this->status;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getAnnualMileage(): ?int
    {
        return $this->annualMileage;
    }

    public function getMonthlyPayment(): ?float
    {
        return $this->monthlyPayment;
    }

    // ========================= SETTERS CONTROLES =========================
    // Pas de setStatus volontaire (piloté par logique métier)

    public function setType(DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setFinancingType(?FinancingType $financingType): self
    {
        $this->financingType = $financingType;
        return $this;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function setVehicle(Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function setAnnualMileage(?int $annualMileage): self
    {
        $this->annualMileage = $annualMileage;
        return $this;
    }

    public function setMonthlyPayment(?float $monthlyPayment): self
    {
        $this->monthlyPayment = $monthlyPayment;
        return $this;
    }

    // ========================= LOGIQUE METIER =========================

    /**
     * Soumission du dossier
     */
    public function submit(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::SUBMITTED)) {
            throw new \LogicException('Transition invalide');
        }

        // Validation spécifique financement (LLD / LOA)
        if ($this->isLeasing()) {
            if (!$this->duration || !$this->monthlyPayment) {
                throw new \LogicException('Informations de financement incomplètes');
            }
        }

        $this->status = DossierStatus::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();
    }

    /**
     * Validation admin
     */
    public function approve(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::APPROVED)) {
            throw new \LogicException('Transition invalide');
        }

        $this->status = DossierStatus::APPROVED;
        $this->processedAt = new \DateTimeImmutable();
    }

    /**
     * Refus admin
     */
    public function reject(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::REJECTED)) {
            throw new \LogicException('Transition invalide');
        }

        $this->status = DossierStatus::REJECTED;
        $this->processedAt = new \DateTimeImmutable();
    }

    // ========================= HELPERS =========================

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isSubmitted(): bool
    {
        return $this->status->isSubmitted();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function isFinancing(): bool
    {
        return $this->type->isFinancing();
    }

    public function isLeasing(): bool
    {
        return in_array($this->financingType, [
            FinancingType::LOA,
            FinancingType::LLD
        ], true);
    }

    public function isLLD(): bool
    {
        return $this->financingType === FinancingType::LLD;
    }

    public function isLOA(): bool
    {
        return $this->financingType === FinancingType::LOA;
    }
}
