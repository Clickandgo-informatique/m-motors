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

    #[ORM\Column(enumType: VehicleStatus::class)]
    private ?VehicleStatus $status = null;

    /*
    ===============================
    IDENTIFICATION
    ===============================
    */

    #[ORM\Column(length: 17, unique: true)]
    #[Assert\NotBlank(message: "Le VIN est obligatoire")]
    #[Assert\Regex(
        pattern: '/^[A-HJ-NPR-Z0-9]{17}$/',
        message: "VIN invalide (17 caractères alphanumériques)"
    )]
    private ?string $vin = null;

    #[ORM\Column(length: 15, unique: true)]
    #[Assert\NotBlank(message: "Le numéro d'immatriculation est obligatoire")]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-]{4,15}$/',
        message: "Format d'immatriculation invalide"
    )]
    private ?string $registrationNumber = null;

    /*
    ===============================
    INFORMATIONS VEHICULE
    ===============================
    */

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1950,
        max: 2100,
        notInRangeMessage: "L'année doit être comprise entre {{ min }} et {{ max }}"
    )]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: "Le kilométrage doit être positif")]
    #[Assert\LessThan(
        value: 2000000,
        message: "Kilométrage incohérent"
    )]
    private ?int $mileage = null;

    /*
    ===============================
    RELATIONS PRINCIPALES
    ===============================
    */

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

    /*
    ===============================
    RELATIONS SECONDAIRES
    ===============================
    */

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'vehicles')]
    private Collection $features;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Maintenance::class)]
    private Collection $maintenances;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Rental::class)]
    private Collection $rentals;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Sale::class)]
    private Collection $sales;

    public function __construct()
    {
        $this->features = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->sales = new ArrayCollection();
    }
    #[ORM\Column(type: 'integer')]
    private ?int $price = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $firstRegistrationDate = null;


    /*
    ===============================
    GETTERS / SETTERS
    ===============================
    */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;
        return $this;
    }

    public function getVehicleModel(): ?VehicleModel
    {
        return $this->vehicleModel;
    }

    public function setVehicleModel(?VehicleModel $vehicleModel): static
    {
        $this->vehicleModel = $vehicleModel;
        return $this;
    }

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(?FuelType $fuelType): static
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    public function getGear(): ?Gear
    {
        return $this->gear;
    }

    public function setGear(?Gear $gear): static
    {
        $this->gear = $gear;
        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

    /*
    ===============================
    FEATURES
    ===============================
    */

    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): static
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }

        return $this;
    }

    public function removeFeature(Feature $feature): static
    {
        $this->features->removeElement($feature);
        return $this;
    }

    /*
    ===============================
    MAINTENANCE
    ===============================
    */

    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    /*
    ===============================
    RENTALS
    ===============================
    */

    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    /*
    ===============================
    SALES
    ===============================
    */

    public function getSales(): Collection
    {
        return $this->sales;
    }
    public function getStatus(): ?VehicleStatus
    {
        return $this->status;
    }

    public function setStatus(VehicleStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFirstRegistrationDate(): ?\DateTime
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(?\DateTime $firstRegistrationDate): static
    {
        $this->firstRegistrationDate = $firstRegistrationDate;

        return $this;
    }
}
