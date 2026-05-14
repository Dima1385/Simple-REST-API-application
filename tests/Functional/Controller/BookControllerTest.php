<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Book;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end tests for the BookController.
 *
 * Each test boots a fresh kernel against an in-memory SQLite database and
 * rebuilds the schema from the Doctrine metadata - this keeps tests fast,
 * fully isolated, and free of external service dependencies.
 */
final class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;

        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    public function testListReturnsEmptyEnvelopeInitially(): void
    {
        $this->client->request('GET', '/api/books');

        self::assertResponseIsSuccessful();
        $body = $this->json();

        self::assertSame([], $body['items']);
        self::assertSame(1, $body['page']);
        self::assertSame(20, $body['perPage']);
        self::assertSame(0, $body['total']);
    }

    public function testCreateBookReturns201AndPersists(): void
    {
        $payload = [
            'title' => 'Refactoring',
            'publisher' => 'Addison-Wesley',
            'author' => 'Martin Fowler',
            'genre' => 'Software Engineering',
            'publicationDate' => '2018-11-19',
            'wordCount' => 140000,
            'priceUsd' => 47.99,
        ];

        $this->client->request(
            'POST',
            '/api/books',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $body = $this->json();

        self::assertSame('Refactoring', $body['title']);
        self::assertSame('Martin Fowler', $body['author']);
        self::assertSame('2018-11-19', $body['publicationDate']);
        self::assertSame(140000, $body['wordCount']);
        self::assertSame(47.99, $body['priceUsd']);
        self::assertIsInt($body['id']);

        $fromDb = $this->em->getRepository(Book::class)->find($body['id']);
        self::assertNotNull($fromDb);
        self::assertSame('Refactoring', $fromDb->getTitle());
    }

    public function testCreateBookValidatesPayload(): void
    {
        $this->client->request(
            'POST',
            '/api/books',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                // title intentionally missing
                'publisher' => 'X',
                'author' => 'Y',
                'genre' => 'Z',
                'publicationDate' => 'not-a-date',
                'wordCount' => -5,
                'priceUsd' => -1,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
        $body = $this->json();

        self::assertSame('Validation failed', $body['title']);
        self::assertIsArray($body['violations']);
        $properties = array_column($body['violations'], 'property');
        self::assertContains('title', $properties);
        self::assertContains('publicationDate', $properties);
        self::assertContains('wordCount', $properties);
        self::assertContains('priceUsd', $properties);
    }

    public function testGetReturnsBook(): void
    {
        $book = $this->seedBook();

        $this->client->request('GET', '/api/books/'.$book->getId());

        self::assertResponseIsSuccessful();
        $body = $this->json();
        self::assertSame($book->getId(), $body['id']);
        self::assertSame('Clean Code', $body['title']);
    }

    public function testGetReturns404ForUnknownId(): void
    {
        $this->client->request('GET', '/api/books/999999');

        self::assertResponseStatusCodeSame(404);
        $body = $this->json();
        self::assertSame(404, $body['status']);
    }

    public function testPatchUpdatesOnlyProvidedFields(): void
    {
        $book = $this->seedBook();

        $this->client->request(
            'PATCH',
            '/api/books/'.$book->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['priceUsd' => 29.99], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $body = $this->json();

        self::assertSame(29.99, $body['priceUsd']);
        self::assertSame('Clean Code', $body['title']);

        $this->em->clear();
        $fromDb = $this->em->getRepository(Book::class)->find($book->getId());
        self::assertSame('29.99', $fromDb->getPriceUsd());
    }

    public function testDeleteRemovesBook(): void
    {
        $book = $this->seedBook();
        $id = $book->getId();

        $this->client->request('DELETE', '/api/books/'.$id);

        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        self::assertNull($this->em->getRepository(Book::class)->find($id));
    }

    public function testDeleteReturns404WhenMissing(): void
    {
        $this->client->request('DELETE', '/api/books/999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testListRespectsPagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->seedBook(title: "Book #$i");
        }

        $this->client->request('GET', '/api/books?page=1&perPage=2');
        self::assertResponseIsSuccessful();

        $body = $this->json();
        self::assertCount(2, $body['items']);
        self::assertSame(5, $body['total']);
        self::assertSame(2, $body['perPage']);
    }

    public function testHealthEndpoint(): void
    {
        $this->client->request('GET', '/api/health');
        self::assertResponseIsSuccessful();
        self::assertSame(['status' => 'ok'], $this->json());
    }

    private function seedBook(string $title = 'Clean Code'): Book
    {
        $book = new Book(
            title: $title,
            publisher: 'Prentice Hall',
            author: 'Robert C. Martin',
            genre: 'Software Engineering',
            publicationDate: new DateTimeImmutable('2008-08-01'),
            wordCount: 95000,
            priceUsd: '34.99',
        );

        $this->em->persist($book);
        $this->em->flush();
        $this->em->clear();

        return $this->em->getRepository(Book::class)->find($book->getId());
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
