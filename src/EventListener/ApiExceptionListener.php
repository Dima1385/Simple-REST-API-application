<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Normalises every exception thrown beneath `/api/*` into a consistent
 * JSON envelope, so clients never see HTML error pages or leaked stack
 * traces. Validation errors get a dedicated, machine-readable shape.
 */
#[AsEventListener(event: 'kernel.exception', priority: 64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();
        $previous = $throwable->getPrevious();

        // Symfony wraps DTO validation errors in HttpException-like exceptions,
        // so we look at the previous exception to recover the original
        // ValidationFailedException and project its violations.
        $validationFailure = $throwable instanceof ValidationFailedException
            ? $throwable
            : ($previous instanceof ValidationFailedException ? $previous : null);

        if ($validationFailure !== null) {
            $violations = [];
            foreach ($validationFailure->getViolations() as $violation) {
                $violations[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'title' => 'Validation failed',
                'detail' => 'One or more fields are invalid.',
                'violations' => $violations,
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'status' => $throwable->getStatusCode(),
                'title' => Response::$statusTexts[$throwable->getStatusCode()] ?? 'Error',
                'detail' => $throwable->getMessage(),
            ], $throwable->getStatusCode(), $throwable->getHeaders()));

            return;
        }

        // Fall through: unexpected exceptions become a generic 500 in production
        // but keep the original message in dev/test so we don't hide bugs.
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $isDebug = $request->server->get('APP_DEBUG') === '1' || $request->server->get('APP_ENV') !== 'prod';

        $event->setResponse(new JsonResponse([
            'status' => $status,
            'title' => 'Internal Server Error',
            'detail' => $isDebug ? $throwable->getMessage() : 'An unexpected error occurred.',
        ], $status));
    }
}
