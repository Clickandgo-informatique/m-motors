<?php

namespace App\Controller\Admin;

use App\Entity\SupplierOrder;
use App\Enum\VehicleStatus;
use App\Repository\SupplierOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/supplier-order')]
class SupplierOrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SupplierOrderRepository $orderRepository
    ) {}

    /**
     * Liste des commandes fournisseurs
     */
    #[Route('/', name: 'admin_supplier_orders', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orders = $this->orderRepository->findAllOrdered();

        return $this->render('admin/supplier_order/index.html.twig', [
            'orders' => $orders
        ]);
    }

    /**
     * Détail d'une commande fournisseur
     */
    #[Route('/{id}', name: 'admin_supplier_order_show', methods: ['GET'])]
    public function show(SupplierOrder $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/supplier_order/show.html.twig', [
            'order' => $order
        ]);
    }

    /**
     * Livraison commande fournisseur
     *
     * - passe commande à DELIVERED
     * - met véhicule à AVAILABLE
     */
    #[Route('/{id}/deliver', name: 'admin_supplier_order_deliver', methods: ['POST'])]
    public function deliver(SupplierOrder $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order->markAsDelivered();

        $vehicle = $order->getVehicle();
        $vehicle->setStatus(VehicleStatus::AVAILABLE);

        $this->em->flush();

        $this->addFlash('success', 'Commande fournisseur livrée.');

        return $this->redirectToRoute('admin_supplier_orders');
    }

    /**
     * Annulation commande fournisseur
     *
     * - passe commande à CANCELLED
     * - remet véhicule à AVAILABLE
     */
    #[Route('/{id}/cancel', name: 'admin_supplier_order_cancel', methods: ['POST'])]
    public function cancel(SupplierOrder $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order->cancel();

        $vehicle = $order->getVehicle();
        $vehicle->setStatus(VehicleStatus::AVAILABLE);

        $this->em->flush();

        $this->addFlash('warning', 'Commande fournisseur annulée.');

        return $this->redirectToRoute('admin_supplier_orders');
    }
}
