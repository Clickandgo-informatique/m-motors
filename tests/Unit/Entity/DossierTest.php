<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Financing;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use PHPUnit\Framework\TestCase;

class DossierTest extends TestCase
{
    //verifie que l'id soit null au démarrage
    public function testIdIsNullAtCreation(): void
    {
        $dossier = new Dossier();
        $this->assertNull($dossier->getId());
    }
    // vérifie les valeurs par défaut du constructeur
    public function testConstructorInitializesDefaults(): void
    {
        $dossier = new Dossier();

        self::assertCount(0, $dossier->getDocuments());
        self::assertSame('draft', $dossier->getStatus());
        self::assertNull($dossier->getFinancing());
        self::assertFalse($dossier->hasFinancing());
    }

    // vérifie le client
    public function testCustomer(): void
    {
        $dossier = new Dossier();
        $customer = new Customer();

        $dossier->setCustomer($customer);

        self::assertSame(
            $customer,
            $dossier->getCustomer()
        );
    }

    // vérifie le véhicule
    public function testVehicle(): void
    {
        $dossier = new Dossier();
        $vehicle = new Vehicle();

        $dossier->setVehicle($vehicle);

        self::assertSame(
            $vehicle,
            $dossier->getVehicle()
        );
    }

    // vérifie que le véhicule peut être null
    public function testVehicleCanBeNull(): void
    {
        $dossier = new Dossier();

        $dossier->setVehicle(null);

        self::assertNull($dossier->getVehicle());
    }

    // vérifie le type de dossier
    public function testType(): void
    {
        $dossier = new Dossier();

        $dossier->setType(DossierType::PURCHASE);

        self::assertSame(
            DossierType::PURCHASE,
            $dossier->getType()
        );
    }

    // vérifie le statut
    public function testStatus(): void
    {
        $dossier = new Dossier();

        $dossier->setStatus('completed');

        self::assertSame(
            'completed',
            $dossier->getStatus()
        );
    }

    // vérifie le code dossier
    public function testDossierCode(): void
    {
        $dossier = new Dossier();

        $dossier->setDossierCode('DOS-001');

        self::assertSame(
            'DOS-001',
            $dossier->getDossierCode()
        );
    }

    // vérifie le créateur
    public function testCreatedBy(): void
    {
        $dossier = new Dossier();
        $user = new User();

        $dossier->setCreatedBy($user);

        self::assertSame(
            $user,
            $dossier->getCreatedBy()
        );
    }

    // vérifie que le créateur peut être null
    public function testCreatedByCanBeNull(): void
    {
        $dossier = new Dossier();

        $dossier->setCreatedBy(null);

        self::assertNull($dossier->getCreatedBy());
    }

    // vérifie la date de finalisation
    public function testCompletedAt(): void
    {
        $dossier = new Dossier();
        $date = new \DateTimeImmutable();

        $dossier->setCompletedAt($date);

        self::assertSame(
            $date,
            $dossier->getCompletedAt()
        );
    }

    // vérifie la date d'annulation
    public function testCancelledAt(): void
    {
        $dossier = new Dossier();
        $date = new \DateTimeImmutable();

        $dossier->setCancelledAt($date);

        self::assertSame(
            $date,
            $dossier->getCancelledAt()
        );
    }

    // vérifie la relation financement
    public function testFinancing(): void
    {
        $dossier = new Dossier();
        $financing = new Financing();

        $dossier->setFinancing($financing);

        self::assertSame(
            $financing,
            $dossier->getFinancing()
        );

        self::assertSame(
            $dossier,
            $financing->getDossier()
        );

        self::assertTrue($dossier->hasFinancing());
    }

    // vérifie la suppression du financement
    public function testFinancingCanBeNull(): void
    {
        $dossier = new Dossier();

        $dossier->setFinancing(null);

        self::assertNull($dossier->getFinancing());
        self::assertFalse($dossier->hasFinancing());
    }

    // vérifie le label du statut draft
    public function testDraftStatusLabel(): void
    {
        $dossier = new Dossier();

        self::assertSame(
            'brouillon',
            $dossier->getStatusLabel()
        );
    }

    // vérifie les labels métier
    public function testStatusLabels(): void
    {
        $statuses = [
            'draft' => 'brouillon',
            'vehicle_selected' => 'vehicule selectionne',
            'documents_pending' => 'documents a fournir',
            'documents_review' => 'documents en validation',
            'financing_review' => 'financement en cours',
            'completed' => 'termine',
            'cancelled' => 'annule',
        ];

        foreach ($statuses as $status => $label) {
            $dossier = new Dossier();

            $dossier->setStatus($status);

            self::assertSame(
                $label,
                $dossier->getStatusLabel()
            );
        }
    }

    // vérifie le fallback du label
    public function testUnknownStatusLabel(): void
    {
        $dossier = new Dossier();

        $dossier->setStatus('custom_status');

        self::assertSame(
            'custom_status',
            $dossier->getStatusLabel()
        );
    }

    // vérifie les badges métier
    public function testStatusBadges(): void
    {
        $statuses = [
            'draft' => 'secondary',
            'vehicle_selected' => 'info',
            'documents_pending' => 'warning',
            'documents_review' => 'warning',
            'financing_review' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
        ];

        foreach ($statuses as $status => $badge) {
            $dossier = new Dossier();

            $dossier->setStatus($status);

            self::assertSame(
                $badge,
                $dossier->getStatusBadge()
            );
        }
    }

    // vérifie le fallback du badge
    public function testUnknownStatusBadge(): void
    {
        $dossier = new Dossier();

        $dossier->setStatus('custom_status');

        self::assertSame(
            'secondary',
            $dossier->getStatusBadge()
        );
    }

    // vérifie l'absence de responsable assigné par défaut
    public function testAssignedToIsNullByDefault(): void
    {
        $dossier = new Dossier();

        self::assertNull($dossier->getAssignedTo());
    }

    // vérifie l'absence de validateur par défaut
    public function testValidatedByIsNullByDefault(): void
    {
        $dossier = new Dossier();

        self::assertNull($dossier->getValidatedBy());
    }
}
