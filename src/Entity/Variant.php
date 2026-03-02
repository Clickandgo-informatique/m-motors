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
    #[ORM\JoinColumn(nullable: false)]
    private ?Model $model = null;

    #[ORM\Column(type: 'text')]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $releaseYear = null;

    #[ORM\OneToMany(
        mappedBy: 'variant',
        targetEntity: VehicleModel::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $vehicleModels;

    public function __construct()
    {
        $this->vehicleModels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * =============================
     * MODEL
     * =============================
     */

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * =============================
     * INFOS
     * =============================
     */

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(?int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;
        return $this;
    }

    /**
     * =============================
     * VEHICLE MODELS
     * =============================
     */

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
            $vehicleModel->setVariant($this);
        }

        return $this;
    }

    public function removeVehicleModel(VehicleModel $vehicleModel): static
    {
        if ($this->vehicleModels->removeElement($vehicleModel)) {
            // ❌ NE PAS mettre setVariant(null) si nullable=false
            // orphanRemoval supprimera l'entité proprement
        }

        return $this;
    }
}
