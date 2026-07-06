<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\EmailLog;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\EmailStatus;
use App\Enum\EmailType;
use App\Enum\FinancingType;
use App\Repository\EmailLogRepository;
use App\Service\CustomerCodeGenerator;
use App\Service\Dossier\DossierWorkflowService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * génération de dossiers via workflow Symfony
 *
 * règle :
 * - jamais de logique métier directe sur status
 * - toujours passer par workflowService
 * - financement géré uniquement via l'entité Financing
 * - emails simulés pour reproduire un comportement production
 */
class DossierFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private CustomerCodeGenerator $codeGenerator,
        private DossierWorkflowService $workflowService,
        private EmailLogRepository $emailLogRepository
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

            $this->applyScenario($dossier, $scenario, $manager);

            $manager->flush();
        }
    }

    /**
     * application des scénarios workflow dossier
     */
    private function applyScenario(Dossier $dossier, string $scenario, ObjectManager $manager): void
    {
        switch ($scenario) {

            case 'draft':
                return;

            case 'vehicle_selected':
                $this->workflowService->applySafe($dossier, 'select_vehicle');

                $this->createEmailLog(
                    $dossier,
                    EmailType::VEHICLE_ASSIGNED,
                    'Votre véhicule a été sélectionné'
                );
                return;

            case 'documents_pending':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');

                $this->createEmailLog(
                    $dossier,
                    EmailType::DOCUMENT_REQUEST,
                    'Demande de documents pour votre dossier'
                );
                return;

            case 'documents_review':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');

                $this->createEmailLog(
                    $dossier,
                    EmailType::DOCUMENT_RECEIVED,
                    'Documents bien reçus, analyse en cours'
                );
                return;

            case 'financing_review':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');
                $this->workflowService->applySafe($dossier, 'validate_documents');

                $this->createEmailLog(
                    $dossier,
                    EmailType::DOSSIER_UPDATED,
                    'Votre financement est en cours d’analyse'
                );
                return;

            case 'completed':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'request_documents');
                $this->workflowService->applySafe($dossier, 'submit_documents');
                $this->workflowService->applySafe($dossier, 'validate_documents');
                $this->workflowService->applySafe($dossier, 'approve_financing');
                $this->workflowService->applySafe($dossier, 'sign_order');

                $this->createEmailLog(
                    $dossier,
                    EmailType::CONTRACT_AVAILABLE,
                    'Votre contrat est disponible'
                );

                $this->createEmailLog(
                    $dossier,
                    EmailType::CONTRACT_SIGNED,
                    'Signature du contrat en attente'
                );
                return;

            case 'cancelled':
                $this->workflowService->applySafe($dossier, 'select_vehicle');
                $this->workflowService->applySafe($dossier, 'cancel');

                $this->createEmailLog(
                    $dossier,
                    EmailType::DOSSIER_UPDATED,
                    'Votre dossier a été annulé'
                );
                return;
        }
    }

    /**
     * création d’un email log réaliste (simulation production SMTP + provider)
     */
    private function createEmailLog(
        Dossier $dossier,
        EmailType $type,
        string $subject
    ): void {
        $email = new EmailLog();

        $email->setDossier($dossier);
        $email->setUser($dossier->getCustomer()->getUser() ?? null);

        $email->setSender('noreply@m-motors.local');
        $email->setRecipient($dossier->getCustomer()->getEmail());

        $email->setSubject($subject);
        $email->setTemplateName($type->value);
        $email->setType($type);

        $email->setSentAt(new \DateTimeImmutable());

        $outcome = random_int(1, 100);

        if ($outcome <= 8) {
            $email->setStatus(EmailStatus::FAILED);
            $email->setFailureReason('SMTP rejected or mailbox unavailable');
            $email->setMessageId(null);
        } elseif ($outcome <= 70) {
            $email->setStatus(EmailStatus::SENT);
            $email->setMessageId('msg_' . bin2hex(random_bytes(8)));
        } else {
            $email->setStatus(EmailStatus::DELIVERED);
            $email->setMessageId('msg_' . bin2hex(random_bytes(8)));
        }

        $this->emailLogRepository->save($email, true);
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
