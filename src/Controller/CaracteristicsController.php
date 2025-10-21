<?php

namespace App\Infrastructure\Controller;

use App\Infrastructure\Freezebee\CharacteristicsApi;
use App\Model\FreezebeeDTO\CharacteristicInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CharacteristicsController extends AbstractController
{
    public function __construct(
        private CharacteristicsApi $apiclient,
        private ValidatorInterface $validator
    ) {}

    /**
     * Créer une nouvelle caractéristique
     */
    #[Route('/rnd/characteristic/', name: 'create_characteristic', methods: ['POST'])] 
   #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent ajouter des caractéristiques')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['name'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Champ requis manquant: name'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputCharacteristic = new CharacteristicInput([
                "name" => $data["name"],
                "description" => $data["description"] ?? null
            ]);

            // Validation
            $errors = $this->validator->validate($inputCharacteristic);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                
                return $this->json([
                    'status' => 'error',
                    'message' => 'Caractéristique non valide',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $characteristic = $this->apiclient->createCharacteristic($inputCharacteristic);

            return $this->json([
                'status' => 'success',
                'data' => $characteristic,
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer toutes les caractéristiques
     */
    #[Route('/rnd/characteristic/', name: 'get_all_characteristics', methods: ['GET'])]
    public function getAllCharacteristics(): JsonResponse
    {
        try {
            $characteristics = $this->apiclient->getCharacteristic();

            return $this->json([
                'status' => 'success',
                'data' => $characteristics,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer une caractéristique par son ID
     */
    #[Route('/rnd/characteristic/{id}', name: 'get_characteristic', methods: ['GET'])]
    public function getCharacteristic(string $id): JsonResponse
    {
        try {
            // Note: L'API actuelle ne fournit pas de méthode getCharacteristic par ID
            // On récupère toutes les caractéristiques et on filtre
            $allCharacteristics = $this->apiclient->getCharacteristic();
            
            // Recherche de la caractéristique par ID dans le tableau
            $characteristic = null;
            foreach ($allCharacteristics as $char) {
                if ($char->getId() === $id || $char->getId()->toString() === $id) {
                    $characteristic = $char;
                    break;
                }
            }

            if (!$characteristic) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Caractéristique non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'status' => 'success',
                'data' => $characteristic,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e->getMessage();
            
            // Si c'est une erreur 404 du microservice
            if (strpos($message, '404') !== false || strpos($message, 'Not Found') !== false) {
                $statusCode = Response::HTTP_NOT_FOUND;
                $message = 'Caractéristique non trouvée';
            }
            
            return $this->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Mettre à jour une caractéristique
     */
    #[Route('/rnd/characteristic/{id}', name: 'update_characteristic', methods: ['PUT'])]
  #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent modifer des caractéristiques')]
    public function updateCharacteristic(Request $request, string $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (empty($data)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Aucune donnée fournie pour la mise à jour'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputCharacteristic = new CharacteristicInput([
                "name" => $data["name"] ?? null,
                "description" => $data["description"] ?? null
            ]);

            // Validation optionnelle pour la mise à jour
            $errors = $this->validator->validate($inputCharacteristic);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                
                return $this->json([
                    'status' => 'error',
                    'message' => 'Données de mise à jour non valides',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $characteristic = $this->apiclient->modifyCharacteristic($id, $inputCharacteristic);

            return $this->json([
                'status' => 'success',
                'data' => $characteristic,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer une caractéristique
     */
    #[Route('/rnd/characteristic/{id}', name: 'delete_characteristic', methods: ['DELETE'])]
  #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent supprimer des caractéristiques')]
    public function deleteCharacteristic(string $id): JsonResponse
    {
        try {
            // Note: L'API actuelle ne fournit pas de méthode deleteCharacteristic
            // Cette méthode devra être implémentée plus tard
            
            return $this->json([
                'status' => 'error',
                'message' => 'Fonctionnalité de suppression non implémentée'
            ], Response::HTTP_NOT_IMPLEMENTED);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}