<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\AuditTrailInterface;
use App\Models\AuditTrail;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class AuditTrailService implements AuditTrailInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeAuditTrail(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $auditTrail = AuditTrail::create([
                    'action' => $data['action'] ?? null,
                    'initiator' => $data['initiator'] ?? null,
                    'initiator_role' => $data['initiator_role'] ?? null,
                    'account_updated' => $data['account_updated'] ?? null,
                    'details' => $data['details'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Audit trail recorded successfully!', $auditTrail, $auditTrail->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteAuditTrail($auditTrailId): array
    {
        try {
            return DB::transaction(function () use ($auditTrailId) {
                $auditTrail = AuditTrail::findOrFail($auditTrailId);
                $auditTrail->delete();

                return $this->returnModel(200, 'success', 'Audit trail deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Record audit trail - static helper method.
     */
    public static function recordAudit(
        string $action,
        string $initiator,
        ?string $initiatorRole,
        string $accountUpdated,
        string $details = ''
    ): void {
        AuditTrail::create([
            'action' => $action,
            'initiator' => $initiator,
            'initiator_role' => $initiatorRole,
            'account_updated' => $accountUpdated,
            'details' => $details,
        ]);
    }
}
