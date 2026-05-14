<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BookNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Book with id "%d" was not found.', $id));
    }
}
