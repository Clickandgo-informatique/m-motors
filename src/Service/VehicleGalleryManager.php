<?php

namespace App\Service;

use App\Entity\Vehicle;
use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VehicleGalleryManager
{
    public function __construct(
        private ImageProcessor $imageProcessor,
        private EntityManagerInterface $em
    ) {}

    /**
     * Upload et persiste une liste d'images pour un véhicule
     *
     * @param UploadedFile[] $files
     */
    public function uploadImages(Vehicle $vehicle, array $files, string $destination = 'vehicles'): void
    {
        if (empty($files)) {
            return;
        }

        $position = $this->getNextPosition($vehicle);

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $result = $this->imageProcessor->process($file, $destination);

            $image = new Image();
            $image->setVehicle($vehicle);
            $image->setFilename($result['webp']);
            $image->setOriginalName($file->getClientOriginalName());
            $image->setMimeType($file->getMimeType() ?? 'image/webp');
            $image->setSize($file->getSize() ?? 0);
            $image->setPosition($position++);

            if ($this->isFirstImage($vehicle)) {
                $image->setIsFeatured(true);
            }

            $this->em->persist($image);
        }

        $this->em->flush();
    }

    /**
     * Supprime une image (DB + fichiers)
     */
    public function deleteImage(Image $image, string $destination = 'vehicles'): void
    {
        $this->imageProcessor->delete($image->getFilename(), $destination);

        $vehicle = $image->getVehicle();

        $this->em->remove($image);
        $this->em->flush();

        $this->reorderPositions($vehicle);
        $this->ensureFeatured($vehicle);
    }

    /**
     * Définit une image comme principale
     */
    public function setFeatured(Image $image): void
    {
        $vehicle = $image->getVehicle();

        foreach ($vehicle->getImages() as $img) {
            $img->setIsFeatured(false);
        }

        $image->setIsFeatured(true);

        $this->em->flush();
    }

    /**
     * Réordonne les positions après suppression
     */
    private function reorderPositions(Vehicle $vehicle): void
    {
        $position = 1;

        foreach ($vehicle->getImages() as $image) {
            $image->setPosition($position++);
        }

        $this->em->flush();
    }

    /**
     * Assure qu'il y a toujours une image featured
     */
    private function ensureFeatured(Vehicle $vehicle): void
    {
        foreach ($vehicle->getImages() as $image) {
            if ($image->isFeatured()) {
                return;
            }
        }

        $first = $vehicle->getImages()->first();

        if ($first) {
            $first->setIsFeatured(true);
            $this->em->flush();
        }
    }

    private function getNextPosition(Vehicle $vehicle): int
    {
        $max = 0;

        foreach ($vehicle->getImages() as $image) {
            $max = max($max, $image->getPosition());
        }

        return $max + 1;
    }

    private function isFirstImage(Vehicle $vehicle): bool
    {
        return count($vehicle->getImages()) === 0;
    }
}
