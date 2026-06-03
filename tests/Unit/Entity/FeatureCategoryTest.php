<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Feature;
use App\Entity\FeatureCategory;
use PHPUnit\Framework\TestCase;

class FeatureCategoryTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $category = new FeatureCategory();

        self::assertNull($category->getId());
        self::assertNull($category->getCode());
        self::assertNull($category->getLabel());
        self::assertNull($category->getPosition());
        self::assertCount(0, $category->getFeatures());
    }

    // vérifie la normalisation du code
    public function testCodeNormalization(): void
    {
        $category = new FeatureCategory();

        $category->setCode('  CONFORT  ');

        self::assertSame(
            'confort',
            $category->getCode()
        );
    }

    // vérifie le label
    public function testLabelNormalization(): void
    {
        $category = new FeatureCategory();

        $category->setLabel('equipements');

        self::assertSame(
            'Equipements',
            $category->getLabel()
        );
    }

    // vérifie la position
    public function testPosition(): void
    {
        $category = new FeatureCategory();

        $category->setPosition(10);

        self::assertSame(
            10,
            $category->getPosition()
        );
    }

    // vérifie qu'une position peut être null
    public function testPositionCanBeNull(): void
    {
        $category = new FeatureCategory();

        $category->setPosition(null);

        self::assertNull($category->getPosition());
    }

    // vérifie l'initialisation de la collection
    public function testFeaturesCollectionInitialization(): void
    {
        $category = new FeatureCategory();

        self::assertCount(
            0,
            $category->getFeatures()
        );
    }

    // vérifie l'ajout d'une feature
    public function testAddFeature(): void
    {
        $category = new FeatureCategory();
        $feature = new Feature();

        $category->addFeature($feature);

        self::assertCount(
            1,
            $category->getFeatures()
        );

        self::assertTrue(
            $category->getFeatures()->contains($feature)
        );

        self::assertSame(
            $category,
            $feature->getCategory()
        );
    }

    // vérifie qu'une feature n'est ajoutée qu'une fois
    public function testAddFeatureOnlyOnce(): void
    {
        $category = new FeatureCategory();
        $feature = new Feature();

        $category->addFeature($feature);
        $category->addFeature($feature);

        self::assertCount(
            1,
            $category->getFeatures()
        );
    }

    // vérifie la suppression d'une feature
    public function testRemoveFeature(): void
    {
        $category = new FeatureCategory();
        $feature = new Feature();

        $category->addFeature($feature);

        self::assertCount(
            1,
            $category->getFeatures()
        );

        $category->removeFeature($feature);

        self::assertCount(
            0,
            $category->getFeatures()
        );

        self::assertNull(
            $feature->getCategory()
        );
    }

    // vérifie la suppression d'une feature absente
    public function testRemoveNonExistingFeature(): void
    {
        $category = new FeatureCategory();
        $feature = new Feature();

        $category->removeFeature($feature);

        self::assertCount(
            0,
            $category->getFeatures()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $category = new FeatureCategory();

        self::assertSame(
            $category,
            $category->setCode('confort')
        );

        self::assertSame(
            $category,
            $category->setLabel('Confort')
        );

        self::assertSame(
            $category,
            $category->setPosition(1)
        );
    }
}
