<?php

namespace App\Entity;

use App\Repository\DossierWorkflowLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Historique des transitions du workflow Dossier.
 *
 * Cette entité permet de tracer :
 * - les changements de statut
 * - les transitions exécutées
 * - les utilisateurs responsables
 * - la chronologie métier complète
 */
#[ORM\Entity(repositoryClass: DossierWorkflowLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DossierWorkflowLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Dossier concerné par la transition.
     */
    #[ORM\ManyToOne(inversedBy: 'workflowLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    /**
     * Nom de la transition Symfony exécutée.
     */
    #[ORM\Column(length: 100)]
    private string $transition;

    /**
     * Statut d'origine avant transition.
     */
    #[ORM\Column(length: 100)]
    private string $fromStatus;

    /**
     * Statut cible après transition.
     */
    #[ORM\Column(length: 100)]
    private string $toStatus;

    /**
     * Identifiant utilisateur ayant déclenché l'action.
     * Null si action système (fixtures, automation).
     */
    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    /**
     * Date de création du log.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function setTransition(string $transition): self
    {
        $this->transition = $transition;
        return $this;
    }

    public function getFromStatus(): string
    {
        return $this->fromStatus;
    }

    public function setFromStatus(string $fromStatus): self
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): string
    {
        return $this->toStatus;
    }

    public function setToStatus(string $toStatus): self
    {
        $this->toStatus = $toStatus;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
