<?php

namespace App\Controller\Admin;

use App\Entity\VehicleBadge;
use App\Form\VehicleBadgeType;
use App\Repository\VehicleBadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/vehicle-badges')]
class VehicleBadgeController extends AbstractController
{
    #[Route('/', name: 'admin_vehicle_badge_index', methods: ['GET'])]
    public function index(VehicleBadgeRepository $repository): Response
    {
        return $this->render('admin/vehicle_badge/index.html.twig', [
            'badges' => $repository->findActiveOrdered(),
        ]);
    }

    #[Route('/new', name: 'admin_vehicle_badge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $badge = new VehicleBadge();

        $form = $this->createForm(VehicleBadgeType::class, $badge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($badge);
            $em->flush();

            return $this->redirectToRoute('admin_vehicle_badge_index');
        }

        return $this->render('admin/vehicle_badge/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_vehicle_badge_edit', methods: ['GET', 'POST'])]
    public function edit(
        VehicleBadge $badge,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(VehicleBadgeType::class, $badge);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_vehicle_badge_index');
        }

        return $this->render('admin/vehicle_badge/edit.html.twig', [
            'form' => $form->createView(),
            'badge' => $badge,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_vehicle_badge_delete', methods: ['POST'])]
    public function delete(
        VehicleBadge $badge,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $badge->getId(), $request->request->get('_token'))) {
            $em->remove($badge);
            $em->flush();
        }

        return $this->redirectToRoute('admin_vehicle_badge_index');
    }
}