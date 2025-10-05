<?php

namespace App\Controller;


use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class ResearchController extends AbstractController
{   // espace pour les chercheurs uniquements 
    #[Route('/research', name: 'research_page')] 

    public function show(): Response
    {
        
        
        return new Response('welcome to researchers space');
    }
}