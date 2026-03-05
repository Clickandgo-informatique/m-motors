<?php

namespace App\Entity;

use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModelRepository::class)]
class Model
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: "Le nom du modèle est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "Le modèle doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le modèle ne peut dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: "models")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La marque est obligatoire.")]
    private ?Brand $brand = null;

    #[ORM\OneToMany(mappedBy: "model", targetEntity: Variant::class)]
    private Collection $variants;

    #[ORM\OneToMany(mappedBy: "model", targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
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

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }

    public function addVehicleModel(VehicleModel $vehicleModel): static
    {
        if (!$this->vehicleModels->contains($vehicleModel)) {
            $this->vehicleModels[] = $vehicleModel;
            $vehicleModel->setModel($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            if ($vehicleModel->getModel() === $this) {
                $vehicleModel->setModel(null);
            }
        }

        return $this;
    }
}