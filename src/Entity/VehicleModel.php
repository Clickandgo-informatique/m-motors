<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleModelRepository::class)]
#[ORM\Table(indexes: [
    new ORM\Index(columns: ['cnit']),
    new ORM\Index(columns: ['utac_code'])
])]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // RELATIONS


    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La marque est obligatoire.")]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le modèle est obligatoire.")]
    private ?Model $model = null;

    #[ORM\ManyToOne(inversedBy: "vehicleModels")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Variant $variant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?FuelType $fuelType = null;

    #[ORM\ManyToOne(targetEntity: GearType::class, inversedBy: 'vehicleModels')]
    #[ORM\JoinColumn(nullable: true)]
    private ?GearType $gearType = null;

    #[ORM\ManyToOne(targetEntity: BodyType::class, inversedBy: 'vehicleModels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le type de carrosserie est obligatoire.")]
    private ?BodyType $bodyType = null;

    #[ORM\OneToMany(mappedBy: "vehicleModel", targetEntity: Vehicle::class, orphanRemoval: true)]
    private Collection $vehicles;

    /*
    |--------------------------------------------------------------------------
    | DONNÉES TECHNIQUES
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^EURO\s?[0-9]{1,2}$/i',
        message: 'Norme EURO invalide.'
    )]
    private ?string $euroNorm = null;

    #[ORM\Column(nullable: true)]
    private ?int $powerHp = null;

    #[ORM\Column(nullable: true)]
    private ?float $powerFiscal = null;

    #[ORM\Column(nullable: true)]
    private ?float $consumption = null;

    #[ORM\Column(nullable: true)]
    private ?int $co2 = null;

    /*
    |--------------------------------------------------------------------------
    | HOMOLOGATION
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cnit = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $utacCode = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $homologationDate = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $massMin = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $massMax = null;

    /*
    |--------------------------------------------------------------------------
    | STOCK (NON PERSISTÉ)
    |--------------------------------------------------------------------------
    */

    private ?int $availableStock = null;
    private ?int $availableForSale = null;
    private ?int $availableForRent = null;

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
    | STOCK GETTERS / SETTERS
    |--------------------------------------------------------------------------
    */

    public function getAvailableStock(): int
    {
        return $this->availableStock ?? 0;
    }

    public function setAvailableStock(int $stock): self
    {
        $this->availableStock = $stock;
        return $this;
    }

    public function getAvailableForSale(): int
    {
        return $this->availableForSale ?? 0;
    }

    public function setAvailableForSale(int $stock): self
    {
        $this->availableForSale = $stock;
        return $this;
    }

    public function getAvailableForRent(): int
    {
        return $this->availableForRent ?? 0;
    }

    public function setAvailableForRent(int $stock): self
    {
        $this->availableForRent = $stock;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTIER
    |--------------------------------------------------------------------------
    */

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function getDisplayName(): string
    {
        return trim(
            ($this->brand?->getName() ?? '') . ' ' .
                ($this->model?->getName() ?? '') . ' ' .
                ($this->variant?->getName() ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GETTERS / SETTERS SIMPLES
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

    public function getGearType(): ?GearType
    {
        return $this->gearType;
    }

    public function setGearType(?GearType $gearType): static
    {
        $this->gearType = $gearType;
        return $this;
    }

    public function getBodyType(): ?BodyType
    {
        return $this->bodyType;
    }

    public function setBodyType(?BodyType $bodyType): static
    {
        $this->bodyType = $bodyType;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLES
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

    /*
    |--------------------------------------------------------------------------
    | TECH
    |--------------------------------------------------------------------------
    */

    public function getEuroNorm(): ?string
    {
        return $this->euroNorm;
    }

    public function setEuroNorm(?string $euroNorm): static
    {
        $this->euroNorm = $euroNorm;
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

    /*
    |--------------------------------------------------------------------------
    | HOMOLOGATION
    |--------------------------------------------------------------------------
    */

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

    public function setMassMin(?int $massMin): static
    {
        $this->massMin = $massMin;
        return $this;
    }

    public function getMassMax(): ?int
    {
        return $this->massMax;
    }

    public function setMassMax(?int $massMax): static
    {
        $this->massMax = $massMax;
        return $this;
    }
}
