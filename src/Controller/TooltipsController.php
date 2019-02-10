<?php

namespace App\Controller;

use App\Exception\InvalidTooltipsRequestException;
use App\Service\Content\Tooltips;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TooltipsController extends AbstractController
{
    /** @var Tooltips */
    private $tooltips;
    
    public function __construct(Tooltips $tooltips)
    {
        $this->tooltips = $tooltips;
    }
    
    /**
     * @Route("/tooltips")
     */
    public function Tooltips(Request $request)
    {
        // decode request
        $json = json_decode($request->getContent());

        if (empty($json)) {
            throw new InvalidTooltipsRequestException();
        }
        
        $response = $this->tooltips->handle($json);

        return $this->json($response);
    }
}
