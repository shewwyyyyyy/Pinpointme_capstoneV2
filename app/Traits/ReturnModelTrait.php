<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait ReturnModelTrait
 *
 * Provides a method to return a standardized API response with a model result.
 */
trait ReturnModelTrait
{
    /**
     * Set model result in a standardized response format.
     *
     * @param int|null $code
     * @param string|null $status
     * @param string|null $message
     * @param Model|null $data
     * @param int|null $lastId
     * @return array
     */
    public function returnModel(
        ?int $code = null,
        ?string $status = null,
        ?string $message = null,
        ?Model $data = null,
        ?int $lastId = null
    ): array {
        return [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'last_id' => $lastId,
            'data' => $data,
        ];
    }
}
