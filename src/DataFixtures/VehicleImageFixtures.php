<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Service\ImageProcessor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VehicleImageFixtures extends Fixture implements DependentFixtureInterface
{
    private ImageProcessor $imageProcessor;
    private LoggerInterface $logger;
    private array $imageFiles = [];

    public function __construct(
        ImageProcessor $imageProcessor,
        LoggerInterface $logger
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->logger = $logger;
    }

    public function load(ObjectManager $manager, OutputInterface $output = null): void
    {
        $vehicles = $manager->getRepository(\App\Entity\Vehicle::class)->findAll();

        $assignedImagesDirectory = dirname(__DIR__, 2) . '/public/uploads/vehicles_assigned_images';

        $this->clearAssignedImagesDirectory($assignedImagesDirectory, $output);

        $this->imageFiles = $this->loadImages();

        $sourceDirectory = dirname(__DIR__, 2) . '/public/uploads/vehicles_samples_images';

        foreach ($vehicles as $vehicle) {

            $nbImages = random_int(1, 5);

            if (count($this->imageFiles) === 0) {
                continue;
            }

            $selected = (array) array_rand(
                array_flip($this->imageFiles),
                min($nbImages, count($this->imageFiles))
            );

            foreach ($selected as $file) {

                $path = $sourceDirectory . '/' . $file;

                $data = $this->imageProcessor->processFromPath($path, 'vehicles_assigned_images');

                $image = new Image();

                $image->setFilename($data['filename']);
                $image->setThumbnail($data['thumbnail']);
                $image->setImagePath($data['path']);
                $image->setOriginalName($file);
                $image->setMimeType('image/webp');
                $image->setSize(0);
                $image->setVehicle($vehicle);

                $manager->persist($image);
            }
        }

        $manager->flush();

        if ($output) {
            $output->writeln('<info>VehicleImageFixtures : génération terminée</info>');
        }

        $this->logger->info('VehicleImageFixtures exécutée avec succès');
    }

    private function loadImages(): array
    {
        $directory = dirname(__DIR__, 2) . '/public/uploads/vehicles_samples_images';

        return array_values(array_filter(scandir($directory), function ($file) use ($directory) {
            if ($file === '.' || $file === '..') {
                return false;
            }

            if ($file === 'default-vehicle.png') {
                return false;
            }

            return is_file($directory . '/' . $file);
        }));
    }

    private function clearAssignedImagesDirectory(string $directory, ?OutputInterface $output = null): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if ($output) {
            $output->writeln('<comment>Le dossier vehicles_assigned_images a été vidé avec succès.</comment>');
        }

        $this->logger->info('vehicles_assigned_images vidé par VehicleImageFixtures');
    }

    public function getDependencies(): array
    {
        return [
            VehicleFixtures::class,
        ];
    }
}
