<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleModel $vehicleModel = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Color $color = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Gear $gear = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    private ?Supplier $supplier = null;

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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

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

    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function getSales(): Collection
    {
        return $this->sales;
    }
}
