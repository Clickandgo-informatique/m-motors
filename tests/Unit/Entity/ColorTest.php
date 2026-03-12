<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Color;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ColorTest extends KernelTestCase
{
    /**
     * Validator Symfony utilisé pour tester les contraintes
     * Assert\NotBlank, Assert\Length, Assert\Regex, etc.
     */
    private ValidatorInterface $validator;

    /**
     * On démarre le Kernel Symfony afin d'accéder au service Validator
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * Vérifie que l'entité peut être instanciée correctement
     */
    public function testColorCreation(): void
    {
        $color = new Color();

        $this->assertInstanceOf(Color::class, $color, "Correctement instancié");
    }

    /**
     * Vérifie que l'id est null par défaut
     * (normal car Doctrine le génère en base de données)
     */
    public function testIdIsNullByDefault(): void
    {
        $color = new Color();

        $this->assertNull($color->getId());
    }

    /**
     * Vérifie que le nom est null par défaut
     */
    public function testNameIsNullByDefault(): void
    {
        $color = new Color();

        $this->assertNull($color->getName());
    }

    /**
     * Vérifie que le setter fonctionne
     * Attention : l'entité normalise le texte avec :
     * ucfirst(strtolower($name))
     */
    public function testSetName(): void
    {
        $color = new Color();

        $color->setName('name of the color');

        $this->assertSame('Name of the color', $color->getName());
    }

    /**
     * Vérifie la normalisation automatique du nom
     */
    public function testNameNormalization(): void
    {
        $color = new Color();

        $color->setName('bLuE');

        $this->assertSame('Blue', $color->getName());
    }

    /**
     * Vérifie que le setter retourne bien l'entité
     * (pattern fluide utilisé par Doctrine)
     */
    public function testSetNameReturnsSelf(): void
    {
        $color = new Color();

        $result = $color->setName('Red');

        $this->assertSame($color, $result);
    }

    /**
     * Vérifie la contrainte NotBlank
     */
    public function testNameCannotBeBlank(): void
    {
        $color = new Color();

        // Attention : espace pour déclencher NotBlank
        $color->setName(' ');

        $errors = $this->validator->validate($color);

        $this->assertCount(1, $errors);
    }

    /**
     * Vérifie le message de la contrainte NotBlank
     */
    public function testNameCannotBeBlankMessage(): void
    {
        $color = new Color();

        $color->setName('');

        $errors = $this->validator->validate($color);

        $this->assertSame(
            "Le nom de la couleur est obligatoire",
            $errors[0]->getMessage()
        );
    }

    /**
     * Vérifie la longueur minimale (Length min)
     */
    public function testNameIsTooShort(): void
    {
        $color = new Color();

        $color->setName('a');

        $errors = $this->validator->validate($color);

        $this->assertGreaterThan(0, count($errors));
    }

    /**
     * Vérifie le message d'erreur pour Length min
     */
    public function testNameTooShortMessage(): void
    {
        $color = new Color();

        $color->setName('a');

        $errors = $this->validator->validate($color);

        $this->assertSame(
            "La couleur doit contenir au moins 2 caractères",
            $errors[0]->getMessage()
        );
    }

    /**
     * Vérifie la longueur maximale (Length max)
     */
    public function testNameIsTooLong(): void
    {
        $color = new Color();

        $color->setName(str_repeat('a', 121));

        $errors = $this->validator->validate($color);

        $this->assertGreaterThan(0, count($errors));
    }

    /**
     * Vérifie le message d'erreur pour Length max
     */
    public function testNameTooLongMessage(): void
    {
        $color = new Color();

        $color->setName(str_repeat('a', 121));

        $errors = $this->validator->validate($color);

        $this->assertSame(
            "La couleur ne peut dépasser 50 caractères",
            $errors[0]->getMessage()
        );
    }

    /**
     * Vérifie un nom valide avec espace
     */
    public function testValidColorName(): void
    {
        $color = new Color();

        $color->setName('Bleu Clair');

        $errors = $this->validator->validate($color);

        $this->assertCount(0, $errors);
    }

    /**
     * Vérifie un nom valide avec accents
     */
    public function testAccentColorName(): void
    {
        $color = new Color();

        $color->setName('Émeraude');

        $errors = $this->validator->validate($color);

        $this->assertCount(0, $errors);
    }

    /**
     * Vérifie un nom valide avec tiret
     */
    public function testHyphenColorName(): void
    {
        $color = new Color();

        $color->setName('Bleu-gris');

        $errors = $this->validator->validate($color);

        $this->assertCount(0, $errors);
    }

    /**
     * Vérifie la contrainte Regex avec des caractères interdits
     */
    public function testInvalidColorName(): void
    {
        $color = new Color();

        $color->setName('Bleu123');

        $errors = $this->validator->validate($color);

        $this->assertGreaterThan(0, count($errors));

        $this->assertSame(
            'La couleur contient des caractères invalides',
            $errors[0]->getMessage()
        );
    }

    /**
     * Vérifie que la collection vehicles est initialisée
     */
    public function testVehicleCollectionIsEmptyByDefault(): void
    {
        $color = new Color();

        // Vérifie que vehicles est une Collection Doctrine
        $this->assertInstanceOf(Collection::class, $color->getVehicles());

        // Vérifie que la collection est vide
        $this->assertCount(0, $color->getVehicles());
    }
}
