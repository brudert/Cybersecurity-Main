<?php

namespace App\Infrastructure\Controller;

use App\Infrastructure\Freezebee\ProcessApi;
use App\Model\FreezebeeDTO\ProcessInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProcessController extends AbstractController
{
    public function __construct(
        private ProcessApi $apiclient,
        private ValidatorInterface $validator
    ) {}

    /**
     * Créer un nouveau processus
     */
    #[Route('/rnd/process/', name: 'create_process', methods: ['POST'])] 
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent ajouter des process')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['name'], $data['description'], $data['tests'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Champs requis manquants: name, description, tests'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputProcess = new ProcessInput([
                "name" => $data["name"],
                "description" => $data["description"],
                "tests" => $data["tests"]
            ]);

            // Validation
            $errors = $this->validator->validate($inputProcess);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                
                return $this->json([
                    'status' => 'error',
                    'message' => 'Processus non valide',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $process = $this->apiclient->addProcess($inputProcess);

            return $this->json([
                'status' => 'success',
                'data' => $process,
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
     * Récupérer un processus par son ID
     */
    #[Route('/rnd/process/{id}', name: 'get_process', methods: ['GET'])]
    public function getProcess(string $id): JsonResponse
    {
        try {
            $process = $this->apiclient->getProcess($id);

            return $this->json([
                'status' => 'success',
                'data' => $process,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e->getMessage();
            
            // Si c'est une erreur 404 du microservice
            if (strpos($message, '404') !== false || strpos($message, 'Not Found') !== false) {
                $statusCode = Response::HTTP_NOT_FOUND;
                $message = 'Processus non trouvé';
            }
            
            return $this->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Mettre à jour un processus
     */
    #[Route('/rnd/process/{id}', name: 'update_process', methods: ['PUT'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent modifer des process')]
    public function updateProcess(Request $request, string $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (empty($data)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Aucune donnée fournie pour la mise à jour'
                ], Response::HTTP_BAD_REQUEST);
            }

            $inputProcess = new ProcessInput([
                "name" => $data["name"] ?? null,
                "description" => $data["description"] ?? null,
                "tests" => $data["tests"] ?? null
            ]);

            // Validation optionnelle pour la mise à jour
            $errors = $this->validator->validate($inputProcess);
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

            $process = $this->apiclient->updateProcess($id, $inputProcess);

            return $this->json([
                'status' => 'success',
                'data' => $process,
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
     * Supprimer un processus
     */
    #[Route('/rnd/process/{id}', name: 'delete_process', methods: ['DELETE'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent supprimer des process')]
    public function deleteProcess(string $id): JsonResponse
    {
        try {
            $this->apiclient->deleteProcess($id);

            return $this->json([
                'status' => 'success',
                'message' => 'Processus supprimé avec succès',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e->getMessage();
            
            // Si c'est une erreur 404 du microservice
            if (strpos($message, '404') !== false || strpos($message, 'Not Found') !== false) {
                $statusCode = Response::HTTP_NOT_FOUND;
                $message = 'Processus non trouvé';
            }
            
            return $this->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Valider un processus
     */
    #[Route('/rnd/process/{id}/validate', name: 'validate_process', methods: ['PUT'])]
    #[IsGranted('ROLE_TESTEUR', message: 'Seuls les testeurs peuvent valider des process')]
    public function validateProcess(string $id): JsonResponse
    {
        try {
            $process = $this->apiclient->validateProcess($id);

            return $this->json([
                'status' => 'success',
                'data' => $process,
                'message' => 'Processus validé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e->getMessage();
            
            // Si c'est une erreur 404 du microservice
            if (strpos($message, '404') !== false || strpos($message, 'Not Found') !== false) {
                $statusCode = Response::HTTP_NOT_FOUND;
                $message = 'Processus non trouvé';
            }
            
            return $this->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Récupérer tous les processus
     */
    #[Route('/rnd/process/', name: 'get_all_processes', methods: ['GET'])]
    public function getAllProcesses(): JsonResponse
    {
        try {
            // Note: L'API actuelle ne fournit pas de méthode pour récupérer tous les processus
            // Cette méthode devra être implémentée plus tard
            
            return $this->json([
                'status' => 'error',
                'message' => 'Fonctionnalité non implémentée - Récupération de tous les processus'
            ], Response::HTTP_NOT_IMPLEMENTED);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}