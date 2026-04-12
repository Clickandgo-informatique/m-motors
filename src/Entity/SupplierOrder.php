<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\SupplierOrderStatus;
use App\Enum\VehicleStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class SupplierOrder
{
    use TimestampableTrait;

    /*
    ===============================
    IDENTIFIANT
    ===============================
    */

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
    ===============================
    STATUT
    ===============================
    */

    #[ORM\Column(enumType: SupplierOrderStatus::class)]
    #[Assert\NotNull]
    private SupplierOrderStatus $status = SupplierOrderStatus::ORDERED;

    /*
    ===============================
    RELATIONS
    ===============================
    */

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le véhicule est obligatoire")]
    private Vehicle $vehicle;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le fournisseur est obligatoire")]
    private Supplier $supplier;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Dossier $dossier = null;

    /*
    ===============================
    DATES MÉTIER
    ===============================
    */

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $orderedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    /*
    ===============================
    GETTERS
    ===============================
    */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): SupplierOrderStatus
    {
        return $this->status;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function getOrderedAt(): ?\DateTimeImmutable
    {
        return $this->orderedAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /*
    ===============================
    SETTERS
    ===============================
    */

    public function setStatus(SupplierOrderStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setVehicle(Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;
        return $this;
    }

    /*
    ===============================
    BUSINESS LOGIC
    ===============================
    */

    /**
     * Commande passée chez le fournisseur
     */
    public function markAsOrdered(): self
    {
        $this->status = SupplierOrderStatus::ORDERED;
        $this->orderedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Livraison du véhicule
     * + libère le véhicule dans le stock
     */
    public function markAsDelivered(): self
    {
        if ($this->status === SupplierOrderStatus::CANCELLED) {
            throw new \LogicException('Commande annulée, livraison impossible.');
        }

        $this->status = SupplierOrderStatus::DELIVERED;
        $this->deliveredAt = new \DateTimeImmutable();

        $this->vehicle->setStatus(VehicleStatus::AVAILABLE);

        return $this;
    }

    /**
     * Annulation de la commande fournisseur
     */
    public function cancel(): self
    {
        if ($this->status === SupplierOrderStatus::DELIVERED) {
            throw new \LogicException('Commande déjà livrée, annulation impossible.');
        }

        $this->status = SupplierOrderStatus::CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();

        return $this;
    }

    /*
    ===============================
    HELPERS
    ===============================
    */

    public function isOrdered(): bool
    {
        return $this->status === SupplierOrderStatus::ORDERED;
    }

    public function isDelivered(): bool
    {
        return $this->status === SupplierOrderStatus::DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === SupplierOrderStatus::CANCELLED;
    }

    public function canBeDelivered(): bool
    {
        return $this->status === SupplierOrderStatus::ORDERED;
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== SupplierOrderStatus::DELIVERED;
    }
}
