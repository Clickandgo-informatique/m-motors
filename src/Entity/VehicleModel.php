<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Brand $brand = null;

    #[ORM\ManyToOne]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleModels')]
    private ?Variant $variant = null;

    #[ORM\ManyToOne]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne]
    private ?Gear $gear = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $powerHp = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $powerFiscal = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $co2 = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $consumption = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $massMin = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $massMax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cnit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $utacCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $euroNorm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;


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

    public function getPowerHp(): ?float
    {
        return $this->powerHp;
    }

    public function setPowerHp(?float $powerHp): static
    {
        $this->powerHp = $powerHp;
        return $this;
    }

    public function getPowerFiscal(): ?int
    {
        return $this->powerFiscal;
    }

    public function setPowerFiscal(?int $powerFiscal): static
    {
        $this->powerFiscal = $powerFiscal;
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

    public function getConsumption(): ?float
    {
        return $this->consumption;
    }

    public function setConsumption(?float $consumption): static
    {
        $this->consumption = $consumption;
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
