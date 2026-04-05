<?php
// src/DataFixtures/UserFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // -----------------------------
        // ADMIN
        // -----------------------------
        $admin = new User();
        $admin->setEmail('admin@m-motors.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'Admin2026!'));
    
        $manager->persist($admin);

        // -----------------------------
        // MANAGER / SALE
        // -----------------------------
        $managerUser = new User();
        $managerUser->setEmail('commercial@m-motors.com')
            ->setRoles(['ROLE_MANAGER'])
            ->setNickname('Vendeur principal')
            ->setPassword($this->passwordHasher->hashPassword($managerUser, 'Admin2026!'));
    
        $manager->persist($managerUser);

        // -----------------------------
        // CLIENTS
        // -----------------------------
        for ($i = 1; $i <= 4; $i++) {
            $client = new User();
            $client->setEmail('client_' . $i . '@google.com')
                ->setNickname('Client ' . $i)
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->passwordHasher->hashPassword($client, 'Client2026!'));
         
            $manager->persist($client);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['UserFixtures'];
    }
}
