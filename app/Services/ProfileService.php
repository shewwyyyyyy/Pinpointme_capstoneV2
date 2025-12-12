<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\ProfileInterface;
use App\Models\Profile;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileService implements ProfileInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    public function __construct(
        private UserService $user,
        private PropertyService $propertyService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function storeProfile(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {

                $userResult = $this->user->storeUser($data);

                if ($userResult['status'] === Helper::ERROR) {
                    throw ValidationException::withMessages([
                        'errors' =>  'Error on creation user: ' . $userResult['message']
                    ]);
                }


                $profile = Profile::create([
                    'first_name' => $data['first_name'] ?? null,
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'unique_identifier' => $data['unique_identifier'] ?? null,
                    'position' => $data['position'] ?? null,
                    'property_id' => $data['property_id'] ?? 1,
                    'department_id' => $data['department_id'] ?? 1,
                    'location_id' => $data['location_id'] ?? 1,
                    'user_id' => $userResult['last_id'] ?? null,
                    'meal_entitlement' => $data['meal_entitlement'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Profile created successfully!', $profile, $profile->id);
            });
        } catch (\Throwable $th) {
             $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateProfile($profileId, array $data): array
    {
        try {
            return DB::transaction(function () use ($profileId, $data) {
                $profile = Profile::findOrFail($profileId);
                
                // Store old values before update for property sync validation
                $oldUniqueIdentifier = $profile->unique_identifier;

                $userResult = $this->user->updateUser($profile->user_id, $data);

                if ($userResult['status'] === Helper::ERROR) {
                    throw ValidationException::withMessages([
                        'errors' =>  'Error on creation user: ' . $userResult['message']
                    ]);
                }

                $profile = tap($profile)->update([
                    'first_name' => $data['first_name'] ?? $profile->first_name,
                    'middle_name' => $data['middle_name'] ?? $profile->middle_name,
                    'last_name' => $data['last_name'] ?? $profile->last_name,
                    'unique_identifier' => $data['unique_identifier'] ?? $profile->unique_identifier,
                    'property_id' => $data['property_id'] ?? $profile->property_id,
                    'department_id' => $data['department_id'] ?? $profile->department_id,
                    'location_id' => $data['location_id'] ?? $profile->location_id,
                    'position' => $data['position'] ?? $profile->position,
                    'meal_entitlement' => $data['meal_entitlement'] ?? $profile->meal_entitlement,
                    'start_date' => $data['start_date'] ?? $profile->start_date,
                    'end_date' => $data['end_date'] ?? $profile->end_date,
                ]);

                // Sync username with property if username was updated
                if (isset($data['username']) && !empty($data['username'])) {
                    $this->propertyService->updatePropertyUsernameFromProfile(
                        $profile->id, 
                        $data['username'],
                        $oldUniqueIdentifier
                    );
                }

                // Sync names with property if first_name or last_name was updated
                if (isset($data['first_name']) || isset($data['last_name'])) {
                    $firstName = $data['first_name'] ?? $profile->first_name;
                    $lastName = $data['last_name'] ?? $profile->last_name;
                    $this->propertyService->updatePropertyNameFromProfile(
                        $profile->id, 
                        $firstName, 
                        $lastName,
                        $oldUniqueIdentifier
                    );
                }

                // Sync unique_identifier with property if unique_identifier was updated
                if (isset($data['unique_identifier'])) {
                    $this->propertyService->updatePropertyUniqueIdentifierFromProfile(
                        $profile->id, 
                        $data['unique_identifier'],
                        $oldUniqueIdentifier
                    );
                }

                return $this->returnModel(200, 'success', 'Profile updated successfully!', $profile, $profile->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteProfile($profileId): array
    {
        try {
            return DB::transaction(function () use ($profileId) {
                $profile = Profile::findOrFail($profileId);
                $profile->delete();

                return $this->returnModel(200, 'success', 'Profile deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
