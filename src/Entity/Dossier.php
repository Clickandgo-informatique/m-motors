<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'dossier')]
#[ORM\UniqueConstraint(name: 'uniq_dossier_code', columns: ['dossier_code'])]
class Dossier
{
    // ================================
    // CONSTANTES METIER
    // ================================
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_RENTAL = 'rental';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // ================================
    // ID
    // ================================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ================================
    // CODE DOSSIER (DUP001-0001)
    // ================================
    #[ORM\Column(name: 'dossier_code', length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9]{3,10}-[0-9]{4}$/',
        message: 'Format invalide (ex: DUP001-0001)'
    )]
    private ?string $dossierCode = null;

    // ================================
    // TYPE
    // ================================
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de dossier est obligatoire.')]
    #[Assert\Choice(
        choices: [self::TYPE_PURCHASE, self::TYPE_RENTAL],
        message: 'Type de dossier invalide.'
    )]
    private string $type;

    // ================================
    // STATUT
    // ================================
    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED
        ],
        message: 'Statut invalide.'
    )]
    private string $status = self::STATUS_DRAFT;

    // ================================
    // RELATIONS
    // ================================
    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le véhicule est obligatoire.')]
    private ?Vehicle $vehicle = null;

    // ================================
    // DATES
    // ================================
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    // ================================
    // LIFECYCLE
    // ================================
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    // ================================
    // GETTERS / SETTERS
    // ================================
    public function getId(): ?int
    {
        return $this->id;
    }

    // -------- CODE --------
    public function getDossierCode(): ?string
    {
        return $this->dossierCode;
    }

    public function setDossierCode(string $dossierCode): self
    {
        $this->dossierCode = strtoupper(trim($dossierCode));
        return $this;
    }

    // -------- TYPE --------
    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    // -------- STATUS --------
    public function getStatus(): string
    {
        return $this->status;
    }

    private function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    // -------- RELATIONS --------
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

    // -------- DATES --------
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    // ================================
    // LOGIQUE METIER SECURISEE
    // ================================
    public function submit(): self
    {
        if (!$this->isDraft()) {
            throw new \LogicException('Seul un dossier draft peut être soumis.');
        }

        $this->setStatus(self::STATUS_SUBMITTED);
        $this->submittedAt = new \DateTimeImmutable();

        return $this;
    }

    public function approve(): self
    {
        if (!$this->isSubmitted() && $this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \LogicException('Le dossier doit être soumis ou en review.');
        }

        $this->setStatus(self::STATUS_APPROVED);
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    public function reject(): self
    {
        if (!$this->isSubmitted() && $this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \LogicException('Le dossier doit être soumis ou en review.');
        }

        $this->setStatus(self::STATUS_REJECTED);
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    // ================================
    // HELPERS
    // ================================
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPurchase(): bool
    {
        return $this->type === self::TYPE_PURCHASE;
    }

    public function isRental(): bool
    {
        return $this->type === self::TYPE_RENTAL;
    }

    // ================================
    // DEBUG / AFFICHAGE
    // ================================
    public function __toString(): string
    {
        return $this->dossierCode ?? 'Dossier #' . $this->id;
    }
}
