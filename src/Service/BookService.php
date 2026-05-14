<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BookCreateRequest;
use App\Dto\BookUpdateRequest;
use App\Entity\Book;
use App\Exception\BookNotFoundException;
use App\Repository\BookRepository;
use DateTimeImmutable;

/**
 * Pure application-layer service: knows nothing about HTTP and is therefore
 * trivially unit-testable. Controllers stay thin, the repository handles
 * persistence, and the service coordinates the two.
 */
class BookService
{
    public function __construct(
        private readonly BookRepository $books,
    ) {
    }

    /**
     * @return list<Book>
     */
    public function listBooks(int $page, int $perPage): array
    {
        return $this->books->findPaginated($page, $perPage);
    }

    public function countBooks(): int
    {
        return $this->books->countAll();
    }

    public function getBook(int $id): Book
    {
        $book = $this->books->find($id);

        if ($book === null) {
            throw new BookNotFoundException($id);
        }

        return $book;
    }

    public function createBook(BookCreateRequest $request): Book
    {
        // Validation has already passed at this point, so every field is safe
        // to dereference - but we still narrow the types explicitly to avoid
        // PHPStan/Psalm-level "possibly null" noise.
        $book = new Book(
            title: (string) $request->title,
            publisher: (string) $request->publisher,
            author: (string) $request->author,
            genre: (string) $request->genre,
            publicationDate: new DateTimeImmutable((string) $request->publicationDate),
            wordCount: (int) $request->wordCount,
            priceUsd: $this->normalizePrice($request->priceUsd),
        );

        $this->books->save($book);

        return $book;
    }

    public function updateBook(int $id, BookUpdateRequest $request): Book
    {
        $book = $this->getBook($id);

        if ($request->title !== null) {
            $book->setTitle($request->title);
        }
        if ($request->publisher !== null) {
            $book->setPublisher($request->publisher);
        }
        if ($request->author !== null) {
            $book->setAuthor($request->author);
        }
        if ($request->genre !== null) {
            $book->setGenre($request->genre);
        }
        if ($request->publicationDate !== null) {
            $book->setPublicationDate(new DateTimeImmutable($request->publicationDate));
        }
        if ($request->wordCount !== null) {
            $book->setWordCount($request->wordCount);
        }
        if ($request->priceUsd !== null) {
            $book->setPriceUsd($this->normalizePrice($request->priceUsd));
        }

        $this->books->save($book);

        return $book;
    }

    public function deleteBook(int $id): void
    {
        $book = $this->getBook($id);
        $this->books->remove($book);
    }

    /**
     * Doctrine's decimal column expects a string with at most two decimals.
     * Centralising the conversion keeps the rest of the code free of
     * formatting concerns.
     */
    private function normalizePrice(int|float|string|null $price): string
    {
        return number_format((float) $price, 2, '.', '');
    }
}
