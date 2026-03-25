<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use App\Entity\Favorite;
use App\Entity\Feature;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: "vehicle", targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'vehicles')]
    private Collection $features;

    public function __construct()
    {
        $this->favorites = new ArrayCollection();
        $this->features = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
            $favorite->setVehicle($this);
        }
        return $this;
    }

    public function removeFavorite(Favorite $favorite): self
    {
        // Avec orphanRemoval=true, Doctrine supprime automatiquement le favorite
        $this->favorites->removeElement($favorite);
        return $this;
    }

    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): self
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }
        return $this;
    }

    public function removeFeature(Feature $feature): self
    {
        $this->features->removeElement($feature);
        return $this;
    }
}
