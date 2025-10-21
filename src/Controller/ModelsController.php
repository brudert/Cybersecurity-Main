<?php

namespace App\Infrastructure\Controller;

use App\Infrastructure\Freezebee\ModelApi;
use App\Model\FreezebeeDTO\ModelInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ModelsController extends AbstractController
{
    public function __construct(
        private ModelApi $apiclient,
        private ValidatorInterface $validator
    ) {}

    /**
     * Créer un nouveau modèle
     */
    #[Route('/rnd/model/', name: 'create_model', methods: ['POST'])] 
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent ajouter des modèles')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['name'], $data['series_id'], $data['ingredients'], $data['characteristics'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Champs requis manquants: name, series_id, ingredients, characteristics'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputModel = new ModelInput([
                "name" => $data["name"],
                "description" => $data["description"] ?? null,
                "pUHT" => $data["pUHT"] ?? null,
                "series_id" => $data["series_id"],
                "ingredients" => $data["ingredients"],
                "characteristics" => $data["characteristics"]
            ]);

            // Validation
            $errors = $this->validator->validate($inputModel);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                
                return $this->json([
                    'status' => 'error',
                    'message' => 'Modèle du produit non valide',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $model = $this->apiclient->addModel($inputModel);

            return $this->json([
                'status' => 'success',
                'data' => $model,
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
     * Récupérer tous les modèles
     */
    #[Route('/rnd/model/', name: 'get_all_models', methods: ['GET'])]
    public function getAllModels(): JsonResponse
    {
        try {
            $models = $this->apiclient->getModels();

            return $this->json([
                'status' => 'success',
                'data' => $models,
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
     * Récupérer un modèle par son ID
     */
    #[Route('/rnd/model/{id}', name: 'get_model', methods: ['GET'])]
    public function getModel(int $id): JsonResponse
    {
        try {
            $model = $this->apiclient->getModel($id);

            if (!$model) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Modèle non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'status' => 'success',
                'data' => $model,
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
     * Mettre à jour un modèle
     */
    #[Route('/rnd/model/{id}', name: 'update_model', methods: ['PUT'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent modifer des modèles')]
    public function updateModel(Request $request, int $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (empty($data)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Aucune donnée fournie pour la mise à jour'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputModel = new ModelInput([
                "name" => $data["name"] ?? null,
                "description" => $data["description"] ?? null,
                "pUHT" => $data["pUHT"] ?? null,
                "series_id" => $data["series_id"] ?? null,
                "ingredients" => $data["ingredients"] ?? null,
                "characteristics" => $data["characteristics"] ?? null
            ]);

            // Validation optionnelle pour la mise à jour
            $errors = $this->validator->validate($inputModel);
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

            $model = $this->apiclient->updateModel($id, $inputModel);

            return $this->json([
                'status' => 'success',
                'data' => $model,
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
     * Supprimer un modèle
     */
    #[Route('/rnd/model/{id}', name: 'delete_model', methods: ['DELETE'])]
      #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent supprimer des modèles')]
    public function deleteModel(int $id): JsonResponse
    {
        try {
            $this->apiclient->deleteModel($id);

            return $this->json([
                'status' => 'success',
                'message' => 'Modèle supprimé avec succès',
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

    
}