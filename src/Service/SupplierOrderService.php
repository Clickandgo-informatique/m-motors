<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\Supplier;
use App\Entity\SupplierOrder;
use App\Enum\VehicleStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier des commandes fournisseurs
 *
 * Centralise toute la logique :
 * - livraison
 * - annulation
 * - synchronisation véhicule
 */
class SupplierOrderService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Marque une commande comme livrée
     * et met à jour le véhicule associé
     */
    public function deliver(SupplierOrder $order): void
    {
        $order->markAsDelivered();

        $vehicle = $order->getVehicle();
        $vehicle->setStatus(VehicleStatus::AVAILABLE);

        $this->em->flush();
    }

    /**
     * Annule une commande fournisseur
     * et remet le véhicule dans un état cohérent
     */
    public function cancel(SupplierOrder $order): void
    {
        $order->cancel();

        $vehicle = $order->getVehicle();
        $vehicle->setStatus(VehicleStatus::AVAILABLE);

        $this->em->flush();
    }
    public function createFromDossier(Dossier $dossier, Supplier $supplier): SupplierOrder
    {
        $order = new SupplierOrder();

        $order->setVehicle($dossier->getVehicle());
        $order->setSupplier($supplier);
        $order->setDossier($dossier);

        $order->markAsOrdered();

        // véhicule réservé en attendant livraison
        $dossier->getVehicle()->setStatus(VehicleStatus::ORDERED);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}
