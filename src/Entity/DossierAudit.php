<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\DossierAuditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierAuditRepository::class)]
class DossierAudit
{
    use TimestampableTrait;

    // =========================================================
    // IDENTIFIANT
    // =========================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================
    // DOSSIER CONCERNE
    // =========================================================

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    // =========================================================
    // UTILISATEUR (optionnel)
    // =========================================================

    #[ORM\ManyToOne]
    private ?User $user = null;

    // =========================================================
    // TYPE D'ACTION
    // =========================================================

    #[ORM\Column(length: 50)]
    private string $action;

    // =========================================================
    // CONTENU / CONTEXTE
    // =========================================================

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    // =========================================================
    // DOCUMENT LIÉ (OPTIONNEL)
    // =========================================================

    #[ORM\ManyToOne]
    private ?DossierDocument $document = null;

    // =========================================================
    // GETTERS / SETTERS
    // =========================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(Dossier $dossier): self
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getDocument(): ?DossierDocument
    {
        return $this->document;
    }

    public function setDocument(?DossierDocument $document): self
    {
        $this->document = $document;
        return $this;
    }
}
