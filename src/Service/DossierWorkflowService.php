<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\SupplierOrder;
use App\Entity\Vehicle;
use App\Enum\DossierDocumentStatus;
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

        // IMPORTANT :
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
    public function getCompletionRate(Dossier $dossier): float
    {
        $documents = $dossier->getDocuments();

        if ($documents->isEmpty()) {
            return 0;
        }

        $total = count($documents);
        $validated = 0;

        foreach ($documents as $doc) {
            if ($doc->getStatus() === DossierDocumentStatus::VALIDATED) {
                $validated++;
            }
        }

        return round(($validated / $total) * 100);
    }
    // Actualise le status du dossier en cours
    public function refreshDossierStatus(Dossier $dossier): void
    {
        $documents = $dossier->getDocuments();

        if ($documents->isEmpty()) {
            $dossier->setStatus('missing');
            return;
        }

        $allValidated = true;

        foreach ($documents as $doc) {
            if ($doc->getStatus() !== \App\Enum\DossierDocumentStatus::VALIDATED) {
                $allValidated = false;
            }
        }

        if ($allValidated) {
            $dossier->setStatus('validated');
        } else {
            $dossier->setStatus('in_progress');
        }
    }
    public function isLocked(Dossier $dossier): bool
    {
        return $dossier->getStatus() === 'validated';
    }
}
