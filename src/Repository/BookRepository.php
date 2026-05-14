<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(Book $book, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($book);

        if ($flush) {
            $em->flush();
        }
    }

    public function remove(Book $book, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($book);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Returns a paginated list of books ordered by id descending (newest first).
     *
     * @return list<Book>
     */
    public function findPaginated(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        /** @var list<Book> $result */
        $result = $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
