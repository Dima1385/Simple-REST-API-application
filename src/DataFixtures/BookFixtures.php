<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Book;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Seed data for development and test environments.
 *
 * A small, hand-picked set is intentionally used (instead of random Faker
 * output) so that fixtures double as a smoke test of the data model and
 * give realistic responses when exploring the API via Swagger.
 */
final class BookFixtures extends Fixture
{
    private const BOOKS = [
        [
            'title' => 'The Pragmatic Programmer',
            'publisher' => 'Addison-Wesley',
            'author' => 'Andrew Hunt, David Thomas',
            'genre' => 'Software Engineering',
            'publicationDate' => '1999-10-30',
            'wordCount' => 100000,
            'priceUsd' => '39.95',
        ],
        [
            'title' => 'Clean Code',
            'publisher' => 'Prentice Hall',
            'author' => 'Robert C. Martin',
            'genre' => 'Software Engineering',
            'publicationDate' => '2008-08-01',
            'wordCount' => 95000,
            'priceUsd' => '34.99',
        ],
        [
            'title' => 'Domain-Driven Design',
            'publisher' => 'Addison-Wesley',
            'author' => 'Eric Evans',
            'genre' => 'Software Architecture',
            'publicationDate' => '2003-08-22',
            'wordCount' => 180000,
            'priceUsd' => '54.99',
        ],
        [
            'title' => 'Refactoring',
            'publisher' => 'Addison-Wesley',
            'author' => 'Martin Fowler',
            'genre' => 'Software Engineering',
            'publicationDate' => '2018-11-19',
            'wordCount' => 140000,
            'priceUsd' => '47.99',
        ],
        [
            'title' => 'The Phoenix Project',
            'publisher' => 'IT Revolution Press',
            'author' => 'Gene Kim, Kevin Behr, George Spafford',
            'genre' => 'DevOps',
            'publicationDate' => '2013-01-10',
            'wordCount' => 120000,
            'priceUsd' => '24.95',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::BOOKS as $row) {
            $book = new Book(
                title: $row['title'],
                publisher: $row['publisher'],
                author: $row['author'],
                genre: $row['genre'],
                publicationDate: new DateTimeImmutable($row['publicationDate']),
                wordCount: $row['wordCount'],
                priceUsd: $row['priceUsd'],
            );
            $manager->persist($book);
        }

        $manager->flush();
    }
}
