<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Enum\DossierType;

class DossierTypeSwitchService
{
    /**
     * bascule achat <-> location avec synchronisation financement
     */
    public function switchType(Dossier $dossier, DossierType $newType): void
    {
        $oldType = $dossier->getType();

        if ($oldType === $newType) {
            return;
        }

        $dossier->setType($newType);

        $financing = $dossier->getFinancing();

        if (!$financing) {
            return;
        }

        /**
         * passage en location
         */
        if ($newType->isRental()) {
            $financing->setType('leasing');

            // si pas encore défini → valeur par défaut démonstration
            if ($financing->getLeasingType() === null) {
                $financing->setLeasingType('loa');
            }

            return;
        }

        /**
         * passage en achat
         */
        if ($newType->isPurchase()) {
            // on garde flexibilité, pas de destruction de données
            if ($financing->getType() === 'leasing') {
                $financing->setType('credit');
            }
        }
    }
}
