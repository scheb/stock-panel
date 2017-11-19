<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\Type\StockType;
use App\Provider\StockPriceProvider;
use App\Repository\StockRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends Controller
{
    /**
     * Add a stock
     * @Route("/add", name="stock_add")
     */
    public function addAction(Request $request): Response
    {
        $stock = new Stock();
        if ($symbol = $request->get("symbol")) {
            $stockProvider = $this->get(StockPriceProvider::class);
            $stock = $stockProvider->createStock($symbol);
        }

        $form = $this->createForm(StockType::class, $stock);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getObjectManager();
                $em->persist($stock);
                $em->flush();
                return $this->redirect($this->generateUrl("stock_panel"));
            }
        }

        return $this->render("Admin/add.html.twig", [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit a stock
     * @Route("/edit/{id}", name="stock_edit")
     */
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
                $em = $this->getObjectManager();
                $em->persist($stock);
                $em->flush();
                return $this->redirect($this->generateUrl("stock_panel"));
            }
        }

        return $this->render("Admin/edit.html.twig", [
            'form' => $form->createView(),
            'id' => $id,
        ]);
    }

    /**
     * Remove a stock
     * @Route("/delete/{id}", name="stock_delete", requirements={"id": "[0-9]+"})
     */
    public function deleteAction(int $id): Response
    {
        $stock = $this->getStock($id);
        if ($stock) {
            $em = $this->getObjectManager();
            $em->remove($stock);
            $em->flush();
        }
        return $this->redirect($this->generateUrl("stock_panel"));
    }

    private function getStock(int $id): ?Stock
    {
        return $this->getStockRepo()->findOneById($id);
    }

    private function getObjectManager(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    private function getStockRepo(): StockRepository
    {
        return $this->getObjectManager()->getRepository(Stock::class);
    }
}
