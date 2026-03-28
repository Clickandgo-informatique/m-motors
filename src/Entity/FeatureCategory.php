<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FeatureCategoryRepository;

#[ORM\Entity(repositoryClass: FeatureCategoryRepository::class)]
class FeatureCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Code technique (slug stable)
    #[ORM\Column(length: 100, unique: true)]
    private ?string $code = null;

    //Label affiché
    #[ORM\Column(length: 100)]
    private ?string $label = null;

    //Ordre d'affichage (optionnel mais utile)
    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    // 🔗 Relation avec Feature
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Feature::class, orphanRemoval: false)]
    private Collection $features;

    public function __construct()
    {
        $this->features = new ArrayCollection();
    }

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtolower(trim($code));
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = ucfirst($label);
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Collection<int, Feature>
     */
    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): static
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
            $feature->setCategory($this);
        }

        return $this;
    }

    public function removeFeature(Feature $feature): static
    {
        if ($this->features->removeElement($feature)) {
            // sécurité : on coupe la relation côté Feature
            if ($feature->getCategory() === $this) {
                $feature->setCategory(null);
            }
        }

        return $this;
    }
}
