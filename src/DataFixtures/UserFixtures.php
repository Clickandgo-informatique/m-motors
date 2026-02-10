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
        $manager->flush();
    }
}
