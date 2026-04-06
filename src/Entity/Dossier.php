<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ----------------- TYPE DE DOSSIER -----------------
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de dossier est obligatoire.')]
    #[Assert\Choice(choices: ['purchase', 'rental'], message: 'Type de dossier invalide.')]
    private string $type;

    // ----------------- STATUT -----------------
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['draft', 'submitted', 'under_review', 'approved', 'rejected'], message: 'Statut invalide.')]
    private string $status = 'draft';

    // ----------------- RELATIONS -----------------
    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le véhicule est obligatoire.')]
    private ?Vehicle $vehicle = null;

    // ----------------- DATES -----------------
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    // ----------------- LIFECYCLE -----------------
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ----------------- GETTERS / SETTERS -----------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }
    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }
    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): self
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }
    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }
    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    // ----------------- LOGIQUE METIER -----------------
    public function submit(): self
    {
        $this->status = 'submitted';
        $this->submittedAt = new \DateTimeImmutable();
        return $this;
    }
    public function approve(): self
    {
        $this->status = 'approved';
        $this->processedAt = new \DateTimeImmutable();
        return $this;
    }
    public function reject(): self
    {
        $this->status = 'rejected';
        $this->processedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }
    public function isRental(): bool
    {
        return $this->type === 'rental';
    }
}
