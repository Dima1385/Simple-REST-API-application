<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\BookCreateRequest;
use App\Dto\BookUpdateRequest;
use App\Entity\Book;
use App\Exception\BookNotFoundException;
use App\Repository\BookRepository;
use App\Service\BookService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookServiceTest extends TestCase
{
    public function testCreateBookPersistsAndReturnsHydratedEntity(): void
    {
        $repo = $this->createMock(BookRepository::class);

        $repo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Book::class));

        $service = new BookService($repo);

        $req = new BookCreateRequest();
        $req->title = 'The Pragmatic Programmer';
        $req->publisher = 'Addison-Wesley';
        $req->author = 'Andrew Hunt, David Thomas';
        $req->genre = 'Software Engineering';
        $req->publicationDate = '1999-10-30';
        $req->wordCount = 100000;
        $req->priceUsd = 39.95;

        $book = $service->createBook($req);

        self::assertSame('The Pragmatic Programmer', $book->getTitle());
        self::assertSame('Addison-Wesley', $book->getPublisher());
        self::assertSame('Andrew Hunt, David Thomas', $book->getAuthor());
        self::assertSame('Software Engineering', $book->getGenre());
        self::assertSame('1999-10-30', $book->getPublicationDate()->format('Y-m-d'));
        self::assertSame(100000, $book->getWordCount());
        // The service must normalize numeric prices to a 2-decimal string.
        self::assertSame('39.95', $book->getPriceUsd());
    }

    public function testGetBookThrowsWhenMissing(): void
    {
        $repo = $this->createMock(BookRepository::class);
        $repo->method('find')->with(999)->willReturn(null);

        $service = new BookService($repo);

        $this->expectException(BookNotFoundException::class);
        $service->getBook(999);
    }

    public function testUpdateBookAppliesOnlyProvidedFields(): void
    {
        $existing = new Book(
            title: 'Original Title',
            publisher: 'Original Publisher',
            author: 'Original Author',
            genre: 'Original Genre',
            publicationDate: new DateTimeImmutable('2010-01-01'),
            wordCount: 50000,
            priceUsd: '19.99',
        );

        $repo = $this->createMock(BookRepository::class);
        $repo->method('find')->with(7)->willReturn($existing);
        $repo->expects(self::once())->method('save')->with($existing);

        $service = new BookService($repo);

        $patch = new BookUpdateRequest();
        $patch->title = 'New Title';
        $patch->priceUsd = '24.50';

        $updated = $service->updateBook(7, $patch);

        self::assertSame('New Title', $updated->getTitle());
        self::assertSame('24.50', $updated->getPriceUsd());
        self::assertSame('Original Publisher', $updated->getPublisher(), 'untouched fields must stay the same');
        self::assertSame('Original Author', $updated->getAuthor());
        self::assertSame('Original Genre', $updated->getGenre());
        self::assertSame(50000, $updated->getWordCount());
    }

    public function testDeleteBookRemovesViaRepository(): void
    {
        $book = new Book(
            title: 't', publisher: 'p', author: 'a', genre: 'g',
            publicationDate: new DateTimeImmutable('2020-01-01'),
            wordCount: 1, priceUsd: '1.00',
        );

        $repo = $this->createMock(BookRepository::class);
        $repo->method('find')->with(3)->willReturn($book);
        $repo->expects(self::once())->method('remove')->with($book);

        (new BookService($repo))->deleteBook(3);
    }
}
