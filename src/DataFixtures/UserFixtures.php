<?php

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
        //Création de l'admin
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Admin2026!');
        $user->setEmail("admin@m-motors.com")
            ->setRoles(['ROLE_ADMIN'])

            ->setPassword($hashedPassword)
        ;
        $manager->persist($user);

        //Création du Sale_Manager
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Admin2026!');
        $user->setEmail("commercial@m-motors.com")
            ->setRoles(['ROLE_MANAGER'])
            ->setPassword($hashedPassword)
            ->setNickname('Vendeur principal')
        ;
        $manager->persist($user);

        //Création de clients
        for ($i = 1; $i < 5; $i++) {
            $client = new User();
            $hashedPassword = $this->passwordHasher->hashPassword($client, 'Client2026!');
            $client->setEmail('client_' . $i . '@google.com')
                ->setNickname('Client ' . $i)
                ->setRoles(['ROLE_USER'])
                ->setPassword($hashedPassword);
        }

        $manager->flush();
    }
    public static function getGroups(): array
    {
        return ['UserFixtures'];
    }
}
