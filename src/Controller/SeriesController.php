<?php
namespace App\Controller;

use App\Infrastructure\Freezebee\SeriesApi;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Model\FreezebeeDTO\SeriesInput;
use App\Model\FreezebeeDTO\SeriesOutput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class SeriesController extends AbstractController
{
    public function __construct(
        private SeriesApi $apiclient,
        private ValidatorInterface $validator
    )
    {}


    #[Route('/rnd/series/', name: 'create_series', methods: ['POST'])]
    #[IsGranted('ROLE_CHERCHEUR', message: 'Seuls les chercheurs peuvent ajouter des ingrédients')]
    public function create(Request $request) : JsonResponse
    {
        try 
        {   
            $data = json_decode($request->getContent(), true);
            $inputSeries = new SeriesInput(
                [
                    "name" => $data["name"],
                    "description" => $data["description"]
                ]
            );

            $validator = $inputSeries;
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

            $series = $this->apiclient->createSeries($data);

            return $this->json([
                'status' => 'success',
                'data' => $series,
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
    #[Route('/series', name: 'get_series', methods: ['GET'])]
    public function get(Request $request) : JsonResponse 
    {
        try
        {
            $series = $this->apiclient->getSeries();
            return $this->json([
                'status' => 'success',
                'data' => $series,
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
    #[Route('/rnd/series/{seriesId}', name: 'update_series', methods: ['PUT'])]
    public function update(Request $request, string $seriesId) : JsonResponse
    {
        try
        {
            $data = json_decode($request->getContent(), true);
            if (isset($data['name']) && (isset($data['description'])))
            {
                $inputSeries = new SeriesInput([
                "name" => $data['name'],
                "description" => $data['description']
            ]);
            }
            elseif (isset($data['name']) && !(isset($data['description'])))
            {
                $inputSeries = new SeriesInput([
                "name" => $data['name'],
            ]);
            }
            elseif (!(isset($data['name'])) && (isset($data['description'])))
            {
                $inputSeries = new SeriesInput([
                "description" => $data['description'],
            ]);
            }

            $validator = $inputSeries;
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
            
            $new_series = $this->apiclient->modifySeries($seriesId, $inputSeries);
            return $this->json([
                'status' => 'success',
                'data' => $new_series,
               

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