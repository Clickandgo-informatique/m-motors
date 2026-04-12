<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierType;
use App\Enum\DossierStatus;
use App\Enum\FinancingType;
use App\Enum\VehicleStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: DossierType::class)]
    private DossierType $type;

    #[ORM\Column(enumType: FinancingType::class, nullable: true)]
    private ?FinancingType $financingType = null;

    #[ORM\Column(enumType: DossierStatus::class)]
    private DossierStatus $status = DossierStatus::DRAFT;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Vehicle $vehicle;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    // ================= GETTERS =================

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

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    // ================= SETTERS =================

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

    // ================= BUSINESS LOGIC =================

    public function submit(): void
    {
        if (!$this->vehicle->isAvailable()) {
            throw new \LogicException('Véhicule indisponible');
        }

        $this->status = DossierStatus::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();

        $this->vehicle->setStatus(VehicleStatus::RESERVED);
    }

    public function approve(): void
    {
        $this->status = DossierStatus::APPROVED;
        $this->processedAt = new \DateTimeImmutable();

        if ($this->isLeasing()) {
            $this->vehicle->setStatus(VehicleStatus::RENTED);
        } else {
            $this->vehicle->setStatus(VehicleStatus::SOLD);
        }
    }

    public function reject(): void
    {
        $this->status = DossierStatus::REJECTED;
        $this->processedAt = new \DateTimeImmutable();

        if ($this->vehicle->isReserved()) {
            $this->vehicle->setStatus(VehicleStatus::AVAILABLE);
        }
    }

    // ================= HELPERS =================

    public function isLeasing(): bool
    {
        return in_array($this->financingType, [
            FinancingType::LOA,
            FinancingType::LLD
        ], true);
    }
}
