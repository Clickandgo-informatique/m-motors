<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    //Vérifie que l'id soit null au démarrage
    public function testIdIsNullAtCreation(): void
    {
        $customer = new Customer();

        $this->assertNull($customer->getId());
    }
    // vérifie les valeurs par défaut du constructeur
    public function testConstructorInitializesCollection(): void
    {
        $customer = new Customer();

        self::assertCount(0, $customer->getDossiers());
    }

    // vérifie le code client
    public function testCustomerCodeNormalization(): void
    {
        $customer = new Customer();

        $customer->setCustomerCode(' dup-001 ');

        self::assertSame(
            'DUP-001',
            $customer->getCustomerCode()
        );
    }

    // vérifie le prénom
    public function testFirstNameNormalization(): void
    {
        $customer = new Customer();

        $customer->setFirstName('  John  ');

        self::assertSame(
            'John',
            $customer->getFirstName()
        );
    }

    // vérifie le nom
    public function testLastNameNormalization(): void
    {
        $customer = new Customer();

        $customer->setLastName('  dupont  ');

        self::assertSame(
            'DUPONT',
            $customer->getLastName()
        );
    }

    // vérifie l'email
    public function testEmailNormalization(): void
    {
        $customer = new Customer();

        $customer->setEmail('  TEST@MAIL.COM  ');

        self::assertSame(
            'test@mail.com',
            $customer->getEmail()
        );
    }

    // vérifie le nettoyage du téléphone principal
    public function testPhoneNumber1Normalization(): void
    {
        $customer = new Customer();

        $customer->setPhoneNumber1('06 12 34 56 78');

        self::assertSame(
            '0612345678',
            $customer->getPhoneNumber1()
        );
    }

    // vérifie le nettoyage du téléphone secondaire
    public function testPhoneNumber2Normalization(): void
    {
        $customer = new Customer();

        $customer->setPhoneNumber2('06 98 76 54 32');

        self::assertSame(
            '0698765432',
            $customer->getPhoneNumber2()
        );
    }

    // vérifie qu'un téléphone null reste null
    public function testPhoneNumberCanBeNull(): void
    {
        $customer = new Customer();

        $customer->setPhoneNumber1(null);
        $customer->setPhoneNumber2(null);

        self::assertNull($customer->getPhoneNumber1());
        self::assertNull($customer->getPhoneNumber2());
    }

    // vérifie l'association utilisateur
    public function testSetUser(): void
    {
        $customer = new Customer();
        $user = new User();

        $customer->setUser($user);

        self::assertSame(
            $user,
            $customer->getUser()
        );

        self::assertSame(
            $customer,
            $user->getCustomer()
        );
    }

    // vérifie l'ajout d'un dossier
    public function testAddDossier(): void
    {
        $customer = new Customer();
        $dossier = new Dossier();

        $customer->addDossier($dossier);

        self::assertCount(1, $customer->getDossiers());

        self::assertTrue(
            $customer->getDossiers()->contains($dossier)
        );

        self::assertSame(
            $customer,
            $dossier->getCustomer()
        );
    }

    // vérifie qu'un dossier n'est ajouté qu'une seule fois
    public function testAddDossierOnlyOnce(): void
    {
        $customer = new Customer();
        $dossier = new Dossier();

        $customer->addDossier($dossier);
        $customer->addDossier($dossier);

        self::assertCount(1, $customer->getDossiers());
    }

    // vérifie la suppression d'un dossier
    public function testRemoveDossier(): void
    {
        $customer = new Customer();
        $dossier = new Dossier();

        $customer->addDossier($dossier);

        self::assertCount(1, $customer->getDossiers());

        $customer->removeDossier($dossier);

        self::assertCount(0, $customer->getDossiers());
    }

    // vérifie l'adresse
    public function testAddress(): void
    {
        $customer = new Customer();

        $customer->setAddress('10 rue de paris');

        self::assertSame(
            '10 rue de paris',
            $customer->getAddress()
        );
    }

    // vérifie le code postal
    public function testZipCode(): void
    {
        $customer = new Customer();

        $customer->setZipCode('75001');

        self::assertSame(
            '75001',
            $customer->getZipCode()
        );
    }

    // vérifie la ville
    public function testCity(): void
    {
        $customer = new Customer();

        $customer->setCity('Paris');

        self::assertSame(
            'Paris',
            $customer->getCity()
        );
    }

    // vérifie le complément d'adresse
    public function testAddressDetails(): void
    {
        $customer = new Customer();

        $customer->setAddressDetails('bâtiment a');

        self::assertSame(
            'bâtiment a',
            $customer->getAddressDetails()
        );
    }

    // vérifie le pays
    public function testCountryNormalization(): void
    {
        $customer = new Customer();

        $customer->setCountry('  France  ');

        self::assertSame(
            'France',
            $customer->getCountry()
        );
    }

    // vérifie qu'un pays null reste null
    public function testCountryCanBeNull(): void
    {
        $customer = new Customer();

        $customer->setCountry(null);

        self::assertNull($customer->getCountry());
    }

    // vérifie la représentation texte
    public function testToString(): void
    {
        $customer = new Customer();

        $customer
            ->setFirstName('John')
            ->setLastName('Dupont')
            ->setCustomerCode('DUP001');

        self::assertSame(
            'John DUPONT (DUP001)',
            (string) $customer
        );
    }
}
