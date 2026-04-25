<?php

namespace App\Entity;

use App\Repository\FeatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Feature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // CODE TECHNIQUE (stable, utilisé partout)
    #[ORM\Column(length: 100, unique: true)]
    private ?string $code = null;

    // LABEL AFFICHÉ
    #[ORM\Column(length: 100)]
    private ?string $label = null;

    // CATÉGORIE
    #[ORM\ManyToOne(inversedBy: 'features')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeatureCategory $category = null;

    #[ORM\ManyToMany(targetEntity: Vehicle::class, mappedBy: 'features')]
    private Collection $vehicles;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
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

    public function getCategory(): ?FeatureCategory
    {
        return $this->category;
    }

    public function setCategory(?FeatureCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    // =========================
    // AUTO-GENERATION DU CODE
    // =========================

    #[ORM\PrePersist]
    public function generateCode(): void
    {
        if (!$this->code && $this->label) {
            $this->code = $this->slugify($this->label);
        }
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
