<?php

namespace App\Tests\Unit\Form;

use App\Entity\Customer;
use App\Form\CustomerFormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class CustomerFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new CustomerFormType(),
                ],
                []
            ),
        ];
    }

    public function testFormUsesCustomerDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité Customer

        $form = $this->factory->create(CustomerFormType::class);

        $this->assertSame(
            Customer::class,
            $form->getConfig()->getOption('data_class')
        );
    }

    public function testFormContainsAllExpectedFields(): void
    {
        // Vérifie la présence de tous les champs configurés

        $form = $this->factory->create(CustomerFormType::class);

        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('customerCode'));
        $this->assertTrue($form->has('zipcode'));
        $this->assertTrue($form->has('city'));
        $this->assertTrue($form->has('address'));
        $this->assertTrue($form->has('addressDetails'));
        $this->assertTrue($form->has('createdAt'));
        $this->assertTrue($form->has('updatedAt'));
        $this->assertTrue($form->has('phoneNumber1'));
        $this->assertTrue($form->has('phoneNumber2'));
    }

    public function testCustomerCodeFieldConfiguration(): void
    {
        // Vérifie la configuration du champ customerCode

        $form = $this->factory->create(CustomerFormType::class);

        $field = $form->get('customerCode');

        $this->assertFalse(
            $field->getConfig()->getOption('required')
        );

        $this->assertTrue(
            $field->getConfig()->getOption('disabled')
        );

        $this->assertTrue(
            $field->getConfig()->getOption('attr')['readonly']
        );
    }

    public function testCreatedAtFieldIsDisabled(): void
    {
        // Vérifie que createdAt est en lecture seule

        $form = $this->factory->create(CustomerFormType::class);

        $this->assertTrue(
            $form->get('createdAt')
                ->getConfig()
                ->getOption('disabled')
        );
    }

    public function testUpdatedAtFieldIsDisabled(): void
    {
        // Vérifie que updatedAt est en lecture seule

        $form = $this->factory->create(CustomerFormType::class);

        $this->assertTrue(
            $form->get('updatedAt')
                ->getConfig()
                ->getOption('disabled')
        );
    }

    public function testPhoneNumberFieldsAreOptional(): void
    {
        // Vérifie que les numéros de téléphone sont facultatifs

        $form = $this->factory->create(CustomerFormType::class);

        $this->assertFalse(
            $form->get('phoneNumber1')
                ->getConfig()
                ->getOption('required')
        );

        $this->assertFalse(
            $form->get('phoneNumber2')
                ->getConfig()
                ->getOption('required')
        );
    }
}
