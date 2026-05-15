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

    /*
     * Dossier lié au financement (relation 1–1 obligatoire)
     */
    #[ORM\OneToOne(inversedBy: 'financing', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dossier $dossier = null;

    /*
     * Statut du financement
     */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['pending', 'approved', 'rejected'], message: 'Statut invalide')]
    private string $status = 'pending';

    /*
     * Type de financement (cash, credit, leasing...)
     */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['cash', 'credit', 'leasing'], message: 'Type invalide')]
    private string $type = 'cash';

    /*
     * Montant financé
     */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $amount = null;

    /*
     * Durée en mois
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $durationMonths = null;

    /*
     * Mensualité calculée
     */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $monthlyPayment = null;

    /*
     * Date de décision du financement
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
}
