<?php

namespace App\Service\Financing;

use App\Entity\Dossier;
use App\Entity\Financing;

class DossierFinancingService
{
    /**
     * Synchronise l'état du financement à partir de l'état du dossier.
     * Crée automatiquement une entité Financing si elle n'existe pas encore.
     */
    public function syncFromDossier(Dossier $dossier): void
    {
        $financing = $dossier->getFinancing();

        if (!$financing) {
            $financing = new Financing();
            $financing->setDossier($dossier);
            $dossier->setFinancing($financing);
        }

        match ($dossier->getStatus()) {
            'financing_review' => $this->onFinancingReview($financing),
            'completed' => $this->onCompleted($financing),
            'cancelled' => $this->onCancelled($financing),
            default => null,
        };
    }

    /**
     * Gère la transition du financement lorsque le dossier passe en phase de revue financement.
     */
    private function onFinancingReview(Financing $financing): void
    {
        if ($financing->getStatus() === 'pending') {
            $financing->setStatus('pending');
        }
    }

    /**
     * Gère la transition du financement lorsque le dossier est marqué comme terminé.
     * Passe le financement en statut approuvé et fixe la date de décision si nécessaire.
     */
    private function onCompleted(Financing $financing): void
    {
        if ($financing->getStatus() !== 'approved') {
            $financing->setStatus('approved');
            $financing->setDecidedAt(new \DateTimeImmutable());
        }
    }

    /**
     * Gère la transition du financement lorsque le dossier est annulé.
     * Passe le financement en statut rejeté et fixe la date de décision.
     */
    private function onCancelled(Financing $financing): void
    {
        $financing->setStatus('rejected');
        $financing->setDecidedAt(new \DateTimeImmutable());
    }

    /**
     * Approuve explicitement un financement lié à un dossier.
     * Crée le financement si nécessaire puis applique le statut approuvé.
     */
    public function approve(Dossier $dossier): void
    {
        $financing = $dossier->getFinancing();

        if (!$financing) {
            $financing = new Financing();
            $financing->setDossier($dossier);
            $dossier->setFinancing($financing);
        }

        $financing->setStatus('approved');
        $financing->setDecidedAt(new \DateTimeImmutable());
    }
}
