<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class FrontinfoController extends AbstractController
{
    #[Route(path: '/me', name: 'user_info',  methods: ['GET'])]
    public function UserInfo(Request $request): JsonResponse
    {
     
        $user = $this->getUser();
        if (!$user)
        {
            return $this->Json([
                'error' => 'aucun utilisateur authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }
        else
        {
            return $this->Json([
                'username' => $user->getUserIdentifier(),
                'role' => $user->getRoles()
            ], Response::HTTP_OK);
        }

    }

}
