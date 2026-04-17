<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\SupplierOrder;
use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use Doctrine\ORM\EntityManagerInterface;

class DossierWorkflowService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Validation d’un dossier (entrée métier)
     */
    public function approve(Dossier $dossier): void
    {
        $vehicle = $dossier->getVehicle();

        if (!$vehicle instanceof Vehicle) {
            return;
        }

        // =========================
        // CAS : véhicule indisponible
        // =========================
        if (!$vehicle->isAvailable()) {
            $this->handleUnavailableVehicle($dossier, $vehicle);
        } else {
            $this->handleAvailableVehicle($dossier, $vehicle);
        }

        // ⚠️ IMPORTANT :
        // On ne touche PLUS au statut du dossier ici
        // → géré par Symfony Workflow uniquement
        $this->em->flush();
    }

    /**
     * Véhicule indisponible → commande fournisseur
     */
    private function handleUnavailableVehicle(Dossier $dossier, Vehicle $vehicle): void
    {
        if ($vehicle->isOrdered()) {
            return;
        }

        $order = new SupplierOrder();
        $order->setVehicle($vehicle);
        $order->setSupplier($vehicle->getSupplier());
        $order->setDossier($dossier);

        $vehicle->setStatus(VehicleStatus::ORDERED);

        $this->em->persist($order);
    }

    /**
     * Véhicule disponible → logique métier
     */
    private function handleAvailableVehicle(Dossier $dossier, Vehicle $vehicle): void
    {
        if ($dossier->isLeasing()) {
            $this->handleLeasing($vehicle);
        } else {
            $this->handlePurchase($vehicle);
        }
    }

    /**
     * Achat
     */
    private function handlePurchase(Vehicle $vehicle): void
    {
        $vehicle->setStatus(VehicleStatus::SOLD);
    }

    /**
     * Leasing
     */
    private function handleLeasing(Vehicle $vehicle): void
    {
        $vehicle->setStatus(VehicleStatus::RENTED);
    }
}