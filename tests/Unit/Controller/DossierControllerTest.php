<?php

namespace App\Tests\Unit\Controller;

use App\Controller\DossierController;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;

class DossierControllerTest extends TestCase
{
    /**
     * Vérifie que l'autocomplete retourne une liste vide
     * lorsque le terme recherché contient moins de 2 caractères.
     */
    public function testSearchAutocompleteReturnsEmptyList(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(DossierController::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['json'])
            ->getMock();

        $controller
            ->expects($this->once())
            ->method('json')
            ->with(['items' => []])
            ->willReturn(new JsonResponse(['items' => []]));

        $request = new Request([
            'autocomplete' => true,
            'q' => 'a',
        ]);

        $repository = $this->createMock(DossierRepository::class);
        $paginator = $this->createMock(PaginatorInterface::class);

        $response = $controller->search(
            $request,
            $repository,
            $paginator
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * Vérifie la génération des résultats d'autocomplete.
     */
    public function testSearchAutocompleteReturnsItems(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(DossierController::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['json', 'generateUrl'])
            ->getMock();

        $controller
            ->method('generateUrl')
            ->willReturn('/admin/dossier/1/edit');

        $repository = $this->createMock(DossierRepository::class);

        $repository
            ->expects($this->once())
            ->method('findForAutocomplete')
            ->with('DO')
            ->willReturn([
                [
                    'id' => 1,
                    'dossierCode' => 'DOS-001',
                ],
            ]);

        $controller
            ->expects($this->once())
            ->method('json')
            ->with([
                'items' => [
                    [
                        'id' => 1,
                        'label' => 'DOS-001',
                        'url' => '/admin/dossier/1/edit',
                    ],
                ],
            ])
            ->willReturn(new JsonResponse());

        $request = new Request([
            'autocomplete' => true,
            'q' => 'DO',
        ]);

        $paginator = $this->createMock(PaginatorInterface::class);

        $controller->search(
            $request,
            $repository,
            $paginator
        );
    }

    /**
     * Vérifie la recherche paginée classique.
     */
    public function testSearchPaginatorMode(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(DossierController::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['json', 'renderView'])
            ->getMock();

        $query = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->createMock(DossierRepository::class);

        $repository
            ->expects($this->once())
            ->method('searchForPaginator')
            ->with('FORD')
            ->willReturn($query);

        $pagination = $this->createMock(PaginationInterface::class);

        $pagination
            ->method('getTotalItemCount')
            ->willReturn(125);

        $pagination
            ->method('getCurrentPageNumber')
            ->willReturn(3);

        $paginator = $this->createMock(PaginatorInterface::class);

        $paginator
            ->expects($this->once())
            ->method('paginate')
            ->with($query, 3, 20)
            ->willReturn($pagination);

        $controller
            ->method('renderView')
            ->willReturn('html');

        $controller
            ->expects($this->once())
            ->method('json')
            ->with([
                'results' => 'html',
                'paginationTop' => 'html',
                'paginationBottom' => 'html',
                'totalItems' => 125,
                'currentPage' => 3,
            ])
            ->willReturn(new JsonResponse());

        $request = new Request([
            'q' => 'FORD',
            'page' => 3,
        ]);

        $controller->search(
            $request,
            $repository,
            $paginator
        );
    }
}
