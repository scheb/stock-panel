<?php

namespace App\Controller;

use App\Provider\SearchResultProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends Controller
{
    /**
     * Show the stock panel
     * @Route("/search", name="stock_search")
     */
    public function searchAction(): Response
    {
        return $this->render("Search/search.html.twig");
    }

    /**
     * Execute the search
     * @Route("/searchResult", name="stock_search_result")
     */
    public function searchResultAction(Request $request): Response
    {
        $searchTerm = $request->get("term");
        $result = [];

        if ($searchTerm) {
            $searchProvider = $this->get(SearchResultProvider::class);
            $result = $searchProvider->search($searchTerm);
        }

        $response = $this->render("Search/result.html.twig", [
            'result' => $result,
        ]);
        $response->setExpires(new \DateTime("+1 hours"));
        return $response;
    }
}
