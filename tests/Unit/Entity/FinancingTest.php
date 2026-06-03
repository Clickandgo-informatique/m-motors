<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Dossier;
use App\Entity\Financing;
use PHPUnit\Framework\TestCase;

class FinancingTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $financing = new Financing();

        self::assertNull($financing->getId());
        self::assertNull($financing->getDossier());

        self::assertSame(
            'pending',
            $financing->getStatus()
        );

        self::assertSame(
            'cash',
            $financing->getType()
        );

        self::assertNull($financing->getLeasingType());
        self::assertNull($financing->getAmount());
        self::assertNull($financing->getDurationMonths());
        self::assertNull($financing->getMonthlyPayment());
        self::assertNull($financing->getDecidedAt());

        self::assertTrue($financing->isCash());
        self::assertFalse($financing->isCredit());
        self::assertFalse($financing->isLeasing());
    }

    // vérifie l'association au dossier
    public function testDossier(): void
    {
        $financing = new Financing();
        $dossier = new Dossier();

        $financing->setDossier($dossier);

        self::assertSame(
            $dossier,
            $financing->getDossier()
        );
    }

    // vérifie qu'un dossier peut être null
    public function testDossierCanBeNull(): void
    {
        $financing = new Financing();

        $financing->setDossier(null);

        self::assertNull($financing->getDossier());
    }

    // vérifie le statut
    public function testStatus(): void
    {
        $financing = new Financing();

        $financing->setStatus('approved');

        self::assertSame(
            'approved',
            $financing->getStatus()
        );
    }

    // vérifie le type cash
    public function testCashType(): void
    {
        $financing = new Financing();

        $financing->setType('cash');

        self::assertTrue($financing->isCash());
        self::assertFalse($financing->isCredit());
        self::assertFalse($financing->isLeasing());
    }

    // vérifie le type credit
    public function testCreditType(): void
    {
        $financing = new Financing();

        $financing->setType('credit');

        self::assertFalse($financing->isCash());
        self::assertTrue($financing->isCredit());
        self::assertFalse($financing->isLeasing());
    }

    // vérifie le type leasing
    public function testLeasingType(): void
    {
        $financing = new Financing();

        $financing->setType('leasing');

        self::assertFalse($financing->isCash());
        self::assertFalse($financing->isCredit());
        self::assertTrue($financing->isLeasing());
    }

    // vérifie le sous-type leasing
    public function testLeasingSubType(): void
    {
        $financing = new Financing();

        $financing->setType('leasing');
        $financing->setLeasingType('loa');

        self::assertSame(
            'loa',
            $financing->getLeasingType()
        );
    }

    // vérifie le nettoyage automatique du sous-type
    public function testChangingTypeRemovesLeasingType(): void
    {
        $financing = new Financing();

        $financing->setType('leasing');
        $financing->setLeasingType('lld');

        self::assertSame(
            'lld',
            $financing->getLeasingType()
        );

        $financing->setType('credit');

        self::assertNull(
            $financing->getLeasingType()
        );
    }

    // vérifie le montant
    public function testAmount(): void
    {
        $financing = new Financing();

        $financing->setAmount(25000);

        self::assertSame(
            25000,
            $financing->getAmount()
        );
    }

    // vérifie la durée
    public function testDurationMonths(): void
    {
        $financing = new Financing();

        $financing->setDurationMonths(48);

        self::assertSame(
            48,
            $financing->getDurationMonths()
        );
    }

    // vérifie la mensualité
    public function testMonthlyPayment(): void
    {
        $financing = new Financing();

        $financing->setMonthlyPayment(399.99);

        self::assertSame(
            399.99,
            $financing->getMonthlyPayment()
        );
    }

    // vérifie la date de décision
    public function testDecidedAt(): void
    {
        $financing = new Financing();

        $date = new \DateTimeImmutable();

        $financing->setDecidedAt($date);

        self::assertSame(
            $date,
            $financing->getDecidedAt()
        );
    }

    // vérifie validateConsistency hors leasing
    public function testValidateConsistencyForCash(): void
    {
        $financing = new Financing();

        $financing->setType('cash');
        $financing->setLeasingType('loa');

        $financing->validateConsistency();

        self::assertNull(
            $financing->getLeasingType()
        );
    }

    // vérifie validateConsistency avec leasing valide
    public function testValidateConsistencyForValidLeasing(): void
    {
        $financing = new Financing();

        $financing->setType('leasing');
        $financing->setLeasingType('loa');

        $financing->validateConsistency();

        self::assertSame(
            'loa',
            $financing->getLeasingType()
        );
    }

    // vérifie l'exception si leasing sans sous-type
    public function testValidateConsistencyThrowsException(): void
    {
        $financing = new Financing();

        $financing->setType('leasing');

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'leasingType obligatoire si type = leasing'
        );

        $financing->validateConsistency();
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $financing = new Financing();

        self::assertSame(
            $financing,
            $financing->setStatus('approved')
        );

        self::assertSame(
            $financing,
            $financing->setType('credit')
        );

        self::assertSame(
            $financing,
            $financing->setLeasingType(null)
        );

        self::assertSame(
            $financing,
            $financing->setAmount(10000)
        );

        self::assertSame(
            $financing,
            $financing->setDurationMonths(24)
        );

        self::assertSame(
            $financing,
            $financing->setMonthlyPayment(450.50)
        );

        self::assertSame(
            $financing,
            $financing->setDecidedAt(
                new \DateTimeImmutable()
            )
        );
    }
}
