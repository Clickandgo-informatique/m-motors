<?php

namespace App\Entity;

use App\Repository\GearTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GearTypeRepository::class)]
class GearType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: "Le type de boîte est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Minimum {{ limit }} caractères",
        maxMessage: "Maximum {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ0-9\s\-]+$/",
        message: "Caractères invalides"
    )]
    private ?string $name = null;

    /*
    ==========================
    VEHICLES
    ==========================
    */
    #[ORM\OneToMany(mappedBy: 'gearType', targetEntity: Vehicle::class)]
    private Collection $vehicles;

    /*
    ==========================
    VEHICLE MODELS
    ==========================
    */
    #[ORM\OneToMany(mappedBy: 'GearType', targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->vehicleModels = new ArrayCollection();
    }

    /*
    ==========================
    GETTERS / SETTERS
    ==========================
    */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    /*
    ==========================
    VEHICLES
    ==========================
    */

    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setGearType($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): static
    {
        if ($this->vehicles->removeElement($vehicle)) {
            if ($vehicle->getGearType() === $this) {
                $vehicle->setGearType(null);
            }
        }

        return $this;
    }

    /*
    ==========================
    VEHICLE MODELS
    ==========================
    */

    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }

    public function addVehicleModel(VehicleModel $vehicleModel): static
    {
        if (!$this->vehicleModels->contains($vehicleModel)) {
            $this->vehicleModels->add($vehicleModel);
            $vehicleModel->setGearType($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            if ($vehicleModel->getGearType() === $this) {
                $vehicleModel->setGearType(null);
            }
        }

        return $this;
    }
}
