<?php

namespace App\Traits;

/**
 * Trait TrimsInputTrait
 *
 * Provides a method to recursively trim whitespace from all string values in an input array.
 */
trait TrimsInputTrait
{
    /**
     * Recursively trim strings in the input array.
     *
     * @param array $data
     * @return array
     */
    protected function trimInputs(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }

            if (is_array($value)) {
                return $this->trimInputs($value); // Handle nested arrays
            }

            return $value;
        }, $data);
    }
}
