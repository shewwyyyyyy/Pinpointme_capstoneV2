<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\UserInterface;
use App\Models\User;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class UserService implements UserInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeUser(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = User::create([
                    'username' => $data['username'] ?? null,
                    'email' => $data['email'] ?? null,
                    'password' => bcrypt($data['password'] ?? 'password'),
                    'isAdmin' => $data['isAdmin'] ?? false,
                    'is_able_to_login' => $data['is_able_to_login'] ?? false
                ]);

                return $this->returnModel(201, Helper::SUCCESS, 'User created successfully!', $user, $user->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateUser($userId, array $data): array
    {
        try {
            return DB::transaction(function () use ($userId, $data) {
                $user = User::findOrFail($userId);

                $user = tap($user)->update([
                    'username' => $data['username'] ?? $user->username,
                    'email' => $data['email'] ?? $user->email,
                    'isAdmin' => $data['isAdmin'] ?? $user->isAdmin,
                    'is_able_to_login' => $data['is_able_to_login'] ?? $user->is_able_to_login,
                ]);

                return $this->returnModel(200, Helper::SUCCESS, 'User updated successfully!', $user, $user->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteUser($userId): array
    {
        try {
            return DB::transaction(function () use ($userId) {
                $user = User::findOrFail($userId);
                $user->delete();

                return $this->returnModel(200, Helper::SUCCESS, 'User deleted successfully!', null, $userId);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }
}
