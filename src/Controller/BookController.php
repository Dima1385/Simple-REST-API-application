<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookCreateRequest;
use App\Dto\BookResponse;
use App\Dto\BookUpdateRequest;
use App\Service\BookService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * REST endpoints for managing books in the library.
 *
 * The controller intentionally stays thin: it only deals with HTTP-level
 * concerns (decoding payloads, picking the right status code, shaping
 * response bodies). All business logic lives in {@see BookService}.
 */
#[AsController]
#[Route('/api/books', name: 'api_books_')]
#[OA\Tag(name: 'Books')]
class BookController
{
    public function __construct(
        private readonly BookService $service,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of books.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: BookResponse::class))),
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'perPage', type: 'integer', example: 20),
                        new OA\Property(property: 'total', type: 'integer', example: 137),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function list(
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $perPage = 20,
    ): JsonResponse {
        $books = $this->service->listBooks($page, $perPage);

        return new JsonResponse([
            'items' => array_map(BookResponse::fromEntity(...), $books),
            'page' => max(1, $page),
            'perPage' => max(1, min(100, $perPage)),
            'total' => $this->service->countBooks(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        responses: [
            new OA\Response(response: 200, description: 'The book.', content: new OA\JsonContent(ref: new Model(type: BookResponse::class))),
            new OA\Response(response: 404, description: 'Book not found.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function get(int $id): JsonResponse
    {
        $book = $this->service->getBook($id);

        return new JsonResponse(BookResponse::fromEntity($book));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: BookCreateRequest::class)),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Book created.', content: new OA\JsonContent(ref: new Model(type: BookResponse::class))),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function create(
        #[MapRequestPayload] BookCreateRequest $payload,
    ): JsonResponse {
        $book = $this->service->createBook($payload);

        return new JsonResponse(
            BookResponse::fromEntity($book),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    #[OA\Patch(
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: BookUpdateRequest::class)),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Book updated.', content: new OA\JsonContent(ref: new Model(type: BookResponse::class))),
            new OA\Response(response: 404, description: 'Book not found.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(
        int $id,
        #[MapRequestPayload] BookUpdateRequest $payload,
    ): JsonResponse {
        $book = $this->service->updateBook($id, $payload);

        return new JsonResponse(BookResponse::fromEntity($book));
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        responses: [
            new OA\Response(response: 204, description: 'Book deleted.'),
            new OA\Response(response: 404, description: 'Book not found.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function delete(int $id): JsonResponse
    {
        $this->service->deleteBook($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
