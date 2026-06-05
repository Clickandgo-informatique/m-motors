<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\VehicleUsageType;
use App\Service\Dossier\DossierCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DossierCreationServiceTest extends TestCase
{
    public function testReturnsExistingDossier(): void
    {
        // vérifie qu'un dossier existant est retourné sans création

        $customer = $this->createMock(Customer::class);

        $vehicle = $this->createMock(Vehicle::class);
        $vehicle->method('isLocked')->willReturn(false);
        $vehicle->method('getUsageType')
            ->willReturn(VehicleUsageType::SALE);

        $existingDossier = new Dossier();

        $repository = $this->createMock(EntityRepository::class);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'customer' => $customer,
                'vehicle' => $vehicle,
                'type' => DossierType::PURCHASE,
            ])
            ->willReturn($existingDossier);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getRepository')
            ->with(Dossier::class)
            ->willReturn($repository);

        $em->expects($this->never())
            ->method('persist');

        $em->expects($this->never())
            ->method('flush');

        $service = new DossierCreationService($em);

        $result = $service->createFromVehicle(
            $customer,
            $vehicle,
            DossierType::PURCHASE
        );

        $this->assertSame($existingDossier, $result);
    }

    public function testCreatesNewDossier(): void
    {
        // vérifie la création d'un nouveau dossier

        $customer = $this->createMock(Customer::class);

        $vehicle = $this->createMock(Vehicle::class);
        $vehicle->method('isLocked')->willReturn(false);
        $vehicle->method('getUsageType')
            ->willReturn(VehicleUsageType::SALE);

        $repository = $this->createMock(EntityRepository::class);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedDossier = null;

        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getRepository')
            ->with(Dossier::class)
            ->willReturn($repository);

        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (mixed $dossier) use (
                        &$persistedDossier,
                        $customer,
                        $vehicle
                    ): bool {
                        $persistedDossier = $dossier;

                        $this->assertInstanceOf(
                            Dossier::class,
                            $dossier
                        );

                        $this->assertSame(
                            $customer,
                            $dossier->getCustomer()
                        );

                        $this->assertSame(
                            $vehicle,
                            $dossier->getVehicle()
                        );

                        $this->assertSame(
                            DossierType::PURCHASE,
                            $dossier->getType()
                        );

                        return true;
                    }
                )
            );

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierCreationService($em);

        $result = $service->createFromVehicle(
            $customer,
            $vehicle,
            DossierType::PURCHASE
        );

        $this->assertSame(
            $persistedDossier,
            $result
        );
    }

    public function testThrowsExceptionWhenVehicleIsLocked(): void
    {
        // vérifie qu'un véhicule verrouillé ne peut pas être utilisé

        $customer = $this->createMock(Customer::class);

        $vehicle = $this->createMock(Vehicle::class);
        $vehicle->method('isLocked')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);

        $service = new DossierCreationService($em);

        $this->expectException(
            AccessDeniedHttpException::class
        );

        $this->expectExceptionMessage(
            'Véhicule indisponible'
        );

        $service->createFromVehicle(
            $customer,
            $vehicle,
            DossierType::PURCHASE
        );
    }

    public function testThrowsExceptionWhenTypeIsNotAllowed(): void
    {
        // vérifie qu'un type de dossier interdit déclenche une exception

        $customer = $this->createMock(Customer::class);

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle->method('isLocked')
            ->willReturn(false);

        $vehicle->method('getUsageType')
            ->willReturn(VehicleUsageType::SALE);

        $em = $this->createMock(EntityManagerInterface::class);

        $service = new DossierCreationService($em);

        $this->expectException(
            AccessDeniedHttpException::class
        );

        $this->expectExceptionMessage(
            'Type de dossier non autorisé pour ce véhicule'
        );

        $service->createFromVehicle(
            $customer,
            $vehicle,
            DossierType::RENTAL
        );
    }
}