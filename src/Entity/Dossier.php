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
#[ORM\Table(name: 'dossier')]
class Dossier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: DossierType::class)]
    #[Assert\NotNull]
    private DossierType $type;

    #[ORM\Column(enumType: FinancingType::class, nullable: true)]
    private ?FinancingType $financingType = null;

    #[ORM\Column(enumType: DossierStatus::class)]
    private DossierStatus $status = DossierStatus::DRAFT;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Vehicle $vehicle;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(nullable: true)]
    private ?int $annualMileage = null;

    #[ORM\Column(nullable: true)]
    private ?float $monthlyPayment = null;

    #[ORM\PrePersist]
    public function init(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ---------------- GETTERS ----------------

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

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // ---------------- SETTERS ----------------

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

    // ---------------- BUSINESS LOGIC ----------------

    public function submit(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::SUBMITTED)) {
            throw new \LogicException('Transition invalide');
        }

        $this->status = DossierStatus::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function approve(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::APPROVED)) {
            throw new \LogicException('Transition invalide');
        }

        $this->status = DossierStatus::APPROVED;
        $this->processedAt = new \DateTimeImmutable();
    }

    public function reject(): void
    {
        if (!$this->status->canTransitionTo(DossierStatus::REJECTED)) {
            throw new \LogicException('Transition invalide');
        }

        $this->status = DossierStatus::REJECTED;
        $this->processedAt = new \DateTimeImmutable();
    }

    // ---------------- HELPERS ----------------

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

    public function isLeasing(): bool
    {
        return in_array($this->financingType, [
            FinancingType::LOA,
            FinancingType::LLD
        ], true);
    }
}
