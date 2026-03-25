<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteController extends AbstractController
{
    private $favoriteRepository;
    private $em;
    private $session;

    public function __construct(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, SessionInterface $session)
    {
        $this->favoriteRepository = $favoriteRepository;
        $this->em = $em;
        $this->session = $session;
    }

    #[Route('/vehicle/{id}/favorite', name: 'vehicle_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(Vehicle $vehicle, Request $request)
    {
        $user = $this->getUser();

        if ($user) {
            // Utilisateur connecté → on utilise la table Favorite
            $added = $this->favoriteRepository->toggleFavorite($user, $vehicle);
        } else {
            // Utilisateur non connecté → stockage en session
            $favorites = $this->session->get('favorites', []);
            $vehicleId = $vehicle->getId();

            if (in_array($vehicleId, $favorites)) {
                $favorites = array_diff($favorites, [$vehicleId]);
                $added = false;
            } else {
                $favorites[] = $vehicleId;
                $added = true;
            }

            $this->session->set('favorites', $favorites);
        }

        return new JsonResponse([
            'success' => true,
            'added' => $added,
            'vehicleId' => $vehicle->getId(),
        ]);
    }

    #[Route('/vehicle/{id}/is-favorite', name: 'vehicle_is_favorite', methods: ['GET'])]
    public function isFavorite(Vehicle $vehicle)
    {
        $user = $this->getUser();
        $isFavorite = false;

        if ($user) {
            $isFavorite = $this->favoriteRepository->isFavorite($user, $vehicle);
        } else {
            $favorites = $this->session->get('favorites', []);
            $isFavorite = in_array($vehicle->getId(), $favorites);
        }

        return new JsonResponse([
            'vehicleId' => $vehicle->getId(),
            'isFavorite' => $isFavorite,
        ]);
    }
}
