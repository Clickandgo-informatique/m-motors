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
use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
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
    // STATUS
    // =========================================================

    #[ORM\Column(enumType: VehicleStatus::class)]
    private VehicleStatus $status = VehicleStatus::AVAILABLE;

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
    // IDENTIFICATION
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

    public function setVin(string $vin): static
    {
        $this->vin = strtoupper(trim($vin));
        return $this;
    }

    #[ORM\Column(length: 15, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-]{4,15}$/')]
    private ?string $registrationNumber = null;

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = strtoupper(trim($registrationNumber));
        return $this;
    }

    // =========================================================
    // TECHNIQUE
    // =========================================================

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Assert\LessThan(2000000)]
    private ?int $mileage = null;

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;
        return $this;
    }

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $firstRegistrationDate = null;

    public function getFirstRegistrationDate(): ?\DateTime
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(?\DateTime $date): static
    {
        $this->firstRegistrationDate = $date;
        return $this;
    }

    // =========================================================
    // RELATIONS (ManyToOne)
    // =========================================================

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleModel $vehicleModel = null;

    public function getVehicleModel(): ?VehicleModel
    {
        return $this->vehicleModel;
    }

    public function setVehicleModel(?VehicleModel $vehicleModel): static
    {
        $this->vehicleModel = $vehicleModel;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?FuelType $fuelType = null;

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(?FuelType $fuelType): static
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    public function getGear(): ?Gear
    {
        return $this->gear;
    }

    public function setGear(?Gear $gear): static
    {
        $this->gear = $gear;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

    // =========================================================
    // COLLECTIONS INIT
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
        $this->features = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->dossiers = new ArrayCollection();
    }

    // =========================================================
    // DOSSIERS
    // =========================================================

    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): self
    {
        if (!$this->dossiers->contains($dossier)) {
            $this->dossiers->add($dossier);
            $dossier->setVehicle($this);
        }
        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        if ($this->dossiers->removeElement($dossier)) {
            if ($dossier->getVehicle() === $this) {
                $dossier->setVehicle(null);
            }
        }

        return $this;
    }

    // =========================================================
    // MAINTENANCES
    // =========================================================

    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): self
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setVehicle($this);
        }
        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): self
    {
        if ($this->maintenances->removeElement($maintenance)) {
            if ($maintenance->getVehicle() === $this) {
                $maintenance->setVehicle(null);
            }
        }
        return $this;
    }

    // =========================================================
    // RENTALS
    // =========================================================

    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function addRental(Rental $rental): self
    {
        if (!$this->rentals->contains($rental)) {
            $this->rentals->add($rental);
            $rental->setVehicle($this);
        }
        return $this;
    }

    public function removeRental(Rental $rental): self
    {
        if ($this->rentals->removeElement($rental)) {
            if ($rental->getVehicle() === $this) {
                $rental->setVehicle(null);
            }
        }
        return $this;
    }

    // =========================================================
    // SALES
    // =========================================================

    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setVehicle($this);
        }
        return $this;
    }

    public function removeSale(Sale $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            if ($sale->getVehicle() === $this) {
                $sale->setVehicle(null);
            }
        }
        return $this;
    }

    // =========================================================
    // FAVORITES
    // =========================================================

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setVehicle($this);
        }
        return $this;
    }
    public function removeFavorite(Favorite $favorite): self
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getVehicle() === $this) {
                $favorite->setVehicle(null);
            }
        }

        return $this;
    }

    // =========================================================
    // FEATURES (ManyToMany)
    // =========================================================

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

    // =========================================================
    // HELPERS
    // =========================================================

    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === VehicleStatus::RESERVED;
    }
}
