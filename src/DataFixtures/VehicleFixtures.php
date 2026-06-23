<?php

namespace App\DataFixtures;

use App\Entity\Color;
use App\Entity\Image;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Enum\VehicleStatus;
use App\Enum\VehicleUsageType;
use App\Service\ImageProcessor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    private const MIN_PER_STATUS = 100;

    private ImageProcessor $imageProcessor;

    private array $imageFiles = [];

    public function __construct(ImageProcessor $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }

    public function load(ObjectManager $manager): void
    {
        $vehicles = [];

        $faker = Factory::create('fr_FR');

        // nettoyage du dossier uploads/vehicles
        $this->clearVehicleUploadsDirectory();

        // récupération des données de référence
        $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
        $suppliers = $manager->getRepository(Supplier::class)->findAll();
        $colors = $manager->getRepository(Color::class)->findAll();

        // chargement des images disponibles
        $this->imageFiles = $this->loadImages();

        $statuses = [
            VehicleStatus::AVAILABLE_FOR_SALE,
            VehicleStatus::AVAILABLE_FOR_RENT,
            VehicleStatus::RENTED,
            VehicleStatus::MAINTENANCE,
            VehicleStatus::ORDERED,
        ];

        $usageTypes = [
            VehicleUsageType::SALE,
            VehicleUsageType::RENT,
            VehicleUsageType::BOTH,
        ];

        foreach ($statuses as $status) {

            for ($i = 0; $i < self::MIN_PER_STATUS; $i++) {

                $vehicle = new Vehicle();

                // statut du véhicule
                $vehicle->setStatus($status);

                // type d’usage
                $vehicle->setUsageType($usageTypes[array_rand($usageTypes)]);

                // vin généré aléatoirement
                $vehicle->setVin(strtoupper($faker->regexify('[a-hj-npr-z0-9]{17}')));

                // modèle et fournisseur
                $vehicleModel = $vehicleModels[array_rand($vehicleModels)];
                $vehicle->setVehicleModel($vehicleModel);

                $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

                // données dépendantes du modèle
                $vehicle->setGearType($vehicleModel->getGearType());
                $vehicle->setFuelType($vehicleModel->getFuelType());

                // couleur optionnelle
                if (!empty($colors)) {
                    $vehicle->setColor($colors[array_rand($colors)]);
                }

                // prix
                $vehicle->setPrice($faker->numberBetween(8000, 90000));

                // kilométrage
                $vehicle->setMileage($faker->numberBetween(0, 300000));

                // date de première immatriculation
                $year = $faker->numberBetween(2005, (int) date('Y'));

                $vehicle->setFirstRegistrationDate(
                    \DateTimeImmutable::createFromFormat('Y-m-d', $year . '-01-01')
                );

                // véhicule mis en avant par défaut
                $vehicle->setIsFeatured(false);

                // ajout des images
                $this->assignImages($vehicle, $manager);

                $manager->persist($vehicle);

                $vehicles[] = $vehicle;
            }
        }

        // génère exactement 10 véhicules mis en avant pour galerie homepage
        shuffle($vehicles);

        $featuredVehicles = array_slice($vehicles, 0, 10);

        foreach ($featuredVehicles as $vehicle) {
            $vehicle->setStatus(VehicleStatus::AVAILABLE_FOR_SALE);
            $vehicle->setIsFeatured(true);
        }

        $manager->flush();
    }

    private function assignImages(Vehicle $vehicle, ObjectManager $manager): void
    {
        // dossier images véhicules
        $directory = dirname(__DIR__, 2) . '/public/uploads/vehicles_images';

        // sélection des images disponibles
        $nbImages = random_int(1, 5);

        $selected = (array) array_rand(
            array_flip($this->imageFiles),
            min($nbImages, count($this->imageFiles))
        );

        foreach ($selected as $file) {

            $path = $directory . '/' . $file;

            // traitement image depuis fichier local
            $data = $this->imageProcessor->processFromPath(
                $path,
                'vehicles'
            );

            // création entité image
            $image = new Image();

            $image->setFilename($data['filename']);
            $image->setThumbnail($data['thumbnail']);
            $image->setImagePath($data['path']);
            $image->setOriginalName($file);
            $image->setMimeType('image/webp');
            $image->setSize(0);
            $image->setVehicle($vehicle);

            $vehicle->addImage($image);

            $manager->persist($image);
        }
    }

    private function loadImages(): array
    {
        $directory = dirname(__DIR__, 2) . '/public/uploads/vehicles_images';

        return array_values(array_filter(scandir($directory), function ($file) use ($directory) {

            return $file !== '.'
                && $file !== '..'
                && is_file($directory . '/' . $file);
        }));
    }

    private function clearVehicleUploadsDirectory(): void
    {
        $directory = dirname(__DIR__, 2) . '/public/uploads/vehicles';

        if (!is_dir($directory)) {
            return;
        }

        $files = scandir($directory);

        foreach ($files as $file) {

            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
            SupplierFixtures::class,
            ColorFixtures::class,
        ];
    }
}
