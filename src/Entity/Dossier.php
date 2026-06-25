<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\DossierStatus;
use App\Enum\DossierType;
use App\EventListener\DossierFinancingListener;
use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\EntityListeners([DossierFinancingListener::class])]
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Dossier
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne]
    private ?User $validatedBy = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\Column(enumType: DossierType::class)]
    private ?DossierType $type = null;

    /**
     * code unique métier du dossier
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $dossierCode = null;

    /**
     * statut du workflow dossier
     */
    #[ORM\Column(enumType: DossierStatus::class)]
    private DossierStatus $status = DossierStatus::DRAFT;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DossierWorkflowLog::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $workflowLogs;


    /**
     * date de finalisation du dossier
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /**
     * date d'annulation du dossier
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    /**
     * documents liés au dossier
     */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DossierDocument::class, cascade: ['persist'])]
    private Collection $documents;

    /**
     * financement associé (source unique de vérité métier financement)
     */
    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: Financing::class, cascade: ['persist', 'remove'])]
    private ?Financing $financing = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->workflowLogs = new ArrayCollection();
    }

    /**
     * @return Collection<int, DossierWorkflowLog>
     */
    public function getWorkflowLogs(): Collection
    {
        return $this->workflowLogs;
    }

    public function addWorkflowLog(DossierWorkflowLog $log): self
    {
        if (!$this->workflowLogs->contains($log)) {
            $this->workflowLogs->add($log);
            $log->setDossier($this);
        }

        return $this;
    }

    public function removeWorkflowLog(DossierWorkflowLog $log): self
    {
        $this->workflowLogs->removeElement($log);

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
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

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): DossierStatus
    {
        return $this->status;
    }

    public function setStatus(DossierStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDossierCode(): ?string
    {
        return $this->dossierCode;
    }

    public function setDossierCode(string $code): self
    {
        $this->dossierCode = $code;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * relation financement (source unique)
     */
    public function getFinancing(): ?Financing
    {
        return $this->financing;
    }

    public function setFinancing(?Financing $financing): self
    {
        $this->financing = $financing;

        if ($financing && $financing->getDossier() !== $this) {
            $financing->setDossier($this);
        }

        return $this;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }



    /* helper métier : vérifie si financement existant */
    public function hasFinancing(): bool
    {
        return $this->financing !== null;
    }
}
