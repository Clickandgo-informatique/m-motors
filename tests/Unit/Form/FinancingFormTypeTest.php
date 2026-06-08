<?php

namespace App\Tests\Unit\Form;

use App\Entity\Financing;
use App\Form\FinancingFormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class FinancingFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new FinancingFormType(),
                ],
                []
            ),
        ];
    }

    public function testFormUsesFinancingDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité Financing

        $form = $this->factory->create(FinancingFormType::class);

        $this->assertSame(
            Financing::class,
            $form->getConfig()->getOption('data_class')
        );
    }

    public function testContainsAllExpectedFields(): void
    {
        // Vérifie la présence de tous les champs

        $form = $this->factory->create(FinancingFormType::class);

        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('leasingType'));
        $this->assertTrue($form->has('status'));
        $this->assertTrue($form->has('amount'));
        $this->assertTrue($form->has('durationMonths'));
        $this->assertTrue($form->has('monthlyPayment'));
    }

    public function testTypeFieldConfiguration(): void
    {
        // Vérifie la configuration du type de financement

        $form = $this->factory->create(FinancingFormType::class);

        $config = $form->get('type')->getConfig();

        $this->assertTrue(
            $config->getOption('required')
        );

        $this->assertSame(
            'cash',
            $config->getOption('empty_data')
        );

        $this->assertNull(
            $config->getOption('placeholder')
        );

        $this->assertSame(
            [
                'Comptant' => 'cash',
                'Crédit' => 'credit',
                'Leasing' => 'leasing',
            ],
            $config->getOption('choices')
        );
    }

    public function testLeasingTypeFieldConfiguration(): void
    {
        // Vérifie la configuration du sous-type leasing

        $form = $this->factory->create(FinancingFormType::class);

        $config = $form->get('leasingType')->getConfig();

        $this->assertFalse(
            $config->getOption('required')
        );

        $this->assertSame(
            'Choisir un type de leasing',
            $config->getOption('placeholder')
        );

        $this->assertSame(
            [
                'LOA' => 'loa',
                'LLD' => 'lld',
            ],
            $config->getOption('choices')
        );
    }

    public function testStatusFieldConfiguration(): void
    {
        // Vérifie la configuration du statut

        $form = $this->factory->create(FinancingFormType::class);

        $config = $form->get('status')->getConfig();

        $this->assertTrue(
            $config->getOption('required')
        );

        $this->assertSame(
            'pending',
            $config->getOption('empty_data')
        );

        $this->assertNull(
            $config->getOption('placeholder')
        );

        $this->assertSame(
            [
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Refusé' => 'rejected',
            ],
            $config->getOption('choices')
        );
    }

    public function testAmountFieldConfiguration(): void
    {
        // Vérifie la configuration du montant financé

        $form = $this->factory->create(FinancingFormType::class);

        $config = $form->get('amount')->getConfig();

        $this->assertFalse(
            $config->getOption('required')
        );

        $this->assertSame(
            'EUR',
            $config->getOption('currency')
        );
    }

    public function testDurationMonthsFieldConfiguration(): void
    {
        // Vérifie la configuration de la durée

        $form = $this->factory->create(FinancingFormType::class);

        $this->assertFalse(
            $form->get('durationMonths')
                ->getConfig()
                ->getOption('required')
        );
    }

    public function testMonthlyPaymentFieldConfiguration(): void
    {
        // Vérifie la configuration de la mensualité

        $form = $this->factory->create(FinancingFormType::class);

        $config = $form->get('monthlyPayment')->getConfig();

        $this->assertFalse(
            $config->getOption('required')
        );

        $this->assertSame(
            'EUR',
            $config->getOption('currency')
        );
    }
}
