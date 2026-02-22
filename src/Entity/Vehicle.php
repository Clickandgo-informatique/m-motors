<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /* -------------------------
     *   RELATIONS EXISTANTES
     * ------------------------- */

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?FuelType $fuel_type = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?BodyType $body_type = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Feature $feature = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Variant $variant = null;

    /* -----------------------------------------
     *   NOUVELLE RELATION : VEHICLE MODEL UTAC
     * ----------------------------------------- */

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?VehicleModel $vehicleModel = null;

    /* -------------------------
     *   CHAMPS DU VEHICULE RÉEL
     * ------------------------- */

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $price = null;

    /* -------------------------
     *   RELATIONS OPÉRATIONNELLES
     * ------------------------- */

    /**
     * @var Collection<int, Maintenance>
     */
    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'vehicle')]
    private Collection $maintenances;

    /**
     * @var Collection<int, Sale>
     */
    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'vehicle')]
    private Collection $sales;

    /**
     * @var Collection<int, Rental>
     */
    #[ORM\OneToMany(targetEntity: Rental::class, mappedBy: 'vehicle')]
    private Collection $rentals;

    public function __construct()
    {
        $this->maintenances = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->rentals = new ArrayCollection();
    }

    /* -------------------------
     *   GETTERS / SETTERS
     * ------------------------- */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getFuelType(): ?FuelType
    {
        return $this->fuel_type;
    }

    public function setFuelType(?FuelType $fuel_type): static
    {
        $this->fuel_type = $fuel_type;
        return $this;
    }

    public function getBodyType(): ?BodyType
    {
        return $this->body_type;
    }

    public function setBodyType(?BodyType $body_type): static
    {
        $this->body_type = $body_type;
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

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature): static
    {
        $this->feature = $feature;
        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(?Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }

    public function setVariant(?Variant $variant): static
    {
        $this->variant = $variant;
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

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }
}
