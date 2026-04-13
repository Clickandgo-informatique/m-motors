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
        for ($i = 1; $i <= 50; $i++) {

            $first = $this->faker->firstName();
            $last  = $this->faker->lastName();

            $customer = new Customer();

            $customer
                ->setFirstName($first)
                ->setLastName($last)
                ->setEmail(strtolower($first . '.' . $last . '.' . $i . '@google.fr'))
                ->setCustomerCode(
                    strtoupper(substr($last, 0, 3)) . str_pad((string)$i, 3, '0', STR_PAD_LEFT)
                );

            $user = new User();
            $user->setEmail($customer->getEmail());
            $user->setRoles(['ROLE_CUSTOMER']);

            $plainPassword = sprintf(
                '%s-%s',
                $customer->getCustomerCode(),
                bin2hex(random_bytes(3))
            );

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $plainPassword)
            );

            // relation bidirectionnelle
            $customer->setUser($user);

            $manager->persist($user);
            $manager->persist($customer);

            // référence pour autres fixtures
            $this->addReference('customer_' . $i, $customer);

            // 🚀 BATCH FLUSH (OPTIMISATION PERFS)
            if ($i % 10 === 0) {
                $manager->flush();
              
            }
        }

        $manager->flush();
       
    }

    public static function getGroups(): array
    {
        return ['customerfixtures'];
    }
}
