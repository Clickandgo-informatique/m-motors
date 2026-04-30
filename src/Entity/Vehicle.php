<?php

namespace App\Entity;

use App\Entity\Feature;
use App\Entity\FuelType;
use App\Entity\Gear;
use App\Entity\Maintenance;
use App\Entity\Rental;
use App\Entity\Sale;
use App\Entity\Supplier;
use App\Entity\VehicleModel;
use App\Entity\Favorite;
use App\Entity\Color;
use App\Entity\Dossier;
use App\Entity\Traits\TimestampableTrait;
use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Vehicle
{
    use TimestampableTrait;
    // =========================================================
    // IDENTIFIANT
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // =========================================================
    // STATUS (Workflow uniquement)
    // =========================================================

    #[ORM\Column(enumType: VehicleStatus::class)]
    private VehicleStatus $status = VehicleStatus::AVAILABLE_FOR_SALE;

    public function getStatus(): VehicleStatus
    {
        return $this->status;
    }

    public function setStatus(VehicleStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    // =========================================================
    // IDENTIFIANTS
    // =========================================================

    #[ORM\Column(length: 17, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 17)]
    #[Assert\Regex(pattern: '/^[A-HJ-NPR-Z0-9]{17}$/')]
    private ?string $vin = null;

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(string $vin): self
    {
        $this->vin = strtoupper(trim($vin));
        return $this;
    }

    #[ORM\Column(length: 15, unique: true, nullable: true)]
    private ?string $registrationNumber = null;

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber
            ? strtoupper(trim($registrationNumber))
            : null;

        return $this;
    }

    // =========================================================
    // DATES
    // =========================================================

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $firstRegistrationDate = null;

    public function getFirstRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(?\DateTimeImmutable $date): self
    {
        $this->firstRegistrationDate = $date;
        return $this;
    }

    // =========================================================
    // TECHNIQUE
    // =========================================================

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): self
    {
        $this->mileage = $mileage;
        return $this;
    }

    #[ORM\Column(type: 'integer')]
    private ?int $price = null;

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    // =========================================================
    // RELATIONS
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?VehicleModel $vehicleModel = null;

    public function getVehicleModel(): ?VehicleModel
    {
        return $this->vehicleModel;
    }

    public function setVehicleModel(?VehicleModel $vehicleModel): self
    {
        $this->vehicleModel = $vehicleModel;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

    // =========================================================
    // COLLECTIONS
    // =========================================================

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Dossier::class)]
    private Collection $dossiers;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Maintenance::class)]
    private Collection $maintenances;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Rental::class)]
    private Collection $rentals;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Sale::class)]
    private Collection $sales;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'vehicles')]
    private Collection $features;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->features = new ArrayCollection();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public function isAvailableForSale(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE_FOR_SALE;
    }

    public function isAvailableForRent(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE_FOR_RENT;
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [
            VehicleStatus::RESERVED,
            VehicleStatus::RENTED,
            VehicleStatus::SOLD,
            VehicleStatus::MAINTENANCE
        ], true);
    }
}
