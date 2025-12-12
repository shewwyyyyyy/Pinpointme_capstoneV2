<?php

namespace App\Traits;

use BadMethodCallException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * Trait HttpErrorCodeTrait
 *
 * Provides a method to map exceptions to HTTP status codes.
 */
trait HttpErrorCodeTrait
{
    /**
     * Get the HTTP status code based on the exception instance.
     *
     * @param Throwable $exception
     * @return int
     */
    public function httpCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ModelNotFoundException        => 404,
            $exception instanceof AuthorizationException        => 403,
            $exception instanceof ValidationException           => 422,
            $exception instanceof NotFoundHttpException         => 404,
            $exception instanceof MethodNotAllowedHttpException => 405,
            $exception instanceof AuthenticationException       => 401,
            $exception instanceof PostTooLargeException         => 413,
            $exception instanceof TooManyRequestsHttpException  => 429,
            $exception instanceof TokenMismatchException        => 419,
            $exception instanceof HttpExceptionInterface        => $exception->getStatusCode(),
            $exception instanceof RuntimeException              => 500,
            $exception instanceof QueryException                => 500,  // Database query errors
            $exception instanceof FileNotFoundException         => 404,  // File not found errors
            $exception instanceof UnauthorizedHttpException     => 401,  // Unauthorized access
            $exception instanceof BadMethodCallException        => 500,  // Bad method calls
            $exception instanceof ServiceUnavailableHttpException => 503, // Service unavailable
            default => 500,  // Default fallback for unknown exceptions
        };
    }
}
