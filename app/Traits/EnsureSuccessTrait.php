<?php

namespace App\Traits;

use RuntimeException;
use App\Helpers\Helper;

/**
 * Trait EnsureSuccessTrait
 *
 * Provides a method to ensure that a response indicates success, throwing an exception if it does not.
 */
trait EnsureSuccessTrait
{
    /**
     * Ensure that the response indicates success.
     *
     * @param array $response The response array to check.
     * @param string $fallbackMessage The message to use if the response indicates an error.
     * @throws RuntimeException If the response indicates an error.
     */
    protected function ensureSuccess(array $response, string $fallbackMessage): void
    {
        if (($response['status'] ?? null) === Helper::ERROR) {
            throw new RuntimeException($response['message'] ?? $fallbackMessage);
        }
    }
}
