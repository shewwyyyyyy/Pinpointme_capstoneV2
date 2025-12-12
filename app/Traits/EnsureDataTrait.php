<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

trait EnsureDataTrait
{
    /**
     * Ensure that the given value is a valid Eloquent model instance.
     *
     * @param mixed $data The value to check.
     * @param string|null $fallbackMessage The message to use if validation fails.
     *
     * @throws RuntimeException If the value is null or not an instance of Model.
     */
    protected function ensureModel(mixed $data, ?string $fallbackMessage = null): void
    {
        if ($data === null || !$data instanceof Model) {
            $message = $fallbackMessage ?? 'Not a valid model';

            throw new RuntimeException($message);
        }
    }

    /**
     * Ensure that the given value is a valid array.
     *
     * @param mixed $data The value to check.
     * @param string|null $fallbackMessage The message to use if validation fails.
     *
     * @throws RuntimeException If the value is not an array.
     */
    protected function ensureArray(mixed $data, ?string $fallbackMessage = null): void
    {
        if (!is_array($data)) {
            $message = $fallbackMessage ?? 'Not a valid array';

            throw new RuntimeException($message);
        }
    }

    /**
     * Ensure that the given value is a valid collection.
     *
     * @param mixed $data The value to check.
     * @param string|null $fallbackMessage The message to use if validation fails.
     *
     * @throws RuntimeException If the value is not a collection.
     */
    protected function ensureCollection(mixed $data, ?string $fallbackMessage = null): void
    {
        if (!($data instanceof Collection)) {
            $message = $fallbackMessage ?? 'Not a valid collection';

            throw new RuntimeException($message);
        }
    }

    /**
     * Ensure that the given value is a valid Eloquent collection.
     *
     * @param mixed $data The value to check.
     * @param string|null $fallbackMessage The message to use if validation fails.
     *
     * @throws RuntimeException If the value is not a valid Eloquent collection.
     */
    protected function ensureEloquentCollection(mixed $data, ?string $fallbackMessage = null): void
    {
        if (!($data instanceof EloquentCollection) || !$data->every(fn($item) => $item instanceof Model)) {
            $message = $fallbackMessage ?? 'Not a valid Eloquent collection';

            throw new RuntimeException($message);
        }
    }
}
