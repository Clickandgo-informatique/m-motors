<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleModelRepository::class)]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le CNIT doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le CNIT ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $cnit = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le code UTAC doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le code UTAC ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $utacCode = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La puissance doit être positive.")]
    #[Assert\LessThanOrEqual(
        value: 2000,
        message: "La puissance semble incorrecte."
    )]
    private ?float $powerHp = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La puissance fiscale doit être positive.")]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: "La puissance fiscale semble incorrecte."
    )]
    private ?float $powerFiscal = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La consommation doit être positive.")]
    #[Assert\LessThanOrEqual(
        value: 50,
        message: "La consommation semble incorrecte."
    )]
    private ?float $consumption = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Le CO2 doit être positif.")]
    #[Assert\LessThanOrEqual(
        value: 2000,
        message: "La valeur CO2 semble incorrecte."
    )]
    private ?float $co2 = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La marque est obligatoire.")]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le modèle est obligatoire.")]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    private ?Variant $variant = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    private ?Gear $gear = null;

    #[ORM\OneToMany(mappedBy: "vehicleModel", targetEntity: Vehicle::class)]
    private Collection $vehicles;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCnit(): ?string
    {
        return $this->cnit;
    }

    public function setCnit(?string $cnit): static
    {
        $this->cnit = $cnit;
        return $this;
    }

    public function getUtacCode(): ?string
    {
        return $this->utacCode;
    }

    public function setUtacCode(?string $utacCode): static
    {
        $this->utacCode = $utacCode;
        return $this;
    }

    public function getHomologationDate(): ?\DateTimeInterface
    {
        return $this->homologationDate;
    }

    public function setHomologationDate(?\DateTimeInterface $homologationDate): static
    {
        $this->homologationDate = $homologationDate;
        return $this;
    }

    public function getPowerHp(): ?float
    {
        return $this->powerHp;
    }

    public function setPowerHp(?float $powerHp): static
    {
        $this->powerHp = $powerHp;
        return $this;
    }

    public function getPowerFiscal(): ?float
    {
        return $this->powerFiscal;
    }

    public function setPowerFiscal(?float $powerFiscal): static
    {
        $this->powerFiscal = $powerFiscal;
        return $this;
    }

    public function getConsumption(): ?float
    {
        return $this->consumption;
    }

    public function setConsumption(?float $consumption): static
    {
        $this->consumption = $consumption;
        return $this;
    }

    public function getCo2(): ?float
    {
        return $this->co2;
    }

    public function setCo2(?float $co2): static
    {
        $this->co2 = $co2;
        return $this;
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

    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->setVehicleModel($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): static
    {
        if ($this->vehicles->removeElement($vehicle)) {
            if ($vehicle->getVehicleModel() === $this) {
                $vehicle->setVehicleModel(null);
            }
        }

        return $this;
    }
}