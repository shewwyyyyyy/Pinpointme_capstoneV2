<?php

namespace App\Services;

use App\Interfaces\AccountInterface;
use App\Interfaces\ProfileInterface;
use App\Interfaces\UserInterface;
use App\Models\Profile;
use App\Traits\EnsureDataTrait;
use App\Traits\EnsureSuccessTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class AccountService implements AccountInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        EnsureSuccessTrait,
        EnsureDataTrait;

    public function __construct(
        private ProfileInterface $profile,
        private UserInterface $user
    ) {}

    /**
     * Store a new user account.
     */
    public function storeAccount(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $userResult = $this->user->storeUser($data);
                $this->ensureSuccess($userResult, 'Failed to create user account');

                $user = $userResult['data'] ?? null;
                $this->ensureModel($user, 'User data is not a valid model instance');

                $profileData = [
                    'first_name' => $data['first_name'] ?? null,
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'unique_identifier' => $data['unique_identifier'] ?? null,
                    'position' => $data['position'] ?? null,
                    'user_id' => $user->id,
                ];

                $profileResult = $this->profile->storeProfile($profileData);
                $this->ensureSuccess($profileResult, 'Failed to create user profile');

                $profile = $profileResult['data'] ?? null;
                $this->ensureModel($profile, 'Profile data is not a valid model instance');

                return $this->returnModel(201, 'success', 'Profile created successfully!', $profile, $profile->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update an existing user account.
     */
    public function updateAccount(array $data, int $profileId): array
    {
        try {
            return DB::transaction(function () use ($data, $profileId) {
                $profileResult = $this->profile->updateProfile($profileId, $data);
                $this->ensureSuccess($profileResult, 'Failed to update user profile');

                $profile = $profileResult['data'] ?? null;
                $this->ensureModel($profile, 'Profile data is not a valid model instance');

                if (isset($data['email']) || isset($data['password'])) {
                    $userData = [];
                    if (isset($data['email'])) {
                        $userData['email'] = $data['email'];
                    }
                    if (isset($data['username'])) {
                        $userData['username'] = $data['username'];
                    }

                    $userResult = $this->user->updateUser($userData, $profile->user_id);
                    $this->ensureSuccess($userResult, 'Failed to update user account');

                    $user = $userResult['data'] ?? null;
                    $this->ensureModel($user, 'User data is not a valid model instance');
                }

                return $this->returnModel(200, 'success', 'Profile updated successfully!', $profile, $profile->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Delete a user account and its associated profile.
     */
    public function deleteAccount(int $profileId): array
    {
        try {
            return DB::transaction(function () use ($profileId) {
                $profile = Profile::findOrFail($profileId);
                $userId = $profile->user_id;

                $deleteProfileResult = $this->profile->deleteProfile($profileId);
                $this->ensureSuccess($deleteProfileResult, 'Failed to delete user profile');

                $deleteUserResult = $this->user->deleteUser($userId);
                $this->ensureSuccess($deleteUserResult, 'Failed to delete user account');

                return $this->returnModel(200, 'success', 'Profile and associated user account deleted successfully!', null, $profileId);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
