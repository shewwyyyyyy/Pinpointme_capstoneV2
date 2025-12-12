<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait ReturnModelCollectionTrait
 *
 * Provides a method to return a standardized API response with model collection or paginated data.
 */
trait ReturnModelCollectionTrait
{
    /**
     * Return a standardized API response with model collection or paginated data.
     *
     * @param int|null $code HTTP status code (e.g., 200 for success).
     * @param string|null $status Status string ('success' or 'error').
     * @param string|null $message Descriptive success or error message.
     * @param EloquentCollection|LengthAwarePaginator|null $data The data to return (Eloquent collection or paginated).
     *
     * @return array{
     *     code: int|null,
     *     status: string|null,
     *     message: string|null,
     *     data: EloquentCollection|LengthAwarePaginator|null
     * }
     */
    public function returnModelCollection(
        ?int $code = null,
        ?string $status = null,
        ?string $message = null,
        EloquentCollection|LengthAwarePaginator|null $data = null
    ): array {
        return [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }
}
