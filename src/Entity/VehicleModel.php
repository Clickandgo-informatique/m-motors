<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;

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

        // Synchronisation automatique Brand ↔ Model
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
     * TECHNICAL DATA
     */

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

    public function getMassMin(): ?float
    {
        return $this->massMin;
    }

    public function setMassMin(?float $massMin): static
    {
        $this->massMin = $massMin;
        return $this;
    }

    public function getMassMax(): ?float
    {
        return $this->massMax;
    }

    public function setMassMax(?float $massMax): static
    {
        $this->massMax = $massMax;
        return $this;
    }

    /*
     * UTAC
     */

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

    public function getEuroNorm(): ?string
    {
        return $this->euroNorm;
    }

    public function setEuroNorm(?string $euroNorm): static
    {
        $this->euroNorm = $euroNorm;
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
}
