<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema for the Book Library.
 *
 * The migration is written portably so it works on both PostgreSQL (used in
 * docker-compose) and SQLite (used by the test suite). Platform-specific SQL
 * is gated by `$this->connection->getDatabasePlatform()` checks.
 */
final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create books table with all required fields and indexes.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(<<<'SQL'
                CREATE TABLE books (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    publisher VARCHAR(255) NOT NULL,
                    author VARCHAR(255) NOT NULL,
                    genre VARCHAR(100) NOT NULL,
                    publication_date DATE NOT NULL,
                    word_count INTEGER NOT NULL,
                    price_usd NUMERIC(10, 2) NOT NULL,
                    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
                )
            SQL);
            $this->addSql('CREATE INDEX idx_books_author ON books (author)');
            $this->addSql('CREATE INDEX idx_books_genre ON books (genre)');
            $this->addSql("COMMENT ON COLUMN books.publication_date IS '(DC2Type:date_immutable)'");
            $this->addSql("COMMENT ON COLUMN books.created_at IS '(DC2Type:datetime_immutable)'");
            $this->addSql("COMMENT ON COLUMN books.updated_at IS '(DC2Type:datetime_immutable)'");

            return;
        }

        if ($platform instanceof SqlitePlatform) {
            $this->addSql(<<<'SQL'
                CREATE TABLE books (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    publisher VARCHAR(255) NOT NULL,
                    author VARCHAR(255) NOT NULL,
                    genre VARCHAR(100) NOT NULL,
                    publication_date DATE NOT NULL --(DC2Type:date_immutable)
                    ,
                    word_count INTEGER NOT NULL,
                    price_usd NUMERIC(10, 2) NOT NULL,
                    created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                    ,
                    updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                )
            SQL);
            $this->addSql('CREATE INDEX idx_books_author ON books (author)');
            $this->addSql('CREATE INDEX idx_books_genre ON books (genre)');

            return;
        }

        // Generic fallback - MySQL / others.
        $this->addSql(<<<'SQL'
            CREATE TABLE books (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                publisher VARCHAR(255) NOT NULL,
                author VARCHAR(255) NOT NULL,
                genre VARCHAR(100) NOT NULL,
                publication_date DATE NOT NULL,
                word_count INT NOT NULL,
                price_usd NUMERIC(10, 2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_books_author (author),
                INDEX idx_books_genre (genre),
                PRIMARY KEY (id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE books');
    }
}
