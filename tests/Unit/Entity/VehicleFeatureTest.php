<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Feature;
use App\Entity\Vehicle;
use App\Entity\VehicleBadge;
use PHPUnit\Framework\TestCase;

class VehicleFeatureTest extends TestCase
{
    // vérifie l'ajout d'un équipement
    public function testAddFeature(): void
    {
        $vehicle = new Vehicle();
        $feature = new Feature();

        $vehicle->addFeature($feature);

        self::assertCount(
            1,
            $vehicle->getFeatures()
        );

        self::assertTrue(
            $vehicle->getFeatures()->contains($feature)
        );
    }

    // vérifie qu'un équipement ne peut être ajouté qu'une fois
    public function testAddFeatureOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $feature = new Feature();

        $vehicle->addFeature($feature);
        $vehicle->addFeature($feature);

        self::assertCount(
            1,
            $vehicle->getFeatures()
        );
    }

    // vérifie la suppression d'un équipement
    public function testRemoveFeature(): void
    {
        $vehicle = new Vehicle();
        $feature = new Feature();

        $vehicle->addFeature($feature);

        self::assertCount(
            1,
            $vehicle->getFeatures()
        );

        $vehicle->removeFeature($feature);

        self::assertCount(
            0,
            $vehicle->getFeatures()
        );
    }

    // vérifie la suppression d'un équipement absent
    public function testRemoveUnknownFeature(): void
    {
        $vehicle = new Vehicle();
        $feature = new Feature();

        $vehicle->removeFeature($feature);

        self::assertCount(
            0,
            $vehicle->getFeatures()
        );
    }

    // vérifie l'ajout d'un badge
    public function testAddBadge(): void
    {
        $vehicle = new Vehicle();
        $badge = new VehicleBadge();

        $vehicle->addBadge($badge);

        self::assertCount(
            1,
            $vehicle->getBadges()
        );

        self::assertTrue(
            $vehicle->getBadges()->contains($badge)
        );
    }

    // vérifie qu'un badge ne peut être ajouté qu'une fois
    public function testAddBadgeOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $badge = new VehicleBadge();

        $vehicle->addBadge($badge);
        $vehicle->addBadge($badge);

        self::assertCount(
            1,
            $vehicle->getBadges()
        );
    }

    // vérifie la suppression d'un badge
    public function testRemoveBadge(): void
    {
        $vehicle = new Vehicle();
        $badge = new VehicleBadge();

        $vehicle->addBadge($badge);

        self::assertCount(
            1,
            $vehicle->getBadges()
        );

        $vehicle->removeBadge($badge);

        self::assertCount(
            0,
            $vehicle->getBadges()
        );
    }

    // vérifie la suppression d'un badge absent
    public function testRemoveUnknownBadge(): void
    {
        $vehicle = new Vehicle();
        $badge = new VehicleBadge();

        $vehicle->removeBadge($badge);

        self::assertCount(
            0,
            $vehicle->getBadges()
        );
    }

    // vérifie le chaînage addFeature
    public function testAddFeatureReturnsSelf(): void
    {
        $vehicle = new Vehicle();

        self::assertSame(
            $vehicle,
            $vehicle->addFeature(new Feature())
        );
    }

    // vérifie le chaînage removeFeature
    public function testRemoveFeatureReturnsSelf(): void
    {
        $vehicle = new Vehicle();
        $feature = new Feature();

        self::assertSame(
            $vehicle,
            $vehicle->removeFeature($feature)
        );
    }

    // vérifie le chaînage addBadge
    public function testAddBadgeReturnsSelf(): void
    {
        $vehicle = new Vehicle();

        self::assertSame(
            $vehicle,
            $vehicle->addBadge(new VehicleBadge())
        );
    }

    // vérifie le chaînage removeBadge
    public function testRemoveBadgeReturnsSelf(): void
    {
        $vehicle = new Vehicle();
        $badge = new VehicleBadge();

        self::assertSame(
            $vehicle,
            $vehicle->removeBadge($badge)
        );
    }
}
