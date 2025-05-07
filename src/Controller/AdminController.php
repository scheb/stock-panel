<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\Type\StockType;
use App\Provider\StockPriceProvider;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private StockPriceProvider $stockPriceProvider,
        private StockRepository $stockRepository,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Add a stock
     */
    #[Route(path: '/add', name: 'stock_add')]
    public function addAction(Request $request): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->stockPriceProvider->initStock($stock);
                $this->em->persist($stock);
                $this->em->flush();
                return $this->redirect($this->generateUrl("stock_table"));
            }
        }

        return $this->render("Admin/add.html.twig", [
            'form' => $form->createView(),
            'categories' => $this->stockRepository->getAllCategories(),
        ]);
    }

    /**
     * Edit a stock
     */
    #[Route(path: '/edit/{id}', name: 'stock_edit')]
    public function editAction(Request $request, int $id): Response
    {
        $stock = $this->getStock($id);
        if (!$stock) {
            throw $this->createNotFoundException("Stock id " . $id . " not found!");
        }

        $form = $this->createForm(StockType::class, $stock);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->em->persist($stock);
                $this->em->flush();
                return $this->redirect($this->generateUrl("stock_table"));
            }
        }

        return $this->render("Admin/edit.html.twig", [
            'form' => $form->createView(),
            'categories' => $this->stockRepository->getAllCategories(),
            'id' => $id,
        ]);
    }

    /**
     * Remove a stock
     */
    #[Route(path: '/delete/{id}', name: 'stock_delete', requirements: ['id' => '[0-9]+'])]
    public function deleteAction(int $id): Response
    {
        $stock = $this->getStock($id);
        if ($stock) {
            $this->em->remove($stock);
            $this->em->flush();
        }
        return $this->redirect($this->generateUrl("stock_table"));
    }

    private function getStock(int $id): ?Stock
    {
        return $this->stockRepository->findOneById($id);
    }


    /**
     * Edit a stock
     */
    #[Route(path: '/favourite/{id}', name: 'stock_favourite')]
    public function favouriteAction(Request $request, int $id): Response
    {
        $stock = $this->getStock($id);
        if (!$stock) {
            throw $this->createNotFoundException("Stock id " . $id . " not found!");
        }

        $stock->setFavourite(!$stock->isFavourite());
        $this->em->persist($stock);
        $this->em->flush();

        return $this->redirect($this->generateUrl('stock_table'));
    }
}
