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

    /**
     * Génère un secret TOTP Base32
     */
    private function generateTotpSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    public function load(ObjectManager $manager): void
    {
        // -----------------------------
        // Admin
        // -----------------------------
        $user = new User();
        $secret = $this->generateTotpSecret();
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Admin2026!');
        $user->setEmail("admin@m-motors.com")
            ->setRoles(['ROLE_ADMIN'])
            ->setTotpSecret($secret)
            ->enableTotp()
            ->setPassword($hashedPassword);
        $manager->persist($user);

        // -----------------------------
        // Manager / Sale
        // -----------------------------
        $user = new User();
        $secret = $this->generateTotpSecret();
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Admin2026!');
        $user->setEmail("commercial@m-motors.com")
            ->setRoles(['ROLE_MANAGER'])
            ->setNickname('Vendeur principal')
            ->setTotpSecret($secret)
            ->enableTotp()
            ->setPassword($hashedPassword);
        $manager->persist($user);

        // -----------------------------
        // Clients
        // -----------------------------
        for ($i = 1; $i <= 4; $i++) {
            $client = new User();
            $secret = $this->generateTotpSecret();
            $hashedPassword = $this->passwordHasher->hashPassword($client, 'Client2026!');
            $client->setEmail('client_' . $i . '@google.com')
                ->setNickname('Client ' . $i)
                ->setRoles(['ROLE_USER'])
                ->setTotpSecret($secret)
                ->enableTotp()
                ->setPassword($hashedPassword);
            $manager->persist($client);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['UserFixtures'];
    }
}
