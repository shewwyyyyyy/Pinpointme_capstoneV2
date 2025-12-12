<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\CurrentUserInterface;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use App\Interfaces\BaseInterface;
use App\Interfaces\FetchInterfaces\BaseFetchInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CurrentUserService implements CurrentUserInterface
{
    use HttpErrorCodeTrait;
    use ReturnModelCollectionTrait;
    use ReturnModelTrait;

    private ?User $cachedUser = null;

    /**
     * get the authenticated user's profile ID.
     *
     * @return integer
     */
    public function getProfileId(): ?int
    {
        $user = $this->getUser();

        if (app()->environment('local')) {
            // If no user or profile in local, return fallback
            if (!$user || !$user->profile?->id) {
                // return 1;
            }
        }

        // For non-local environments, fail if missing
        if (!$user || !$user->profile?->id) {
            throw new RuntimeException('Authenticated user or profile not found.');
        }

        return $user->profile->id;
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return integer
     */
    public function getUserId(): ?int
    {
        $user = $this->getUser();

        if (app()->environment('local')) {
            // If no user or profile in local, return fallback
            if (!$user || !$user->id) {
                // return 1;
            }
        }

        // For non-local environments, fail if missing
        if (!$user || !$user->id) {
            throw new RuntimeException('Authenticated user or profile not found.');
        }

        return $user->id;
    }

    /**
     * Get the current property ID for the authenticated user.
     *
     * @return integer
     */
    public function getCurrentPropertyId(): ?int
    {
        $user = $this->getUser();

        if (app()->environment('local')) {
            // If no user or profile in local, return fallback
            if (!$user || !$user->profile?->current_property_id) {
                return 1; // APZ
            }
        }

        // For non-local environments, fail if missing
        if (!$user || !$user->profile?->current_property_id) {
            throw new RuntimeException('No property found for the authenticated user.');
        }

        return $user->profile->current_property_id;
    }


    #methods start
    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    private function getUser(): User|null
    {
        if ($this->cachedUser === null) {
            $this->cachedUser = Auth::user();
        }

        return $this->cachedUser;
    }
    #methods end
}
