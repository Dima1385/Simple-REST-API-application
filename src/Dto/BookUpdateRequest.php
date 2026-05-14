<?php

declare(strict_types=1);

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload shape for `PATCH /api/books/{id}`.
 *
 * Every field is optional - only the fields the client actually sends will
 * be applied to the entity. Validation rules only fire on non-null values,
 * which is what makes partial updates work correctly.
 */
final class BookUpdateRequest
{
    #[Assert\Length(min: 1, max: 255)]
    #[OA\Property(nullable: true, example: 'Clean Code')]
    public ?string $title = null;

    #[Assert\Length(min: 1, max: 255)]
    #[OA\Property(nullable: true, example: 'Prentice Hall')]
    public ?string $publisher = null;

    #[Assert\Length(min: 1, max: 255)]
    #[OA\Property(nullable: true, example: 'Robert C. Martin')]
    public ?string $author = null;

    #[Assert\Length(min: 1, max: 100)]
    #[OA\Property(nullable: true, example: 'Software Engineering')]
    public ?string $genre = null;

    #[Assert\Date(message: 'Publication date must be a valid date (YYYY-MM-DD).')]
    #[OA\Property(nullable: true, format: 'date', example: '2008-08-01')]
    public ?string $publicationDate = null;

    #[Assert\Type(type: 'integer', message: 'Word count must be an integer.')]
    #[Assert\PositiveOrZero(message: 'Word count cannot be negative.')]
    #[OA\Property(nullable: true, example: 95000)]
    public ?int $wordCount = null;

    #[Assert\Type(type: 'numeric', message: 'Price must be a number.')]
    #[Assert\PositiveOrZero(message: 'Price cannot be negative.')]
    #[OA\Property(nullable: true, example: 29.99)]
    public int|float|string|null $priceUsd = null;
}
