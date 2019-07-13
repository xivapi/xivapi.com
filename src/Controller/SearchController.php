<?php

namespace App\Controller;

use App\Common\Exceptions\SearchException;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResponse;
use App\Service\Search\Search;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package App\Controller
 */
class SearchController extends AbstractController
{
    /** @var Search */
    private $search;

    function __construct(Search $search)
    {
        $this->search = $search;
    }
    
    /**
     * @Route("/search/playground", name="search_playground")
     */
    public function playground()
    {
        return $this->render('search/play.html.twig');
    }

    /**
     * @Route("/Search")
     * @Route("/search")
     */
    public function search(Request $request)
    {
        try {
            $searchRequest = new SearchRequest();
            $searchRequest->buildFromRequest($request);
    
            $searchResponse = new SearchResponse($searchRequest);
            $this->search->handleRequest($searchRequest, $searchResponse);
    
            # print_r($searchResponse->response);die;
    
            if ($request->get('print_query')) {
                return $this->json($searchResponse->query);
            }
    
            return $this->json($searchResponse->response);
        } catch (\Exception $ex) {
            throw new SearchException("Search Error: {$ex->getMessage()}");
        }
    }

    /**
     * @Route("/Search/Mapping{index}")
     * @Route("/search/mapping/{index}")
     */
    public function searchMapping($index)
    {
        return $this->json(
            $this->search->handleMappingRequest($index)
        );
    }
    
    /**
     * @Route("/Lore")
     * @Route("/lore")
     */
    public function lore(Request $request)
    {
        try {
            $request->request->set('indexes', 'lore_finder');
            $request->request->set('string_column', 'Text_%s');
    
            // setup request
            $searchRequest = new SearchRequest();
            $searchRequest->buildFromRequest($request);
        
            $searchResponse = new SearchResponse($searchRequest);
            $this->search->handleRequest($searchRequest, $searchResponse);
        
            # print_r($searchResponse->response);die;
        
            return $this->json($searchResponse->response);
        } catch (\Exception $ex) {
            throw new SearchException("Search Error: {$ex->getMessage()}");
        }
    }
    
    
}
