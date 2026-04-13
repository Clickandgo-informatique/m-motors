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
use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ===============================
    // STATUS
    // ===============================
    #[ORM\Column(enumType: VehicleStatus::class)]
    private VehicleStatus $status = VehicleStatus::AVAILABLE;

    // ===============================
    // IDENTIFICATION
    // ===============================

    #[ORM\Column(length: 17, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 17)]
    #[Assert\Regex(pattern: '/^[A-HJ-NPR-Z0-9]{17}$/')]
    private ?string $vin = null;

    #[ORM\Column(length: 15, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-]{4,15}$/')]
    private ?string $registrationNumber = null;

    // ===============================
    // TECHNIQUE
    // ===============================

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Assert\LessThan(2000000)]
    private ?int $mileage = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $price = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $firstRegistrationDate = null;

    // ===============================
    // RELATIONS PRINCIPALES
    // ===============================

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleModel $vehicleModel = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

    // ===============================
    // MANY TO MANY
    // ===============================

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'vehicles')]
    private Collection $features;

    // ===============================
    // ONE TO MANY
    // ===============================

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Maintenance::class)]
    private Collection $maintenances;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Rental::class)]
    private Collection $rentals;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Sale::class)]
    private Collection $sales;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    // ===============================
    // CONSTRUCTOR
    // ===============================

    public function __construct()
    {
        $this->features = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    // ===============================
    // ID
    // ===============================

    public function getId(): ?int
    {
        return $this->id;
    }

    // ===============================
    // STATUS
    // ===============================

    public function getStatus(): VehicleStatus
    {
        return $this->status;
    }

    public function setStatus(VehicleStatus $status): self
    {
        // simple et safe pour fixtures
        if ($this->status === $status) {
            return $this;
        }

        $this->status = $status;
        return $this;
    }

    // ===============================
    // IDENTIFICATION
    // ===============================

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(string $vin): static
    {
        $this->vin = strtoupper(trim($vin));
        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = strtoupper(trim($registrationNumber));
        return $this;
    }

    // ===============================
    // TECHNIQUE
    // ===============================

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;
        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getFirstRegistrationDate(): ?\DateTime
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(?\DateTime $date): static
    {
        $this->firstRegistrationDate = $date;
        return $this;
    }

    // ===============================
    // RELATIONS
    // ===============================

    public function getVehicleModel(): ?VehicleModel
    {
        return $this->vehicleModel;
    }

    public function setVehicleModel(?VehicleModel $v): static
    {
        $this->vehicleModel = $v;
        return $this;
    }

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(?FuelType $v): static
    {
        $this->fuelType = $v;
        return $this;
    }

    public function getGear(): ?Gear
    {
        return $this->gear;
    }

    public function setGear(?Gear $v): static
    {
        $this->gear = $v;
        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $v): static
    {
        $this->color = $v;
        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $v): static
    {
        $this->supplier = $v;
        return $this;
    }

    // ===============================
    // FEATURES (IMPORTANT FIX)
    // ===============================

    /**
     * @return Collection<int, Feature>
     */
    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): self
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }

        return $this;
    }

    public function removeFeature(Feature $feature): self
    {
        $this->features->removeElement($feature);
        return $this;
    }

    // ===============================
    // HELPERS
    // ===============================

    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === VehicleStatus::RESERVED;
    }
}
