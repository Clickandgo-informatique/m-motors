<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Vehicle;
use App\Form\VehicleFormType;
use App\Service\VehicleGalleryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller dédié au CRUD des véhicules
 * IMPORTANT : aucune logique frontend / listing ici
 */
#[Route('/vehicles')]
class VehicleController extends AbstractController
{
    /**
     * Création d’un véhicule
     */
    #[Route('/admin/new', name: 'vehicle_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $vehicle = new Vehicle();

        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule créé.');

            return $this->redirectToRoute('vehicles_index');
        }

        return $this->render('vehicles/edit.html.twig', [
            'form' => $form,
            'title' => 'Créer un véhicule',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Edition d’un véhicule
     */
    #[Route('/admin/{id<\d+>}/edit', name: 'vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule modifié.');

            return $this->redirectToRoute('vehicles_index');
        }
        return $this->render('vehicles/_vehicle_form.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
            'title' => "Modifier un véhicule"
        ]);
    }

    //Affichage d'un véhicule
    #[Route('/{id<\d+>}/show', name: 'vehicle_show', methods: ['GET', 'POST'])]
    public function show(
        Vehicle $vehicle,
    ): Response {
        return $this->render('vehicles/show.html.twig', [
            'vehicle' => $vehicle,
            'title' => "Fiche véhicule"
        ]);
    }

    /**
     * Suppression d’un véhicule
     */
    #[Route('/admin/{id<\d+>}', name: 'vehicle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid('delete' . $vehicle->getId(), $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule supprimé.');
        }

        return $this->redirectToRoute('vehicles_index');
    }
    /**
     * Retourne les images d’un véhicule
     */
    #[Route('/admin/{id<\d+>}/images', name: 'vehicle_images', methods: ['GET'])]
    public function images(
        Vehicle $vehicle
    ): JsonResponse {

        $documents = [];

        foreach ($vehicle->getImages() as $image) {
            $documents[] = [
                'id' => $image->getId(),
                'fileName' => $image->getFilename(),
                'originalName' => $image->getOriginalName(),
                'createdAt' => $image->getCreatedAt()?->format('d/m/Y H:i'),
                'isFeatured' => $image->isFeatured(),
                'position' => $image->getPosition(),
                'path' => 'vehicles/' . $image->getFilename()
            ];
        }

        return $this->json([
            'documents' => $documents
        ]);
    }

    /**
     * Upload des images véhicule
     */
    #[Route('/admin/images/upload', name: 'vehicle_image_upload', methods: ['POST'])]
    public function uploadImages(
        Request $request,
        EntityManagerInterface $em,
        VehicleGalleryManager $galleryManager
    ): JsonResponse {

        $vehicleId = $request->request->get('destination');

        if (!$vehicleId) {
            return $this->json([
                'success' => false,
                'message' => 'Destination manquante'
            ], 400);
        }

        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);

        if (!$vehicle) {
            return $this->json([
                'success' => false,
                'message' => 'Véhicule introuvable'
            ], 404);
        }

        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier'
            ], 400);
        }

        $galleryManager->uploadImages($vehicle, $files);

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * Suppression image
     */
    #[Route('/admin/images/{id}', name: 'vehicle_image_delete', methods: ['DELETE'])]
    public function deleteImage(
        Image $image,
        VehicleGalleryManager $galleryManager
    ): JsonResponse {

        $galleryManager->deleteImage($image);

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * Définit l’image principale
     */
    #[Route('/admin/images/{id}/featured', name: 'vehicle_image_featured', methods: ['POST'])]
    public function featuredImage(
        Image $image,
        VehicleGalleryManager $galleryManager
    ): JsonResponse {

        $galleryManager->setFeatured($image);

        return $this->json([
            'success' => true
        ]);
    }
}
