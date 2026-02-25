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

    // Brand possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    // Gear possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    // Supplier possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

    // FuelType possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?FuelType $fuel_type = null;

    // BodyType possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?BodyType $body_type = null;

    // Color possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    // Feature possède $vehicles
    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Feature $feature = null;

    // Model n’a PAS $vehicles → pas d’inversedBy
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Model $model = null;

    // Variant n’a PAS $vehicles → pas d’inversedBy
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Variant $variant = null;

    // VehicleModel n’a PAS $vehicles → pas d’inversedBy
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?VehicleModel $vehicleModel = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $price = null;

    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'vehicle')]
    private Collection $maintenances;

    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'vehicle')]
    private Collection $sales;

    #[ORM\OneToMany(targetEntity: Rental::class, mappedBy: 'vehicle')]
    private Collection $rentals;

    public function __construct()
    {
        $this->maintenances = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->rentals = new ArrayCollection();
    }

    /* ============================
       GETTERS / SETTERS
       ============================ */

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

    /* ============================
       COLLECTIONS
       ============================ */

    /** @return Collection<int, Maintenance> */
    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): static
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setVehicle($this);
        }
        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): static
    {
        if ($this->maintenances->removeElement($maintenance)) {
            if ($maintenance->getVehicle() === $this) {
                $maintenance->setVehicle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Sale> */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): static
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setVehicle($this);
        }
        return $this;
    }

    public function removeSale(Sale $sale): static
    {
        if ($this->sales->removeElement($sale)) {
            if ($sale->getVehicle() === $this) {
                $sale->setVehicle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Rental> */
    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function addRental(Rental $rental): static
    {
        if (!$this->rentals->contains($rental)) {
            $this->rentals->add($rental);
            $rental->setVehicle($this);
        }
        return $this;
    }

    public function removeRental(Rental $rental): static
    {
        if ($this->rentals->removeElement($rental)) {
            if ($rental->getVehicle() === $this) {
                $rental->setVehicle(null);
            }
        }
        return $this;
    }
}
