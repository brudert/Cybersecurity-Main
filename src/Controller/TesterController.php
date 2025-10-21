<?php

namespace App\Controller;

use App\Infrastructure\Freezebee\IngredientsApi;
use App\Model\FreezebeeDTO\IngredientInput;
use GuzzleHttp\Client;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class TesterController extends AbstractController
{   // espace pour les testeurs uniquement :)
    
    #[Route('/test', name: 'test_page')] 

    public function show(): Response
    {
        return new Response('welcome to testers space');
    }
}