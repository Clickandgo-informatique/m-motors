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
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * generation de dossiers via workflow symfony
 *
 * règle :
 * - jamais de logique métier directe sur status
 * - toujours passer par workflowService
 * - financement géré uniquement via l'entité Financing
 */
class DossierFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
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
            throw new \RuntimeException('customers ou vehicles manquants');
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

        $completedForced = 0;
        $maxCompleted = 5;

        for ($i = 0; $i < 30; $i++) {

            $customer = $customers[array_rand($customers)];
            $vehicle  = $vehicles[array_rand($vehicles)];

            $dossier = new Dossier();
            $dossier->setCustomer($customer);
            $dossier->setVehicle($vehicle);

            $dossier->setType(
                DossierType::cases()[array_rand(DossierType::cases())]
            );

            $dossier->setDossierCode(
                $this->codeGenerator->generateDossierCode($customer)
            );

            $manager->persist($dossier);
            $manager->flush();

            $financing = $dossier->getFinancing();

            if ($financing) {
                $financingTypes = FinancingType::cases();
                $financingType = $financingTypes[array_rand($financingTypes)];

                $financing->setType($financingType->value);

                if ($financingType->value === 'leasing') {
                    $financing->setLeasingType(
                        random_int(0, 1) === 0 ? 'loa' : 'lld'
                    );
                }

                $financing->setStatus('pending');
            }

            $manager->flush();

            if ($completedForced < $maxCompleted) {
                $scenario = 'completed';
                $completedForced++;
            } else {
                $scenario = $scenarios[array_rand($scenarios)];
            }

            $this->applyScenario($dossier, $scenario);

            $manager->flush();
        }
    }

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
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'cancel');
                return;
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CustomerFixtures::class,
            VehicleFixtures::class,
        ];
    }
    public static function getGroups(): array
    {
        return ['dossier'];
    }
}
