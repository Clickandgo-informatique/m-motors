<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use App\Service\CustomerCodeGenerator;
use App\Service\DossierWorkflowService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/*
 * Création de dossiers réalistes via le workflow Symfony.
 *
 * Objectifs :
 * - utiliser les vraies transitions métier
 * - produire plusieurs états réalistes
 * - synchroniser automatiquement véhicule + financement
 * - éviter les statuts forcés manuellement
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

        /*
         * États métier à représenter dans l’application.
         */
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

            /*
             * Sélection aléatoire stable.
             */
            $customer = $customers[array_rand($customers)];
            $vehicle  = $vehicles[array_rand($vehicles)];

            /*
             * Type de financement réaliste.
             */
            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            /*
             * Détermination automatique du type de dossier.
             */
            $type = in_array(
                $financingType,
                [FinancingType::LOA, FinancingType::LLD],
                true
            ) ? DossierType::RENTAL : DossierType::SALE;

            /*
             * Création du dossier.
             */
            $dossier = new Dossier();

            $dossier->setCustomer($customer);
            $dossier->setVehicle($vehicle);
            $dossier->setType($type);

            /*
             * Le workflow démarre toujours en draft.
             */
            $dossier->setStatus('draft');

            /*
             * Code métier unique.
             */
            $dossier->setDossierCode(
                $this->codeGenerator->generateDossierCode($customer)
            );

            /*
             * Type de financement choisi.
             */
            $dossier->setFinancingType($financingType->value);

            $manager->persist($dossier);

            /*
             * Application d’un scénario métier réaliste.
             */
            $scenario = $scenarios[array_rand($scenarios)];

            $this->applyScenario($dossier, $scenario);
        }

        $manager->flush();
    }

    /*
     * Application des transitions workflow selon le scénario choisi.
     */
    private function applyScenario(Dossier $dossier, string $scenario): void
    {
        switch ($scenario) {

            case 'vehicle_selected':
                $this->workflowService->selectVehicle($dossier);
                break;

            case 'documents_pending':
                $this->workflowService->selectVehicle($dossier);
                $this->workflowService->requestDocuments($dossier);
                break;

            case 'documents_review':
                $this->workflowService->selectVehicle($dossier);
                $this->workflowService->requestDocuments($dossier);
                $this->workflowService->submitDocuments($dossier);
                break;

            case 'financing_review':
                $this->workflowService->selectVehicle($dossier);
                $this->workflowService->requestDocuments($dossier);
                $this->workflowService->submitDocuments($dossier);
                $this->workflowService->validateDocuments($dossier);
                break;

            case 'completed':
                $this->workflowService->selectVehicle($dossier);
                $this->workflowService->requestDocuments($dossier);
                $this->workflowService->submitDocuments($dossier);
                $this->workflowService->validateDocuments($dossier);
                $this->workflowService->approveFinancing($dossier);
                break;

            case 'cancelled':
                $this->workflowService->cancel($dossier);
                break;
        }
    }

    public static function getGroups(): array
    {
        return ['DossierFixtures'];
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            VehicleFixtures::class,
        ];
    }
}
