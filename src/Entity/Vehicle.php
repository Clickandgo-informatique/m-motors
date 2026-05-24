<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Entity\VehicleBadge;
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

    /**
     * Identifiant
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Statut métier du véhicule
     */
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

    /**
     * Type d’usage du véhicule (vente, location, les deux)
     */
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

    //Immatriculation
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

    // TECHNIQUE
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
    // IDENTIFIANTS
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


    // Images du véhicule     
    #[ORM\OneToMany(
        mappedBy: 'vehicle',
        targetEntity: Image::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;


    // Dossiers liés au véhicule   
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Dossier::class, orphanRemoval: true)]
    private Collection $dossiers;


    // Maintenances   
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Maintenance::class, orphanRemoval: true)]
    private Collection $maintenances;


    // Locations    
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Rental::class, orphanRemoval: true)]
    private Collection $rentals;

    //Ventes

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Sale::class, orphanRemoval: true)]
    private Collection $sales;

    /**
     * Favoris
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    /**
     * Options / équipements
     */
    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'vehicles')]
    private Collection $features;


    // Constructeur

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->dossiers = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->features = new ArrayCollection();
        $this->badges = new ArrayCollection();
    }

    // RELATIONS (ManyToOne)
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

    /**
     * Images
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

    /**
     * Dossiers
     */
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

    /**
     * Maintenances
     */
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

    /**
     * Locations
     */
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

    /**
     * Ventes
     */
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

    /**
     * Favoris
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
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


    //Options véhicule

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


    //Véhicule disponible    
    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    //Véhicule bloqué pour création de dossier

    public function isLocked(): bool
    {
        return in_array($this->status, [
            VehicleStatus::RESERVED,
            VehicleStatus::RENTED,
            VehicleStatus::SOLD,
            VehicleStatus::MAINTENANCE
        ], true);
    }


    //Réservation

    public function reserve(): self
    {
        $this->status = VehicleStatus::RESERVED;

        return $this;
    }


    // Vente   
    public function markAsSold(): self
    {
        $this->status = VehicleStatus::SOLD;

        return $this;
    }

    //Location     
    public function markAsRented(): self
    {
        $this->status = VehicleStatus::RENTED;

        return $this;
    }

    // Remise en disponibilité    
    public function makeAvailable(): self
    {
        $this->status = VehicleStatus::AVAILABLE_FOR_SALE;

        return $this;
    }

    //Crée une image de véhicule par défaut pour la galerie
    //fallback centralisé,
    //image featured prioritaire,
    //première image sinon image par défaut
    public function getMainImagePath(): string
    {
        foreach ($this->images as $image) {
            if ($image->isFeatured()) {
                return $image->getImagePath();
            }
        }

        if (!$this->images->isEmpty()) {
            return $this->images->first()->getImagePath();
        }

        return 'uploads/vehicles/default-vehicle.png';
    }

    // gestion des badges commerciaux dans la galerie de véhicules
    /**
     * @var Collection<int, VehicleBadge>
     */
    #[ORM\ManyToMany(targetEntity: VehicleBadge::class, inversedBy: 'vehicles')]
    #[ORM\JoinTable(name: 'vehicle_vehicle_badge')]
    private Collection $badges;
    /**
     * @return Collection<int, VehicleBadge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(VehicleBadge $badge): self
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
        }

        return $this;
    }

    public function removeBadge(VehicleBadge $badge): self
    {
        $this->badges->removeElement($badge);

        return $this;
    }
}
