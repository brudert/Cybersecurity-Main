<?php
namespace App\Controller;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Infrastructure\Freezebee\IngredientsApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Model\FreezebeeDTO\IngredientInput;
use App\Model\FreezebeeDTO\IngredientOutput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class IngredientsController extends AbstractController
{
    public function __construct(
        private IngredientsApi $apiclient,
        private ValidatorInterface $validator
    )
    {}


    #[Route('/rnd/ingredient/', name: 'create_ingredient', methods: ['POST'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent ajouter des ingrédients')]
    public function create(Request $request) : JsonResponse
    {
        try 
        {   
            $data = json_decode($request->getContent(), true);
            $inputIngredients = new IngredientInput(
                [
                    "name" => $data["name"],
                    "description" => $data["description"]
                ]
            );

            $validator = $inputIngredients;
            // validation
            $errors = $this->validator->validate($validator);
            if (count($errors)>0) 
            {
                return $this->json([
                    'status' => 'error',
                    'message' => 'ingrédient non valide',
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $ingredient = $this->apiclient->createIngredients($data);

            return $this->json([
                'status' => 'success',
                'data' => $ingredient,
            ], Response::HTTP_CREATED);

        }
        catch (\Exception $e)
        {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    #[Route('/ingredient/', name: 'get_ingredients', methods: ['GET'])]
    /*#[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent voir des ingrédients')]*/
    public function get(Request $request) : JsonResponse 
    {
        try
        {
            $ingredients = $this->apiclient->getIngredients();
            return $this->json([
                'status' => 'success',
                'data' => $ingredients,
            ], Response::HTTP_OK);

        }
        catch (\Exception $e)
        {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/rnd/ingredient/{ingredientId}', name: 'update_ingredient', methods: ['PUT'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent modifier des ingrédients')]
    public function update(Request $request, string $ingredientId) : JsonResponse
    {
        try
        {
            $data = json_decode($request->getContent(), true);
            if (isset($data['name']) && (isset($data['description'])))
            {
                $inputIngredients = new IngredientInput([
                "name" => $data['name'],
                "description" => $data['description']
            ]);
            }
            elseif (isset($data['name']) && !(isset($data['description'])))
            {
                $inputIngredients = new IngredientInput([
                "name" => $data['name'],
            ]);
            }
            elseif (!(isset($data['name'])) && (isset($data['description'])))
            {
                $inputIngredients = new IngredientInput([
                "description" => $data['description'],
            ]);
            }

            $validator = $inputIngredients;
            // validation
            $errors = $this->validator->validate($validator);
            if (count($errors)>0) 
            {
                return $this->json([
                    'status' => 'error',
                    'message' => 'ingrédient non valide',
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $new_ingredient = $this->apiclient->modifyIngredients($ingredientId, $inputIngredients);
            return $this->json([
                'status' => 'success',
                'data' => $new_ingredient,
               

            ], Response::HTTP_OK);
        }
        catch (\Exception $e)
        {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

   

}