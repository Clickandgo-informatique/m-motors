<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\Financing;

class DossierFinancingService
{
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

    private function onFinancingReview(Financing $financing): void
    {
        if ($financing->getStatus() === 'pending') {
            $financing->setStatus('pending');
        }
    }

    private function onCompleted(Financing $financing): void
    {
        if ($financing->getStatus() !== 'approved') {
            $financing->setStatus('approved');
            $financing->setDecidedAt(new \DateTimeImmutable());
        }
    }

    private function onCancelled(Financing $financing): void
    {
        $financing->setStatus('rejected');
        $financing->setDecidedAt(new \DateTimeImmutable());
    }
}
