<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleModelRepository::class)]
#[ORM\Index(columns: ['cnit'])]
#[ORM\Index(columns: ['utac_code'])]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La marque est obligatoire")]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le modèle est obligatoire")]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Variant $variant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Gear $gear = null;

    #[ORM\OneToMany(mappedBy: "vehicleModel", targetEntity: Vehicle::class)]
    private Collection $vehicles;

    /*
    |--------------------------------------------------------------------------
    | DONNÉES TECHNIQUES
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^EURO\s?[0-9]{1,2}$/i',
        message: 'Norme EURO invalide'
    )]
    private ?string $euroNorm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 2000,
        notInRangeMessage: "La puissance doit être entre {{ min }} et {{ max }} chevaux"
    )]
    private ?int $powerHp = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: "La puissance fiscale doit être entre {{ min }} et {{ max }}"
    )]
    private ?float $powerFiscal = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 50,
        notInRangeMessage: "Consommation invalide"
    )]
    private ?float $consumption = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 2000,
        notInRangeMessage: "CO2 invalide"
    )]
    private ?int $co2 = null;

    /*
    |--------------------------------------------------------------------------
    | HOMOLOGATION
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        min: 4,
        max: 50,
        minMessage: "CNIT trop court",
        maxMessage: "CNIT trop long"
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z0-9]+$/",
        message: "Le CNIT ne doit contenir que lettres majuscules et chiffres"
    )]
    private ?string $cnit = null;

    #[ORM\Column(name: "utac_code", length: 50, nullable: true)]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Code UTAC trop court",
        maxMessage: "Code UTAC trop long"
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z0-9\-]+$/",
        message: "Code UTAC invalide"
    )]
    private ?string $utacCode = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(
        message: 'La masse minimale doit être positive.'
    )]
    #[Assert\LessThanOrEqual(
        propertyPath: 'massMax',
        message: 'La masse minimale doit être inférieure ou égale à la masse maximale.'
    )]
    private ?int $massMin = null;


    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(
        message: 'La masse maximale doit être positive.'
    )]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'massMin',
        message: 'La masse maximale doit être supérieure ou égale à la masse minimale.'
    )]
    private ?int $massMax = null;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTEUR
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    /*
    |--------------------------------------------------------------------------
    | GETTERS / SETTERS
    |--------------------------------------------------------------------------
    */

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

    public function getPowerHp(): ?int
    {
        return $this->powerHp;
    }

    public function setPowerHp(?int $powerHp): static
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

    public function getCo2(): ?int
    {
        return $this->co2;
    }

    public function setCo2(?int $co2): static
    {
        $this->co2 = $co2;
        return $this;
    }

    public function getCnit(): ?string
    {
        return $this->cnit;
    }

    public function setCnit(?string $cnit): static
    {
        $this->cnit = $cnit ? strtoupper($cnit) : null;
        return $this;
    }

    public function getUtacCode(): ?string
    {
        return $this->utacCode;
    }

    public function setUtacCode(?string $utacCode): static
    {
        $this->utacCode = $utacCode ? strtoupper($utacCode) : null;
        return $this;
    }

    public function getHomologationDate(): ?\DateTimeInterface
    {
        return $this->homologationDate;
    }

    public function setHomologationDate(?\DateTimeInterface $date): static
    {
        $this->homologationDate = $date;
        return $this;
    }
    public function getMassMin(): ?int
    {
        return $this->massMin;
    }

    public function setMassMin(?int $massMin): self
    {
        $this->massMin = $massMin;

        return $this;
    }

    public function getMassMax(): ?int
    {
        return $this->massMax;
    }

    public function setMassMax(?int $massMax): self
    {
        $this->massMax = $massMax;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION VEHICLES
    |--------------------------------------------------------------------------
    */

    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
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
    public function getEuroNorm(): ?string
    {
        return $this->euroNorm;
    }

    public function setEuroNorm(?string $euroNorm): static
    {
        $this->euroNorm = $euroNorm;
        return $this;
    }
}
