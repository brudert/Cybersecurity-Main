<?php

namespace App\Controller;


use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class ProductionController extends AbstractController
{   // espace pour les chefs de productions uniquement
    
    #[Route('/production', name: 'production_page')] 

    public function show(): Response
    {
        
        
        return new Response('welcome to producers space');
    }
}