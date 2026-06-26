<?php

namespace App\EventListener;

use App\Entity\Dossier;
use App\Entity\Financing;

class DossierFinancingListener
{
    public function prePersist(Dossier $dossier): void
    {
        if ($dossier->getFinancing() !== null) {
            return;
        }

        $financing = new Financing();
        $financing->setStatus('pending');
        $financing->setType('cash');

        $dossier->setFinancing($financing);
        $financing->setDossier($dossier);
    }
}
