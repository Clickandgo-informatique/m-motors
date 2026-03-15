<?php

namespace App\Entity;

use App\Repository\BodyTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BodyTypeRepository::class)]
class BodyType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank(message: "Le nom du type de carrosserie est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    /*
    ======================================
    RELATION
    ======================================
    */

    #[ORM\OneToMany(mappedBy: 'bodyType', targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->vehicleModels = new ArrayCollection();
    }

    /*
    ======================================
    GETTERS / SETTERS
    ======================================
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
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /*
    ======================================
    VEHICLE MODELS
    ======================================
    */

    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }

    public function addVehicleModel(VehicleModel $vehicleModel): static
    {
        if (!$this->vehicleModels->contains($vehicleModel)) {
            $this->vehicleModels->add($vehicleModel);
            $vehicleModel->setBodyType($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            if ($vehicleModel->getBodyType() === $this) {
                $vehicleModel->setBodyType(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
