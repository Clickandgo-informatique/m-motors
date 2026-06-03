<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Feature;
use App\Entity\FeatureCategory;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $feature = new Feature();

        self::assertNull($feature->getId());
        self::assertNull($feature->getCode());
        self::assertNull($feature->getLabel());
        self::assertNull($feature->getCategory());
        self::assertCount(0, $feature->getVehicles());
    }

    // vérifie la normalisation du code
    public function testCodeNormalization(): void
    {
        $feature = new Feature();

        $feature->setCode('  GPS_PREMIUM  ');

        self::assertSame(
            'gps_premium',
            $feature->getCode()
        );
    }

    // vérifie le label
    public function testLabel(): void
    {
        $feature = new Feature();

        $feature->setLabel('climatisation');

        self::assertSame(
            'Climatisation',
            $feature->getLabel()
        );
    }

    // vérifie la catégorie
    public function testCategory(): void
    {
        $feature = new Feature();
        $category = new FeatureCategory();

        $feature->setCategory($category);

        self::assertSame(
            $category,
            $feature->getCategory()
        );
    }

    // vérifie qu'une catégorie peut être null
    public function testCategoryCanBeNull(): void
    {
        $feature = new Feature();

        $feature->setCategory(null);

        self::assertNull($feature->getCategory());
    }

    // vérifie l'initialisation de la collection véhicules
    public function testVehiclesCollectionInitialization(): void
    {
        $feature = new Feature();

        self::assertCount(
            0,
            $feature->getVehicles()
        );
    }

    // vérifie la génération automatique du code
    public function testGenerateCodeFromLabel(): void
    {
        $feature = new Feature();

        $feature->setLabel('Caméra de recul');

        $feature->generateCode();

        self::assertSame(
            'cam_ra_de_recul',
            $feature->getCode()
        );
    }

    // vérifie qu'un code existant n'est pas écrasé
    public function testGenerateCodeDoesNotOverrideExistingCode(): void
    {
        $feature = new Feature();

        $feature->setLabel('Caméra de recul');
        $feature->setCode('camera_recul');

        $feature->generateCode();

        self::assertSame(
            'camera_recul',
            $feature->getCode()
        );
    }

    // vérifie qu'aucun code n'est généré sans label
    public function testGenerateCodeWithoutLabel(): void
    {
        $feature = new Feature();

        $feature->generateCode();

        self::assertNull($feature->getCode());
    }

    // vérifie la génération sur plusieurs mots
    public function testGenerateCodeWithMultipleSpaces(): void
    {
        $feature = new Feature();

        $feature->setLabel('Sieges chauffants avant');

        $feature->generateCode();

        self::assertSame(
            'sieges_chauffants_avant',
            $feature->getCode()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $feature = new Feature();

        self::assertSame(
            $feature,
            $feature->setCode('gps')
        );

        self::assertSame(
            $feature,
            $feature->setLabel('Gps')
        );

        self::assertSame(
            $feature,
            $feature->setCategory(new FeatureCategory())
        );
    }
}
