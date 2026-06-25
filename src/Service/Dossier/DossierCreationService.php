<?php

namespace App\Service\Dossier;

use App\Entity\Customer;
use App\Entity\Vehicle;
use App\Entity\Dossier;
use App\Entity\DossierWorkflowLog;
use App\Enum\DossierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DossierCreationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DossierCodeGenerator $codeGenerator
    ) {}

    public function createFromVehicle(
        Customer $customer,
        Vehicle $vehicle,
        DossierType $type
    ): array {
        // vérifie si le véhicule est bloqué
        if ($vehicle->isLocked()) {
            throw new AccessDeniedHttpException('véhicule indisponible');
        }

        // vérifie si le type de dossier est autorisé pour ce véhicule
        $this->assertAllowedType($vehicle, $type);

        // recherche d’un dossier existant
        $existing = $this->em->getRepository(Dossier::class)
            ->findOneBy([
                'customer' => $customer,
                'vehicle' => $vehicle,
                'type' => $type
            ]);

        // si déjà existant, on retourne sans recréer
        if ($existing) {
            return [
                'dossier' => $existing,
                'created' => false
            ];
        }

        // création du dossier
        $dossier = new Dossier();

        $dossier
            ->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($type);

        // génération du code dossier
        $dossier->setDossierCode(
            $this->codeGenerator->generate()
        );

        $this->em->persist($dossier);

        // log initial pour alimenter la timeline
        $log = new DossierWorkflowLog();

        $log = new DossierWorkflowLog();

        $log
            ->setDossier($dossier)
            ->setTransition('create')
            ->setFromStatus('new')
            ->setToStatus('created');

        $this->em->persist($log);

        $this->em->flush();

        return [
            'dossier' => $dossier,
            'created' => true
        ];
    }

    private function assertAllowedType(Vehicle $vehicle, DossierType $type): void
    {
        $allowed = $vehicle->getUsageType()->allowedDossierTypes();

        if (!in_array($type, $allowed, true)) {
            throw new AccessDeniedHttpException(
                'type de dossier non autorisé pour ce véhicule'
            );
        }
    }
}
