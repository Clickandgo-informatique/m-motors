<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\EmailLog;
use App\Entity\Financing;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\DossierStatus;
use App\Enum\DossierType;
use App\Enum\EmailStatus;
use App\Enum\EmailType;
use App\Service\CustomerCodeGenerator;
use App\Service\Dossier\DossierCodeGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DemoWorkflowFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private CustomerCodeGenerator $customerCodeGenerator,
        private DossierCodeGenerator $dossierCodeGenerator,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $vehicles = $manager->getRepository(Vehicle::class)->findAll();

        if (count($vehicles) < 3) {
            throw new \RuntimeException('DemoWorkflowFixtures requires at least 3 vehicles.');
        }

        $vehicle1 = $vehicles[0];
        $vehicle2 = $vehicles[1];
        $vehicle3 = $vehicles[2];

        /*
         * customer + user unique demo
         */
        $customer = new Customer();
        $customer->setFirstName('Demo');
        $customer->setLastName('User');
        $customer->setCustomerCode(
            $this->customerCodeGenerator->generateCustomerCode($customer->getLastName())
        );
        $customer->setEmail('userdemo@mail.com');
        $customer->setPhoneNumber1('0600000000');

        $manager->persist($customer);

        $user = new User();
        $user->setEmail('userdemo@mail.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Admin2026!')
            );

        $user->setCustomer($customer);

        $manager->persist($user);

        /*
         * dossier 1 - FULL WORKFLOW
         */
        $dossierFull = new Dossier();
        $dossierFull->setCustomer($customer);
        $dossierFull->setVehicle($vehicle1);
        $dossierFull->setCreatedBy($user);
        $dossierFull->setStatus(DossierStatus::COMPLETED);
        $dossierFull->setType(DossierType::PURCHASE);
        $dossierFull->setDossierCode(
            $this->dossierCodeGenerator->generate('DEMO-001')
        );

        $manager->persist($dossierFull);

        if ($dossierFull->getFinancing() === null) {
            $financing = new Financing();
            $financing->setDossier($dossierFull);
            $financing->setAmount(22000);
            $financing->setDurationMonths(48);

            $manager->persist($financing);
        }

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossierFull,
            EmailType::DOSSIER_CREATED,
            EmailStatus::SENT
        );

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossierFull,
            EmailType::CONTRACT_AVAILABLE,
            EmailStatus::SENT
        );

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossierFull,
            EmailType::VEHICLE_ASSIGNED,
            EmailStatus::SENT
        );

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossierFull,
            EmailType::CONTRACT_SIGNED,
            EmailStatus::SENT
        );

        /*
         * dossier 2 - FINANCING REVIEW
         */
        $dossier2 = new Dossier();
        $dossier2->setCustomer($customer);
        $dossier2->setVehicle($vehicle2);
        $dossier2->setCreatedBy($user);
        $dossier2->setStatus(DossierStatus::FINANCING_REVIEW);
        $dossier2->setType(DossierType::PURCHASE);
        $dossier2->setDossierCode(
            $this->dossierCodeGenerator->generate('DEMO-002')
        );

        $manager->persist($dossier2);

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossier2,
            EmailType::DOSSIER_CREATED,
            EmailStatus::SENT
        );

        /*
         * dossier 3 - DOCUMENTS PENDING
         */
        $dossier3 = new Dossier();
        $dossier3->setCustomer($customer);
        $dossier3->setVehicle($vehicle3);
        $dossier3->setCreatedBy($user);
        $dossier3->setStatus(DossierStatus::DOCUMENTS_PENDING);
        $dossier3->setType(DossierType::PURCHASE);
        $dossier3->setDossierCode(
            $this->dossierCodeGenerator->generate('DEMO-003')
        );

        $manager->persist($dossier3);

        $this->createEmail(
            $manager,
            $user,
            $customer,
            $dossier3,
            EmailType::DOSSIER_CREATED,
            EmailStatus::SENT
        );

        $manager->flush();
    }

    private function createEmail(
        ObjectManager $manager,
        User $user,
        Customer $customer,
        Dossier $dossier,
        EmailType $type,
        EmailStatus $status
    ): void {
        $log = new EmailLog();

        $log->setSender($user->getEmail());
        $log->setRecipient($customer->getEmail());

        $log->setSubject($type->getLabel());

        // FIX CRITIQUE MANQUANT
        $log->setTemplateName($type->value);

        $log->setType($type);
        $log->setStatus($status);
        $log->setDossier($dossier);

        $manager->persist($log);
    }

    public function getDependencies(): array
    {
        return [
            VehicleFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['dossierworkflowdemo'];
    }
}
