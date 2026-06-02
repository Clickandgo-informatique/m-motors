<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Customer;
use App\Entity\Favorite;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    // vérifie les valeurs par défaut du constructeur
    public function testConstructorInitializesDefaults(): void
    {
        $user = new User();

        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertFalse($user->isVerified());
        self::assertFalse($user->is2FAEnabled());
        self::assertFalse($user->isTwoFactorVerified());
        self::assertCount(0, $user->getFavorites());
    }

    // vérifie la normalisation de l'email
    public function testSetEmailNormalizesValue(): void
    {
        $user = new User();

        $user->setEmail('  TEST@MAIL.COM  ');

        self::assertSame('test@mail.com', $user->getEmail());
    }

    // vérifie l'identifiant utilisateur
    public function testGetUserIdentifier(): void
    {
        $user = new User();

        $user->setEmail('user@test.com');

        self::assertSame('user@test.com', $user->getUserIdentifier());
    }

    // vérifie les rôles
    public function testRoles(): void
    {
        $user = new User();

        $user->setRoles([
            'ROLE_ADMIN',
            'ROLE_ADMIN',
            'ROLE_USER',
        ]);

        self::assertSame(
            ['ROLE_ADMIN', 'ROLE_USER'],
            array_values($user->getRoles())
        );
    }

    // vérifie le mot de passe
    public function testPassword(): void
    {
        $user = new User();

        $user->setPassword('hashed-password');

        self::assertSame(
            'hashed-password',
            $user->getPassword()
        );
    }

    // vérifie l'état de validation du compte
    public function testVerificationStatus(): void
    {
        $user = new User();

        $user->setIsVerified(true);

        self::assertTrue($user->isVerified());
    }

    // vérifie le nickname
    public function testNickname(): void
    {
        $user = new User();

        $user->setNickname('john');

        self::assertSame(
            'john',
            $user->getNickname()
        );
    }

    // vérifie le totp secret
    public function testTotpSecret(): void
    {
        $user = new User();

        $user->setTotpSecret('secret');

        self::assertSame(
            'secret',
            $user->getTotpSecret()
        );
    }

    // vérifie le secret google 2fa
    public function testGoogle2FASecret(): void
    {
        $user = new User();

        $user->setGoogle2FASecret('google-secret');

        self::assertSame(
            'google-secret',
            $user->getGoogle2FASecret()
        );
    }

    // vérifie l'activation de la 2fa
    public function testIs2FAEnabled(): void
    {
        $user = new User();

        $user->setIs2FAEnabled(true);

        self::assertTrue($user->is2FAEnabled());
    }

    // vérifie l'état de validation de la 2fa
    public function testTwoFactorVerified(): void
    {
        $user = new User();

        $user->setIsTwoFactorVerified(true);

        self::assertTrue($user->isTwoFactorVerified());
    }

    // vérifie l'association client
    public function testCustomer(): void
    {
        $user = new User();
        $customer = new Customer();

        $user->setCustomer($customer);

        self::assertSame(
            $customer,
            $user->getCustomer()
        );
    }

    // vérifie l'ajout d'un favori
    public function testAddFavorite(): void
    {
        $user = new User();
        $favorite = new Favorite();

        $user->addFavorite($favorite);

        self::assertCount(1, $user->getFavorites());
        self::assertTrue(
            $user->getFavorites()->contains($favorite)
        );
        self::assertSame(
            $user,
            $favorite->getUser()
        );
    }

    // vérifie qu'un favori n'est pas ajouté deux fois
    public function testAddFavoriteOnlyOnce(): void
    {
        $user = new User();
        $favorite = new Favorite();

        $user->addFavorite($favorite);
        $user->addFavorite($favorite);

        self::assertCount(1, $user->getFavorites());
    }

    // vérifie la suppression d'un favori
    public function testRemoveFavorite(): void
    {
        $user = new User();
        $favorite = new Favorite();

        $user->addFavorite($favorite);
        $user->removeFavorite($favorite);

        self::assertCount(0, $user->getFavorites());
        self::assertNull($favorite->getUser());
    }

    // vérifie que eraseCredentials ne provoque aucune erreur
    public function testEraseCredentials(): void
    {
        $user = new User();

        $user->eraseCredentials();

        self::assertTrue(true);
    }

    // vérifie l'identifiant manuel
    public function testId(): void
    {
        $user = new User();

        $user->setId(42);

        self::assertSame(
            42,
            $user->getId()
        );
    }
}
