<?php

namespace App\Traits;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trait ReturnResultTrait
 *
 * Provides a method to return a standardized API response with a result.
 */
trait ReturnResultTrait
{
    /**
     * Set model result in a standardized response.
     *
     * @param int|null $code
     * @param string|null $status
     * @param string|null $message
     * @param JsonResource|null $result
     * @param int|null $lastId
     * @return array
     */
    public function returnResult(
        ?int $code = null,
        ?string $status = null,
        ?string $message = null,
        ?JsonResource $data = null,
        ?int $lastId = null
    ): array {
        return [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'last_id' => $lastId,
        ];
    }
}
