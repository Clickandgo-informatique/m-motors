<?php

namespace App\Tests\Workflow;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Enum\DossierType;
use App\Service\Dossier\DossierDocumentValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class DossierWorkflowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;

    protected function setUp(): void
    {
        self::bootKernel();

        // Mock du validator pour ne pas bloquer submit_documents
        $validator = $this->createMock(DossierDocumentValidator::class);
        $validator->method('isComplete')->willReturn(true);

        // On remplace le service réel par le mock
        self::getContainer()->set(DossierDocumentValidator::class, $validator);

        // Récupération du workflow via le registry
        $registry = self::getContainer()->get('workflow.registry');
        $this->workflow = $registry->get(new Dossier(), 'dossier');
    }

    /**
     * Crée un dossier valide pour passer les guards
     */
    private function createValidDossier(): Dossier
    {
        $dossier = new Dossier();
        $dossier->setStatus('draft');

        // Champs requis par les guards
        $dossier->setCustomer(new Customer());

        // On prend la première valeur de l'enum automatiquement (PURCHASE ou RENTAL)
        $enumValues = DossierType::cases();
        $dossier->setType($enumValues[0]);

        return $dossier;
    }

    public function testFullWorkflow(): void
    {
        $dossier = $this->createValidDossier();

        // draft → vehicle_selected
        $this->assertTrue($this->workflow->can($dossier, 'select_vehicle'));
        $this->workflow->apply($dossier, 'select_vehicle');
        $this->assertEquals('vehicle_selected', $dossier->getStatus());

        // vehicle_selected → documents_pending
        $this->assertTrue($this->workflow->can($dossier, 'request_documents'));
        $this->workflow->apply($dossier, 'request_documents');
        $this->assertEquals('documents_pending', $dossier->getStatus());

        // documents_pending → documents_review
        $this->assertTrue($this->workflow->can($dossier, 'submit_documents'));
        $this->workflow->apply($dossier, 'submit_documents');
        $this->assertEquals('documents_review', $dossier->getStatus());

        // documents_review → financing_review
        $this->assertTrue($this->workflow->can($dossier, 'validate_documents'));
        $this->workflow->apply($dossier, 'validate_documents');
        $this->assertEquals('financing_review', $dossier->getStatus());

        // financing_review → order_signed
        $this->assertTrue($this->workflow->can($dossier, 'approve_financing'));
        $this->workflow->apply($dossier, 'approve_financing');
        $this->assertEquals('order_signed', $dossier->getStatus());

        // order_signed → completed
        $this->assertTrue($this->workflow->can($dossier, 'sign_order'));
        $this->workflow->apply($dossier, 'sign_order');
        $this->assertEquals('completed', $dossier->getStatus());
    }

    public function testRejectDocumentFlow(): void
    {
        $dossier = $this->createValidDossier();

        $this->workflow->apply($dossier, 'select_vehicle');
        $this->workflow->apply($dossier, 'request_documents');
        $this->workflow->apply($dossier, 'submit_documents');

        $this->assertEquals('documents_review', $dossier->getStatus());

        $this->assertTrue($this->workflow->can($dossier, 'reject_documents'));
        $this->workflow->apply($dossier, 'reject_documents');
        $this->assertEquals('documents_pending', $dossier->getStatus());
    }

    public function testCancelFromAnyState(): void
    {
        $states = [
            'draft',
            'vehicle_selected',
            'documents_pending',
            'documents_review',
            'financing_review',
            'order_signed',
        ];

        foreach ($states as $state) {
            $dossier = $this->createValidDossier();
            $dossier->setStatus($state);

            $this->assertTrue(
                $this->workflow->can($dossier, 'cancel'),
                "Cancel should be possible from $state"
            );

            $this->workflow->apply($dossier, 'cancel');
            $this->assertEquals('cancelled', $dossier->getStatus());
        }
    }

    public function testInvalidTransition(): void
    {
        $dossier = $this->createValidDossier();

        // Impossible : submit_documents depuis draft
        $this->assertFalse($this->workflow->can($dossier, 'submit_documents'));
    }
}
