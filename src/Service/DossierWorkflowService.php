<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\SupplierOrder;
use App\Enum\VehicleStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service central du workflow métier des dossiers.
 *
 * Responsabilités :
 * - Orchestration des décisions métier
 * - Création des commandes fournisseurs
 * - Garantie de cohérence des statuts véhicule + dossier
 */
class DossierWorkflowService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Point d'entrée principal : validation d'un dossier
     */
    public function approve(Dossier $dossier): void
    {
        $vehicle = $dossier->getVehicle();

        /*
        =========================================================
        CAS 1 : véhicule indisponible → commande fournisseur
        =========================================================
        */
        if (!$vehicle->isAvailable()) {
            $this->handleUnavailableVehicle($dossier, $vehicle);
        }

        /*
        =========================================================
        CAS 2 : véhicule disponible → traitement métier
        =========================================================
        */ else {
            $this->handleAvailableVehicle($dossier, $vehicle);
        }

        /*
        =========================================================
        Finalisation dossier
        =========================================================
        */
        $dossier->approve();

        $this->em->flush();
    }

    /**
     * Véhicule indisponible :
     * création commande fournisseur si nécessaire
     */
    private function handleUnavailableVehicle(Dossier $dossier, $vehicle): void
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
     * Véhicule disponible :
     * traitement métier du dossier
     */
    private function handleAvailableVehicle(Dossier $dossier, $vehicle): void
    {
        /*
        IMPORTANT :
        Ne pas mettre RESERVED ici si déjà réservé via submit()
        sinon double transition incohérente
        */

        if ($dossier->isLeasing()) {
            $this->handleLeasing($vehicle);
        } else {
            $this->handlePurchase($vehicle);
        }
    }

    /**
     * Achat classique
     */
    private function handlePurchase($vehicle): void
    {
        $vehicle->setStatus(VehicleStatus::SOLD);
    }

    /**
     * Leasing (LOA / LLD)
     */
    private function handleLeasing($vehicle): void
    {
        $vehicle->setStatus(VehicleStatus::RENTED);
    }
}
