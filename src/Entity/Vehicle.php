<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\VehicleStatus;
use App\Enum\VehicleUsageType;
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

    // Type d'usage véhicule pour workflow
    #[ORM\Column(enumType: VehicleUsageType::class)]
    private VehicleUsageType $usageType;

    public function getUsageType(): VehicleUsageType
    {
        return $this->usageType;
    }

    public function setUsageType(VehicleUsageType $usageType): self
    {
        $this->usageType = $usageType;
        return $this;
    }

    // Gestion de la galerie d'images
    #[ORM\OneToMany(
        mappedBy: 'vehicle',
        targetEntity: Image::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setVehicle($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            if ($image->getVehicle() === $this) {
                $image->setVehicle(null);
            }
        }

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
    // RELATIONS (ManyToOne)
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

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(?FuelType $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?GearType $gearType = null;

    public function getGearType(): ?GearType
    {
        return $this->gearType;
    }

    public function setGearType(?GearType $gearType): self
    {
        $this->gearType = $gearType;
        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): self
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

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    // =========================================================
    // COLLECTIONS
    // =========================================================

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Dossier::class, orphanRemoval: true)]
    private Collection $dossiers;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Maintenance::class, orphanRemoval: true)]
    private Collection $maintenances;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Rental::class, orphanRemoval: true)]
    private Collection $rentals;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Sale::class, orphanRemoval: true)]
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
        $this->images = new ArrayCollection();
    }

    // ========================= DOSSIERS =========================

    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): self
    {
        if (!$this->dossiers->contains($dossier)) {
            $this->dossiers[] = $dossier;
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

    // ========================= MAINTENANCES =========================

    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): self
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances[] = $maintenance;
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

    // ========================= RENTALS =========================

    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function addRental(Rental $rental): self
    {
        if (!$this->rentals->contains($rental)) {
            $this->rentals[] = $rental;
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

    // ========================= SALES =========================

    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales[] = $sale;
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

    // ========================= FAVORITES =========================

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
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

    // ========================= FEATURES =========================

    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): self
    {
        if (!$this->features->contains($feature)) {
            $this->features[] = $feature;
        }
        return $this;
    }

    public function removeFeature(Feature $feature): self
    {
        $this->features->removeElement($feature);
        return $this;
    }

    // =========================================================
    // HELPERS MÉTIER
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
