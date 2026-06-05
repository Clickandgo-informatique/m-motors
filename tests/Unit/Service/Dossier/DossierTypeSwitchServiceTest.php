<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\Financing;
use App\Enum\DossierType;
use App\Service\Dossier\DossierTypeSwitchService;
use PHPUnit\Framework\TestCase;

class DossierTypeSwitchServiceTest extends TestCase
{
    public function testDoesNothingWhenTypeIsIdentical(): void
    {
        // vérifie qu'aucune modification n'est faite si le type est identique

        $dossier = new Dossier();
        $dossier->setType(DossierType::PURCHASE);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::PURCHASE);

        $this->assertSame(
            DossierType::PURCHASE,
            $dossier->getType()
        );
    }

    public function testSwitchToRentalWithoutFinancing(): void
    {
        // vérifie la bascule vers location sans financement existant

        $dossier = new Dossier();
        $dossier->setType(DossierType::PURCHASE);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::RENTAL);

        $this->assertSame(
            DossierType::RENTAL,
            $dossier->getType()
        );

        $this->assertNull(
            $dossier->getFinancing()
        );
    }

    public function testSwitchToRentalWithFinancingCreatesLeasing(): void
    {
        // vérifie la transformation en leasing avec valeur par défaut

        $dossier = new Dossier();
        $dossier->setType(DossierType::PURCHASE);

        $financing = new Financing();
        $financing->setType('credit');
        $financing->setLeasingType(null);

        $dossier->setFinancing($financing);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::RENTAL);

        $this->assertSame(
            DossierType::RENTAL,
            $dossier->getType()
        );

        $this->assertSame(
            'leasing',
            $financing->getType()
        );

        $this->assertSame(
            'loa',
            $financing->getLeasingType()
        );
    }

    public function testSwitchToRentalDoesNotOverrideExistingLeasingType(): void
    {
        // vérifie que le leasingType existant n'est pas écrasé

        $dossier = new Dossier();
        $dossier->setType(DossierType::PURCHASE);

        $financing = new Financing();
        $financing->setType('credit');
        $financing->setLeasingType('lld');

        $dossier->setFinancing($financing);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::RENTAL);

        $this->assertSame(
            'lld',
            $financing->getLeasingType()
        );
    }

    public function testSwitchToPurchaseConvertsLeasingToCredit(): void
    {
        // vérifie la conversion leasing → credit

        $dossier = new Dossier();
        $dossier->setType(DossierType::RENTAL);

        $financing = new Financing();
        $financing->setType('leasing');

        $dossier->setFinancing($financing);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::PURCHASE);

        $this->assertSame(
            DossierType::PURCHASE,
            $dossier->getType()
        );

        $this->assertSame(
            'credit',
            $financing->getType()
        );
    }

    public function testSwitchToPurchaseDoesNotChangeNonLeasingFinancing(): void
    {
        // vérifie qu'un financement non leasing n'est pas modifié

        $dossier = new Dossier();
        $dossier->setType(DossierType::RENTAL);

        $financing = new Financing();
        $financing->setType('credit');

        $dossier->setFinancing($financing);

        $service = new DossierTypeSwitchService();

        $service->switchType($dossier, DossierType::PURCHASE);

        $this->assertSame(
            'credit',
            $financing->getType()
        );
    }
}
