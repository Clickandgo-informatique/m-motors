<?php

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VariantRepository::class)]
class Variant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    private ?Model $model = null;

    #[ORM\Column(type: 'text')]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $release_year = null;

    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->vehicleModels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->release_year;
    }

    public function setReleaseYear(?int $release_year): static
    {
        $this->release_year = $release_year;
        return $this;
    }

    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }
}
