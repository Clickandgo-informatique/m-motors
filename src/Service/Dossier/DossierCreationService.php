<?php

namespace App\Service\Dossier;

use App\Entity\Customer;
use App\Entity\Vehicle;
use App\Entity\Dossier;
use App\Enum\DossierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DossierCreationService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function createFromVehicle(
        Customer $customer,
        Vehicle $vehicle,
        DossierType $type
    ): Dossier {

        if ($vehicle->isLocked()) {
            throw new AccessDeniedHttpException('Véhicule indisponible');
        }

        $this->assertAllowedType($vehicle, $type);

        $existing = $this->em->getRepository(Dossier::class)
            ->findOneBy([
                'customer' => $customer,
                'vehicle' => $vehicle,
                'type' => $type
            ]);

        if ($existing) {
            return $existing;
        }

        $dossier = new Dossier();

        $dossier
            ->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($type);

        $this->em->persist($dossier);
        $this->em->flush();

        return $dossier;
    }

    private function assertAllowedType(Vehicle $vehicle, DossierType $type): void
    {
        $allowed = $vehicle->getUsageType()->allowedDossierTypes();

        if (!in_array($type, $allowed, true)) {
            throw new AccessDeniedHttpException(
                'Type de dossier non autorisé pour ce véhicule'
            );
        }
    }
}
