<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleModelRepository::class)]
#[ORM\Index(columns: ['cnit'], name: 'idx_cnit')]
#[ORM\Index(columns: ['utac_code'], name: 'idx_utac_code')]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * =========================
     * RELATION INVERSE AVEC VEHICLE
     * =========================
     */

    #[ORM\OneToMany(mappedBy: 'vehicleModel', targetEntity: Vehicle::class)]
    private Collection $vehicles;

    /*
     * =========================
     * RELATIONS
     * =========================
     */

    #[ORM\ManyToOne(inversedBy: 'vehicleModels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleModels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleModels')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Variant $variant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Gear $gear = null;

    /*
     * =========================
     * DONNÉES TECHNIQUES
     * =========================
     */

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $powerHp = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $powerFiscal = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $consumption = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $co2 = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $massMin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $massMax = null;

    /*
     * =========================
     * IDENTIFIANTS UTAC
     * =========================
     */

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cnit = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $utacCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $euroNorm = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;

    /*
     * =========================
     * CONSTRUCTEUR
     * =========================
     */

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    /*
     * =========================
     * GETTERS / SETTERS
     * =========================
     */

    public function getId(): ?int
    {
        return $this->id;
    }

    /*
     * VEHICLES (RELATION INVERSE)
     */

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

    /*
     * BRAND
     */

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(Brand $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    /*
     * MODEL
     */

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;

        if ($model->getBrand() !== null) {
            $this->brand = $model->getBrand();
        }

        return $this;
    }

    /*
     * VARIANT
     */

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }

    public function setVariant(?Variant $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /*
     * FUEL
     */

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(FuelType $fuelType): static
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    /*
     * GEAR
     */

    public function getGear(): ?Gear
    {
        return $this->gear;
    }

    public function setGear(?Gear $gear): static
    {
        $this->gear = $gear;
        return $this;
    }

    /*
     * TECH DATA
     */

    public function getPowerHp(): ?float
    {
        return $this->powerHp;
    }
    public function setPowerHp(?float $v): static
    {
        $this->powerHp = $v;
        return $this;
    }

    public function getPowerFiscal(): ?float
    {
        return $this->powerFiscal;
    }
    public function setPowerFiscal(?float $v): static
    {
        $this->powerFiscal = $v;
        return $this;
    }

    public function getConsumption(): ?float
    {
        return $this->consumption;
    }
    public function setConsumption(?float $v): static
    {
        $this->consumption = $v;
        return $this;
    }

    public function getCo2(): ?float
    {
        return $this->co2;
    }
    public function setCo2(?float $v): static
    {
        $this->co2 = $v;
        return $this;
    }

    public function getMassMin(): ?float
    {
        return $this->massMin;
    }
    public function setMassMin(?float $v): static
    {
        $this->massMin = $v;
        return $this;
    }

    public function getMassMax(): ?float
    {
        return $this->massMax;
    }
    public function setMassMax(?float $v): static
    {
        $this->massMax = $v;
        return $this;
    }

    /*
     * UTAC
     */

    public function getCnit(): ?string
    {
        return $this->cnit;
    }
    public function setCnit(?string $v): static
    {
        $this->cnit = $v;
        return $this;
    }

    public function getUtacCode(): ?string
    {
        return $this->utacCode;
    }
    public function setUtacCode(?string $v): static
    {
        $this->utacCode = $v;
        return $this;
    }

    public function getEuroNorm(): ?string
    {
        return $this->euroNorm;
    }
    public function setEuroNorm(?string $v): static
    {
        $this->euroNorm = $v;
        return $this;
    }

    public function getHomologationDate(): ?\DateTimeInterface
    {
        return $this->homologationDate;
    }
    public function setHomologationDate(?\DateTimeInterface $v): static
    {
        $this->homologationDate = $v;
        return $this;
    }
}
