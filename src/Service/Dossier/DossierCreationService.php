<?php

namespace App\Service\Dossier;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DossierCreationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DossierCodeGenerator $codeGenerator
    ) {}

    /**
     * Crée un dossier à partir d'un véhicule.
     *
     * Si un dossier existe déjà pour le même client, le même véhicule et le
     * même type, il est simplement retourné.
     */
    public function createFromVehicle(
        Customer $customer,
        Vehicle $vehicle,
        DossierType $type
    ): array {
        if ($vehicle->isLocked()) {
            throw new AccessDeniedHttpException('véhicule indisponible');
        }

        $this->assertAllowedType($vehicle, $type);

        $existing = $this->em->getRepository(Dossier::class)->findOneBy([
            'customer' => $customer,
            'vehicle' => $vehicle,
            'type' => $type,
        ]);

        if ($existing !== null) {
            return [
                'dossier' => $existing,
                'created' => false,
            ];
        }

        $dossier = new Dossier();

        $dossier
            ->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($type)
            ->setDossierCode($this->codeGenerator->generate());

        $this->em->persist($dossier);
        $this->em->flush();

        return [
            'dossier' => $dossier,
            'created' => true,
        ];
    }

    /**
     * Vérifie que le type de dossier est autorisé pour le véhicule.
     */
    private function assertAllowedType(
        Vehicle $vehicle,
        DossierType $type
    ): void {
        $allowed = $vehicle->getUsageType()->allowedDossierTypes();

        if (!is_array($allowed)) {
            throw new \LogicException('allowedDossierTypes doit retourner un tableau');
        }

        $allowedValues = array_map(
            fn($t) => $t instanceof DossierType ? $t->value : $t,
            $allowed
        );

        if (!in_array($type->value, $allowedValues, true)) {
            throw new AccessDeniedHttpException(
                'type de dossier non autorisé pour ce véhicule'
            );
        }
    }
}
