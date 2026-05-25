<?php

namespace App\Entity;

use App\Repository\FinancingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FinancingRepository::class)]
class Financing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * relation obligatoire avec le dossier
     */
    #[ORM\OneToOne(inversedBy: 'financing', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dossier $dossier = null;

    /**
     * statut du financement
     * pending = en attente
     * approved = accepté
     * rejected = refusé
     */
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['pending', 'approved', 'rejected'])]
    private string $status = 'pending';

    /**
     * type principal de financement
     * cash = comptant
     * credit = crédit
     * leasing = loa ou lld
     */
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['cash', 'credit', 'leasing'])]
    private string $type = 'cash';

    /**
     * sous-type leasing uniquement si type = leasing
     * loa = location avec option d’achat
     * lld = location longue durée
     */
    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: ['loa', 'lld'], groups: ['leasing'])]
    private ?string $leasingType = null;

    /**
     * montant financé
     */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $amount = null;

    /**
     * durée en mois
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $durationMonths = null;

    /**
     * mensualité estimée ou calculée
     */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $monthlyPayment = null;

    /**
     * date de décision du financement
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $decidedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        // sécurité métier : si on quitte leasing on nettoie le sous-type
        if ($type !== 'leasing') {
            $this->leasingType = null;
        }

        return $this;
    }

    public function getLeasingType(): ?string
    {
        return $this->leasingType;
    }

    public function setLeasingType(?string $leasingType): self
    {
        $this->leasingType = $leasingType;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDurationMonths(): ?int
    {
        return $this->durationMonths;
    }

    public function setDurationMonths(?int $durationMonths): self
    {
        $this->durationMonths = $durationMonths;

        return $this;
    }

    public function getMonthlyPayment(): ?float
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(?float $monthlyPayment): self
    {
        $this->monthlyPayment = $monthlyPayment;

        return $this;
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?\DateTimeImmutable $decidedAt): self
    {
        $this->decidedAt = $decidedAt;

        return $this;
    }

    /**
     * règle métier simple de cohérence
     */
    public function isLeasing(): bool
    {
        return $this->type === 'leasing';
    }

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function validateConsistency(): void
    {
        if ($this->type !== 'leasing') {
            $this->leasingType = null;

            return;
        }

        if ($this->leasingType === null) {
            throw new \InvalidArgumentException(
                'leasingType obligatoire si type = leasing'
            );
        }
    }
}
