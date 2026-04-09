<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteController extends AbstractController
{
    private FavoriteRepository $favoriteRepository;
    private EntityManagerInterface $em;

    public function __construct(FavoriteRepository $favoriteRepository, EntityManagerInterface $em)
    {
        $this->favoriteRepository = $favoriteRepository;
        $this->em = $em;
    }

    #[Route('/vehicle/{id}/favorite', name: 'vehicle_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(Vehicle $vehicle, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $session = $request->getSession();

        if ($user) {
            // Utilisateur connecté → DB
            $added = $this->favoriteRepository->toggleFavorite($user, $vehicle);
        } else {
<<<<<<< HEAD
            // Session
=======
            // Utilisateur non connecté → session
>>>>>>> feature/vehicles_favorites
            $favorites = $session->get('favorites', []);
            $vehicleId = $vehicle->getId();

            if (in_array($vehicleId, $favorites)) {
                $favorites = array_diff($favorites, [$vehicleId]);
                $added = false;
            } else {
                $favorites[] = $vehicleId;
                $added = true;
            }

            $session->set('favorites', $favorites);
        }

        return new JsonResponse([
            'success' => true,
            'added' => $added,
            'vehicleId' => $vehicle->getId(),
        ]);
    }

    #[Route('/vehicle/{id}/is-favorite', name: 'vehicle_is_favorite', methods: ['GET'])]
    public function isFavorite(Vehicle $vehicle, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $session = $request->getSession();
<<<<<<< HEAD
=======

        $isFavorite = false;
>>>>>>> feature/vehicles_favorites

        if ($user) {
            $isFavorite = $this->favoriteRepository->isFavorite($user, $vehicle);
        } else {
            $favorites = $session->get('favorites', []);
            $isFavorite = in_array($vehicle->getId(), $favorites);
        }

        return new JsonResponse([
            'vehicleId' => $vehicle->getId(),
            'isFavorite' => $isFavorite,
        ]);
    }
}
