<?php

namespace App\Entity;

use App\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Cette marque existe déjà dans la base de données.')]
#[ORM\Table(
    name: "brand",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_brand_name", columns: ["name"])
    ]
)]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $name = null;

    /**
     * @var Collection<int, VehicleModel>
     */
    #[ORM\OneToMany(mappedBy: 'brand', targetEntity: VehicleModel::class)]
    private Collection $vehicleModels;

    public function __construct()
    {
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
        $this->name = trim($name);
        return $this;
    }

    /**
     * @return Collection<int, VehicleModel>
     */
    public function getVehicleModels(): Collection
    {
        return $this->vehicleModels;
    }

    public function addVehicleModel(VehicleModel $vehicleModel): static
    {
        if (!$this->vehicleModels->contains($vehicleModel)) {
            $this->vehicleModels->add($vehicleModel);
            $vehicleModel->setBrand($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            if ($vehicleModel->getBrand() === $this) {
                $vehicleModel->setBrand(null);
            }
        }

        return $this;
    }
}
