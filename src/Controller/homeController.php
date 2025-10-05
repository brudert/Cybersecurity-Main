<?php

namespace App\Controller;


use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class homeController extends AbstractController
{
    #[Route('/home', name: 'home_page')] 

    public function show(): Response
    {
        
        
        return new Response('welcome to home page');
    }
}