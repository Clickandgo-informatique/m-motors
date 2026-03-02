<?php

namespace App\Entity;

use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModelRepository::class)]
#[ORM\Table(
    name: "model",
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: "unique_brand_model_normalized",
            columns: ["brand_id", "normalized_name"]
        )
    ]
)]
#[UniqueEntity(
    fields: ["brand", "normalizedName"],
    message: "Ce modèle existe déjà pour cette marque."
)]
class Model
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $normalizedName = null;

    #[ORM\ManyToOne(inversedBy: 'models')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Brand $brand = null;

    #[ORM\OneToMany(
        mappedBy: 'model',
        targetEntity: Variant::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $variants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
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
        $clean = trim($name);

        $this->name = $clean;
        $this->normalizedName = mb_strtolower($clean);

        return $this;
    }

    public function getNormalizedName(): ?string
    {
        return $this->normalizedName;
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

    /**
     * =============================
     * VARIANTS
     * =============================
     */

    /**
     * @return Collection<int, Variant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setModel($this);
        }

        return $this;
    }

    public function removeVariant(Variant $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            // ⚠️ NE PAS mettre setModel(null) si nullable=false
            // Doctrine supprimera grâce à orphanRemoval=true
        }

        return $this;
    }
}
