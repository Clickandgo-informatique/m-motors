<?php

namespace App\Tests\Entity;

use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests unitaires sur la gestion des favoris utilisateur.
 *
 * Objectifs :
 * - Vérifier le bon fonctionnement de la collection OneToMany (User -> Favorite)
 * - Vérifier la cohérence de la relation bidirectionnelle (Favorite.user)
 * - Vérifier le comportement lors des ajouts et suppressions
 */
class UserFavoritesTest extends KernelTestCase
{
    /**
     * Vérifie qu’un favori est correctement ajouté à l’utilisateur
     * et que la relation côté Favorite est bien définie.
     */
    public function testAddFavoriteSetsUser(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        // Ajout du favori à l'utilisateur
        $user->addFavorite($favorite);

        // Vérifie que la collection contient bien un élément
        $this->assertCount(1, $user->getFavorites());

        // Vérifie que la relation est bien synchronisée côté Favorite
        $this->assertSame($user, $favorite->getUser());
    }

    /**
     * Vérifie qu’un favori est correctement supprimé
     * et que la relation côté Favorite est annulée.
     */
    public function testRemoveFavoriteUnsetsUser(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        $user->addFavorite($favorite);

        // Suppression du favori
        $user->removeFavorite($favorite);

        // La collection doit être vide
        $this->assertCount(0, $user->getFavorites());

        // La relation doit être supprimée côté Favorite
        $this->assertNull($favorite->getUser());
    }

    /**
     * Vérifie qu’un même objet Favorite ne peut pas être ajouté deux fois.
     */
    public function testNoDuplicateFavoriteObjects(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $favorite = new Favorite();
        $favorite->setVehicle($vehicle);

        // Tentative d'ajout en double
        $user->addFavorite($favorite);
        $user->addFavorite($favorite);

        // La collection ne doit contenir qu’un seul élément
        $this->assertCount(1, $user->getFavorites());
    }

    /**
     * Vérifie la gestion correcte d’une collection avec plusieurs favoris.
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

        // Ajout de plusieurs favoris
        $user->addFavorite($fav1);
        $user->addFavorite($fav2);

        // Vérifie que les deux sont bien présents
        $this->assertCount(2, $user->getFavorites());
        $this->assertTrue($user->getFavorites()->contains($fav1));
        $this->assertTrue($user->getFavorites()->contains($fav2));
    }

    /**
     * Vérifie que la suppression d’un favori n’impacte pas les autres.
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

        // Suppression d’un seul favori
        $user->removeFavorite($fav1);

        // Vérifie que l’autre est toujours présent
        $this->assertCount(1, $user->getFavorites());
        $this->assertTrue($user->getFavorites()->contains($fav2));
    }
}
