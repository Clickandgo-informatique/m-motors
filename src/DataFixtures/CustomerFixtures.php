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
    private $codeGenerator;
    private $passwordHasher;
    private Generator $faker;

    public function __construct(CustomerCodeGenerator $codeGenerator, UserPasswordHasherInterface $passwordHasher)
    {
        $this->codeGenerator = $codeGenerator;
        $this->passwordHasher = $passwordHasher;
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 50; $i++) {

            $first = $this->faker->firstName();
            $last = $this->faker->lastName();

            $customer = new Customer();

            $customer->setFirstName($first)
                ->setLastName($last)
                ->setEmail(strtolower($first . '.' . $last . '.' . $i . '@google.fr'));

            $prefix = strtoupper(substr($last, 0, 3));
            $customer->setCustomerCode($prefix . str_pad((string)$i, 3, '0', STR_PAD_LEFT));

            $user = new User();
            $user->setEmail($customer->getEmail());
            $user->setRoles(['ROLE_CUSTOMER']);

            $plainPassword = sprintf('%s-%s', $customer->getCustomerCode(), bin2hex(random_bytes(3)));

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $plainPassword)
            );

            $customer->setUser($user);

            $manager->persist($user);
            $manager->persist($customer);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['CustomerFixtures'];
    }
}
