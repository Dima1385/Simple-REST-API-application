<?php

declare(strict_types=1);

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class HealthController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    #[OA\Tag(name: 'Health')]
    #[OA\Get(
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is up.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
