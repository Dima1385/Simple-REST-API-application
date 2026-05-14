<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Book;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testItExposesAllConstructorValuesViaGetters(): void
    {
        $publicationDate = new DateTimeImmutable('1999-10-30');

        $book = new Book(
            title: 'The Pragmatic Programmer',
            publisher: 'Addison-Wesley',
            author: 'Andrew Hunt, David Thomas',
            genre: 'Software Engineering',
            publicationDate: $publicationDate,
            wordCount: 100000,
            priceUsd: '39.95',
        );

        self::assertNull($book->getId());
        self::assertSame('The Pragmatic Programmer', $book->getTitle());
        self::assertSame('Addison-Wesley', $book->getPublisher());
        self::assertSame('Andrew Hunt, David Thomas', $book->getAuthor());
        self::assertSame('Software Engineering', $book->getGenre());
        self::assertSame($publicationDate, $book->getPublicationDate());
        self::assertSame(100000, $book->getWordCount());
        self::assertSame('39.95', $book->getPriceUsd());
        self::assertEqualsWithDelta(
            (new DateTimeImmutable())->getTimestamp(),
            $book->getCreatedAt()->getTimestamp(),
            5,
        );
    }

    public function testSettersAreFluentAndUpdateState(): void
    {
        $book = $this->makeBook();

        $result = $book
            ->setTitle('Clean Code')
            ->setPublisher('Prentice Hall')
            ->setAuthor('Robert C. Martin')
            ->setGenre('Software Engineering')
            ->setPublicationDate(new DateTimeImmutable('2008-08-01'))
            ->setWordCount(95000)
            ->setPriceUsd('34.99');

        self::assertSame($book, $result, 'Setters must return $this for fluent chaining.');
        self::assertSame('Clean Code', $book->getTitle());
        self::assertSame('Prentice Hall', $book->getPublisher());
        self::assertSame('Robert C. Martin', $book->getAuthor());
        self::assertSame('Software Engineering', $book->getGenre());
        self::assertSame('2008-08-01', $book->getPublicationDate()->format('Y-m-d'));
        self::assertSame(95000, $book->getWordCount());
        self::assertSame('34.99', $book->getPriceUsd());
    }

    public function testTouchAdvancesUpdatedAt(): void
    {
        $book = $this->makeBook();
        $original = $book->getUpdatedAt();

        usleep(1_100_000); // give the clock a chance to tick a second over

        $book->touch();

        self::assertGreaterThanOrEqual(
            $original->getTimestamp(),
            $book->getUpdatedAt()->getTimestamp(),
        );
    }

    private function makeBook(): Book
    {
        return new Book(
            title: 'Original',
            publisher: 'Pub',
            author: 'Author',
            genre: 'Genre',
            publicationDate: new DateTimeImmutable('2020-01-01'),
            wordCount: 1000,
            priceUsd: '9.99',
        );
    }
}
