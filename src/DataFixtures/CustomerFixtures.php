<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\CustomerCodeGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use Faker\Generator;

class CustomerFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private CustomerCodeGenerator $codeGenerator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $batchSize = 20;

        for ($i = 1; $i <= 50; $i++) {

            $first = $this->faker->firstName();
            $last  = $this->faker->lastName();

            $email = strtolower($first . '.' . $last . '.' . $i . '@google.fr');

            $customer = new Customer();
            $customer
                ->setFirstName($first)
                ->setLastName($last)
                ->setEmail($email)
                ->setCustomerCode(
                    strtoupper(substr($last, 0, 3)) . str_pad((string)$i, 3, '0', STR_PAD_LEFT)
                )
                ->setAddress($this->faker->streetAddress())
                ->setZipCode($this->faker->postcode())
                ->setCity($this->faker->city())
                ->setAddressDetails('Détails de l\'adresse du client');

            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_CUSTOMER']);

            $plainPassword = sprintf(
                '%s-%s',
                $customer->getCustomerCode(),
                bin2hex(openssl_random_pseudo_bytes(3))
            );

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $plainPassword)
            );

            $customer->setUser($user);

            $manager->persist($user);
            $manager->persist($customer);

            // IMPORTANT : références stables pour autres fixtures
            $this->addReference('customer_' . $i, $customer);

            if ($i % $batchSize === 0) {
                $manager->flush();                
            }
        }

        $manager->flush();
    }
}