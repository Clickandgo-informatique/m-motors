<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use App\Service\CustomerCodeGenerator;
use App\Service\Dossier\DossierWorkflowService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures de génération de dossiers réalistes via le workflow Symfony.
 *
 * Règles :
 * - toujours utiliser les transitions Symfony (jamais de setStatus logique métier)
 * - ne jamais dépendre de getStatus pour piloter le flux
 * - utiliser can() pour sécuriser les transitions
 */
class DossierFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private CustomerCodeGenerator $codeGenerator,
        private DossierWorkflowService $workflowService
    ) {}

    public function load(ObjectManager $manager): void
    {
        $customers = $manager->getRepository(Customer::class)->findAll();
        $vehicles  = $manager->getRepository(Vehicle::class)->findAll();

        if (!$customers || !$vehicles) {
            throw new \RuntimeException('Customers ou Vehicles manquants.');
        }

        $scenarios = [
            'draft',
            'vehicle_selected',
            'documents_pending',
            'documents_review',
            'financing_review',
            'completed',
            'cancelled',
        ];

        for ($i = 0; $i < 30; $i++) {

            $customer = $customers[array_rand($customers)];
            $vehicle  = $vehicles[array_rand($vehicles)];

            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            $type = in_array(
                $financingType,
                [FinancingType::LOA, FinancingType::LLD],
                true
            ) ? DossierType::RENTAL : DossierType::SALE;

            $dossier = new Dossier();

            $dossier->setCustomer($customer);
            $dossier->setVehicle($vehicle);
            $dossier->setType($type);

            $dossier->setStatus('draft');

            $dossier->setDossierCode(
                $this->codeGenerator->generateDossierCode($customer)
            );

            $dossier->setFinancingType($financingType->value);

            $manager->persist($dossier);
            $manager->flush();

            $scenario = $scenarios[array_rand($scenarios)];

            $this->applyScenario($dossier, $scenario);

            $manager->flush();
        }
    }

    /**
     * Application SAFE du scénario via workflow Symfony.
     * Aucune dépendance au status réel.
     */
    private function applyScenario(Dossier $dossier, string $scenario): void
    {
        // 1. Select vehicle
        $this->workflowService->applySafe($dossier, 'select_vehicle');

        if ($scenario === 'draft') {
            return;
        }

        // 2. Request documents
        $this->workflowService->applySafe($dossier, 'request_documents');

        if ($scenario === 'vehicle_selected') {
            return;
        }

        // 3. Submit documents
        $this->workflowService->applySafe($dossier, 'submit_documents');

        if ($scenario === 'documents_pending') {
            return;
        }

        // 4. Validate documents
        $this->workflowService->applySafe($dossier, 'validate_documents');

        if ($scenario === 'documents_review') {
            return;
        }

        // 5. Approve financing
        $this->workflowService->applySafe($dossier, 'approve_financing');

        if ($scenario === 'financing_review') {
            return;
        }

        // 6. Cancel override
        if ($scenario === 'cancelled') {
            $this->workflowService->applySafe($dossier, 'cancel');
        }
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            VehicleFixtures::class,
        ];
    }
}