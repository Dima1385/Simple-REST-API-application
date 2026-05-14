<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Book;
use OpenApi\Attributes as OA;

/**
 * Outbound representation of a Book in the public API.
 *
 * Keeping this separate from the Doctrine entity gives us a stable wire
 * contract and prevents leaking internal lifecycle fields by accident.
 */
final class BookResponse
{
    public function __construct(
        #[OA\Property(example: 42)]
        public readonly int $id,
        #[OA\Property(example: 'The Pragmatic Programmer')]
        public readonly string $title,
        #[OA\Property(example: 'Addison-Wesley')]
        public readonly string $publisher,
        #[OA\Property(example: 'Andrew Hunt, David Thomas')]
        public readonly string $author,
        #[OA\Property(example: 'Software Engineering')]
        public readonly string $genre,
        #[OA\Property(format: 'date', example: '1999-10-30')]
        public readonly string $publicationDate,
        #[OA\Property(example: 80000)]
        public readonly int $wordCount,
        #[OA\Property(example: 39.95, description: 'Price in US Dollars.')]
        public readonly float $priceUsd,
        #[OA\Property(format: 'date-time', example: '2026-01-15T12:34:56+00:00')]
        public readonly string $createdAt,
        #[OA\Property(format: 'date-time', example: '2026-01-15T12:34:56+00:00')]
        public readonly string $updatedAt,
    ) {
    }

    public static function fromEntity(Book $book): self
    {
        return new self(
            id: (int) $book->getId(),
            title: $book->getTitle(),
            publisher: $book->getPublisher(),
            author: $book->getAuthor(),
            genre: $book->getGenre(),
            publicationDate: $book->getPublicationDate()->format('Y-m-d'),
            wordCount: $book->getWordCount(),
            priceUsd: (float) $book->getPriceUsd(),
            createdAt: $book->getCreatedAt()->format(DATE_ATOM),
            updatedAt: $book->getUpdatedAt()->format(DATE_ATOM),
        );
    }
}
