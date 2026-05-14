<?php

declare(strict_types=1);

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload shape for `POST /api/books`.
 *
 * All fields are required; validation is performed declaratively via
 * `Assert` attributes and executed automatically by the Symfony Validator
 * before the controller hits the service layer.
 */
final class BookCreateRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed {{ limit }} characters.')]
    #[OA\Property(example: 'The Pragmatic Programmer')]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Publisher is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Publisher cannot exceed {{ limit }} characters.')]
    #[OA\Property(example: 'Addison-Wesley')]
    public ?string $publisher = null;

    #[Assert\NotBlank(message: 'Author is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Author cannot exceed {{ limit }} characters.')]
    #[OA\Property(example: 'Andrew Hunt, David Thomas')]
    public ?string $author = null;

    #[Assert\NotBlank(message: 'Genre is required.')]
    #[Assert\Length(max: 100, maxMessage: 'Genre cannot exceed {{ limit }} characters.')]
    #[OA\Property(example: 'Software Engineering')]
    public ?string $genre = null;

    #[Assert\NotBlank(message: 'Publication date is required.')]
    #[Assert\Date(message: 'Publication date must be a valid date (YYYY-MM-DD).')]
    #[OA\Property(format: 'date', example: '1999-10-30')]
    public ?string $publicationDate = null;

    #[Assert\NotNull(message: 'Word count is required.')]
    #[Assert\Type(type: 'integer', message: 'Word count must be an integer.')]
    #[Assert\PositiveOrZero(message: 'Word count cannot be negative.')]
    #[OA\Property(example: 80000)]
    public ?int $wordCount = null;

    #[Assert\NotNull(message: 'Price is required.')]
    #[Assert\Type(type: 'numeric', message: 'Price must be a number.')]
    #[Assert\PositiveOrZero(message: 'Price cannot be negative.')]
    #[OA\Property(example: 39.95, description: 'Price in US Dollars.')]
    public int|float|string|null $priceUsd = null;
}
