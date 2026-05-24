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
            ) ? DossierType::RENTAL : DossierType::PURCHASE;

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
        switch ($scenario) {

            case 'draft':
                return;

            case 'vehicle_selected':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                return;

            case 'documents_pending':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                return;

            case 'documents_review':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');
                return;

            case 'financing_review':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');
                $this->workflowService->applySafe($dossier, 'validate_documents');
                return;

            case 'completed':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');
                $this->workflowService->applySafe($dossier, 'validate_documents');
                $this->workflowService->applySafe($dossier, 'approve_financing');
                $this->workflowService->applySafe($dossier, 'sign_order');
                return;

            case 'cancelled':
                $this->workflowService->applySafe($dossier, 'cancel');
                return;
        }
    }

    public static function getGroup(): array
    {
        return ['DossierFixtures'];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CustomerFixtures::class,
            VehicleFixtures::class,
        ];
    }
}
