<?php

namespace App\Http\Controllers;

use App\DTOs\ChangePasswordDTO;
use App\Helpers\ErrorHelper;
use App\Helpers\Helper;
use App\Http\Requests\LoginFormRequest;
use App\Http\Requests\System\ChangePasswordFormRequest;
use App\Interfaces\ActivityLogInterface;
use App\Interfaces\AuthInterface;
use App\Interfaces\CurrentUserInterface;
use App\Interfaces\ManageAccountInterface;
use App\Traits\ActivityLoggerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{

    public function __construct(
        private AuthInterface $auth,
        private CurrentUserInterface $currentUser,
        // private ManageAccountInterface $manageAccount,
        // private ActivityLogInterface $activityLog,
    ) {}

    /**
     * show login page
     */
    public function index()
    {
        return Inertia::render('Login');
    }

    /**
     * attempt login
     */
    public function login(LoginFormRequest $request)
    {
        $result = $this->auth->login($request->toArray());
        [
            'code'    => $code,
            'message' => $message
        ] = $result;

        if ($code == 422) {
            return back()->withErrors([
                'error' => $message,
            ]);
        }

        if ($code == 403) {
            return back()->withErrors([
                'error' => $message,
            ]);
        }

        $request->except(['_token', 'password']);

        return redirect()->intended();
    }

    /**
     * attempt logout
     */
    public function logout()
    {
        $profileId = $this->currentUser->getProfileId();
        $request = new Request();
        $request->merge([
            'user_id' => $this->currentUser->getUserId(),
            'profile_id' => $profileId
        ]);

        $result = $this->auth->logout();

        if ($result['code'] != 200) {
            return back()->withErrors([
                'error' => $result['message'],
            ]);
        }

        // $this->logActivity($result, $request, 'auth', 'logout', $profileId);

        return redirect()->route('login');
    }

    /**
     * Change the user's password.
     */
    // public function changePassword(ChangePasswordFormRequest $request)
    // {
    //     // Prefer using request->filled() over exists() for clarity
    //     $profileId = $request->filled('profile_id')
    //         ? $request->input('profile_id')
    //         : Auth::user()->profile->id;

    //     // Merge the resolved profile_id back into the request so DTO sees it
    //     $request->merge(['profile_id' => $profileId]);

    //     // Build the DTO from the request
    //     $changePasswordDTO = ChangePasswordDTO::fromArray($request->toArray());

    //     // Call service
    //     [
    //         'code'    => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->changeUserProfilePassword($changePasswordDTO, $profileId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    /**
     * Reset the user's password.
     */
    // public function resetPassword(int $userId)
    // {
    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->resetPassword($userId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    /**
     * Set the user's active status.
     */
    // public function setUserStatus(int $userId)
    // {
    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->setUserActiveStatus($userId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    // public function changeProperty(int $propertyId)
    // {

    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->changeProperty($propertyId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }
}
