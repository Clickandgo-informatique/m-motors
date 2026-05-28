<?php

namespace App\Tests\Entity;

use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * tests unitaires sur la gestion des favoris utilisateur
 *
 * objectifs :
 * - vérifier le bon fonctionnement de la collection oneToMany (user -> favorite)
 * - vérifier la cohérence de la relation bidirectionnelle (favorite.user)
 * - vérifier le comportement lors des ajouts et suppressions
 */
class UserFavoritesTest extends KernelTestCase
{
    /**
     * vérifie qu’un favori est correctement ajouté à l’utilisateur
     * et que la relation côté favorite est bien définie
     */

    // vérifie que l'id d'un favori soit null par défaut
    public function testIdIsNullByDefault(): void
    {
        $favorite = new Favorite();

        $this->assertNull($favorite->getId());
    }

    // vérifie que createdAt est initialisé à la création
    public function testCreatedAtIsInitialized(): void
    {
        $favorite = new Favorite();

        $this->assertInstanceOf(\DateTimeImmutable::class, $favorite->getCreatedAt());
    }

    // vérifie que createdAt correspond à un instant proche de la création de l'objet
    public function testCreatedAtIsSetToNow(): void
    {
        $before = new \DateTimeImmutable();

        $favorite = new Favorite();

        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $favorite->getCreatedAt());
        $this->assertLessThanOrEqual($after, $favorite->getCreatedAt());
    }

    // vérifie que le user peut être défini directement sur le favorite
    public function testSetUserDirectly(): void
    {
        $favorite = new Favorite();
        $user = new User();

        $favorite->setUser($user);

        $this->assertSame($user, $favorite->getUser());
    }

    // vérifie que le user peut être supprimé directement depuis le favorite
    public function testUnsetUserDirectly(): void
    {
        $favorite = new Favorite();
        $user = new User();

        $favorite->setUser($user);
        $favorite->setUser(null);

        $this->assertNull($favorite->getUser());
    }

    // vérifie que le vehicle peut être défini directement sur le favorite
    public function testSetVehicleDirectly(): void
    {
        $favorite = new Favorite();
        $vehicle = new Vehicle();

        $favorite->setVehicle($vehicle);

        $this->assertSame($vehicle, $favorite->getVehicle());
    }

    // vérifie que le vehicle peut être supprimé directement depuis le favorite
    public function testUnsetVehicleDirectly(): void
    {
        $favorite = new Favorite();
        $vehicle = new Vehicle();

        $favorite->setVehicle($vehicle);
        $favorite->setVehicle(null);

        $this->assertNull($favorite->getVehicle());
    }

    // vérifie qu’un favorite peut exister sans user au départ
    public function testFavoriteCanExistWithoutUserInitially(): void
    {
        $favorite = new Favorite();
        $vehicle = new Vehicle();

        $favorite->setVehicle($vehicle);

        $this->assertNull($favorite->getUser());
        $this->assertSame($vehicle, $favorite->getVehicle());
    }

    // vérifie que setUser(null) fonctionne correctement
    public function testSetUserWithNull(): void
    {
        $favorite = new Favorite();

        $favorite->setUser(null);

        $this->assertNull($favorite->getUser());
    }

    // vérifie que setVehicle(null) fonctionne correctement
    public function testSetVehicleWithNull(): void
    {
        $favorite = new Favorite();

        $favorite->setVehicle(null);

        $this->assertNull($favorite->getVehicle());
    }

    // vérifie le cycle de vie complet d’un favorite
    public function testCompleteFavoriteLifecycle(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setVehicle($vehicle);

        $this->assertSame($user, $favorite->getUser());
        $this->assertSame($vehicle, $favorite->getVehicle());
        $this->assertInstanceOf(\DateTimeImmutable::class, $favorite->getCreatedAt());
    }

    // vérifie que l'ajout d’un favorite met bien à jour la relation user
    public function testAddFavoriteSetsUser(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        $user->addFavorite($favorite);

        $this->assertCount(1, $user->getFavorites());
        $this->assertSame($user, $favorite->getUser());
    }

    /**
     * vérifie qu’un favori est correctement supprimé
     * et que la relation côté favorite est annulée
     */
    public function testRemoveFavoriteUnsetsUser(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        $user->addFavorite($favorite);

        $user->removeFavorite($favorite);

        $this->assertCount(0, $user->getFavorites());
        $this->assertNull($favorite->getUser());
    }

    /**
     * vérifie qu’un même objet favorite ne peut pas être ajouté deux fois
     */
    public function testNoDuplicateFavoriteObjects(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        $user->addFavorite($favorite);
        $user->addFavorite($favorite);

        $this->assertCount(1, $user->getFavorites());
    }

    /**
     * vérifie la gestion correcte d’une collection avec plusieurs favoris
     */
    public function testMultipleFavoritesCollection(): void
    {
        $user = new User();

        $vehicle1 = new Vehicle();
        $vehicle2 = new Vehicle();

        $fav1 = new Favorite();
        $fav1->setVehicle($vehicle1);

        $fav2 = new Favorite();
        $fav2->setVehicle($vehicle2);

        $user->addFavorite($fav1);
        $user->addFavorite($fav2);

        $this->assertCount(2, $user->getFavorites());
        $this->assertTrue($user->getFavorites()->contains($fav1));
        $this->assertTrue($user->getFavorites()->contains($fav2));
    }

    /**
     * vérifie que la suppression d’un favori n’impacte pas les autres
     */
    public function testRemoveOneKeepsOthers(): void
    {
        $user = new User();

        $vehicle1 = new Vehicle();
        $vehicle2 = new Vehicle();

        $fav1 = new Favorite();
        $fav1->setVehicle($vehicle1);

        $fav2 = new Favorite();
        $fav2->setVehicle($vehicle2);

        $user->addFavorite($fav1);
        $user->addFavorite($fav2);

        $user->removeFavorite($fav1);

        $this->assertCount(1, $user->getFavorites());
        $this->assertTrue($user->getFavorites()->contains($fav2));
    }
}
