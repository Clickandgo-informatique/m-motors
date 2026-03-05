<?php

namespace App\Entity;

use App\Repository\FuelTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FuelTypeRepository::class)]
class FuelType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le carburant est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le carburant doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le carburant ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'fuelType', targetEntity: Vehicle::class)]
    private Collection $vehicles;

    #[ORM\OneToMany(mappedBy: 'fuelType', targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->vehicleModels = new ArrayCollection();
    }

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
        $this->name = $name;
        return $this;
    }

    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }
}