<?php

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VariantRepository::class)]
class Variant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la variante est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "La variante doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La variante ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    /*
    ==========================
    MODEL
    ==========================
    */

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Model $model = null;

    /*
    ==========================
    VEHICLE MODELS
    ==========================
    */

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

    /*
    ==========================
    NAME
    ==========================
    */

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /*
    ==========================
    MODEL
    ==========================
    */

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(?Model $model): static
    {
        $this->model = $model;
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
            $vehicleModel->setVariant($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            if ($vehicleModel->getVariant() === $this) {
                $vehicleModel->setVariant(null);
            }
        }

        return $this;
    }
}