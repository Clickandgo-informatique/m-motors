<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\CustomerCodeGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * création des utilisateurs et clients de test
 */
class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private CustomerCodeGenerator $customerCodeGenerator
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // admin
        $admin = new User();

        $admin->setEmail('admin@m-motors.com')

            ->setRoles(['ROLE_ADMIN'])
            ->setPassword(
                $this->passwordHasher->hashPassword($admin, 'Admin2026!')
            );

        $adminCustomer = new Customer();

        $adminCustomer->setUser($admin);
        $adminCustomer->setFirstName('admin');
        $adminCustomer->setLastName('system');
        $adminCustomer->setEmail($admin->getEmail());

        $adminCustomer->setCustomerCode(
            $this->customerCodeGenerator->generateCustomerCodeFixture('system', 1)
        );

        $admin->setCustomer($adminCustomer);

        $manager->persist($admin);
        $manager->persist($adminCustomer);

        // manager
        $managerUser = new User();

        $managerUser->setEmail('commercial@m-motors.com')
            ->setNickname('vendeur principal')

            ->setRoles(['ROLE_MANAGER'])
            ->setPassword(
                $this->passwordHasher->hashPassword($managerUser, 'Admin2026!')
            );

        $managerCustomer = new Customer();

        $managerCustomer->setUser($managerUser);
        $managerCustomer->setFirstName('manager');
        $managerCustomer->setLastName('sales');
        $managerCustomer->setEmail($managerUser->getEmail());

        $managerCustomer->setCustomerCode(
            $this->customerCodeGenerator->generateCustomerCodeFixture('sales', 2)
        );

        $managerUser->setCustomer($managerCustomer);

        $manager->persist($managerUser);
        $manager->persist($managerCustomer);

        // Client demo        

        $client = new User();

        $client->setEmail('client@mail.com')

            ->setNickname('Client_Demo')

            ->setRoles(['ROLE_USER'])
            ->setPassword(
                $this->passwordHasher->hashPassword($client, 'Client2026!')
            );

        $customer = new Customer();

        $customer->setUser($client);
        $customer->setFirstName('Client');
        $customer->setLastName('Demo');
        $customer->setEmail($client->getEmail());

        $customer->setCustomerCode(
            $this->customerCodeGenerator->generateCustomerCode('Demo')
        );

        $client->setCustomer($customer);

        $manager->persist($client);
        $manager->persist($customer);


        $manager->flush();
    }
}
