<?php

namespace App\Providers;

use App\Interfaces\AuthInterface;
use App\Interfaces\CurrentUserInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\EmployeeInterface;
use App\Interfaces\Fetches\EmployeeFetchInterface;

// Building Interfaces
use App\Interfaces\BuildingInterface;
use App\Interfaces\Fetches\BuildingFetchInterface;

// Floor Interfaces
use App\Interfaces\FloorInterface;
use App\Interfaces\Fetches\FloorFetchInterface;

// Room Interfaces
use App\Interfaces\RoomInterface;
use App\Interfaces\Fetches\RoomFetchInterface;

// Rescue Request Interfaces
use App\Interfaces\RescueRequestInterface;
use App\Interfaces\Fetches\RescueRequestFetchInterface;

// Conversation Interfaces
use App\Interfaces\ConversationInterface;
use App\Interfaces\Fetches\ConversationFetchInterface;

// Message Interfaces
use App\Interfaces\MessageInterface;
use App\Interfaces\Fetches\MessageFetchInterface;

// Audit Trail Interfaces
use App\Interfaces\AuditTrailInterface;
use App\Interfaces\Fetches\AuditTrailFetchInterface;

use App\Services\AuthService;
use App\Services\CurrentUserService;
use App\Services\UserService;
use App\Services\EmployeeService;
use App\Services\Fetches\EmployeeFetchService;

// Building Services
use App\Services\BuildingService;
use App\Services\Fetches\BuildingFetchService;

// Floor Services
use App\Services\FloorService;
use App\Services\Fetches\FloorFetchService;

// Room Services
use App\Services\RoomService;
use App\Services\Fetches\RoomFetchService;

// Rescue Request Services
use App\Services\RescueRequestService;
use App\Services\Fetches\RescueRequestFetchService;

// Conversation Services
use App\Services\ConversationService;
use App\Services\Fetches\ConversationFetchService;

// Message Services
use App\Services\MessageService;
use App\Services\Fetches\MessageFetchService;

// Audit Trail Services
use App\Services\AuditTrailService;
use App\Services\Fetches\AuditTrailFetchService;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // User
        $this->app->bind(UserInterface::class, UserService::class);
        $this->app->bind(AuthInterface::class, AuthService::class);
        $this->app->bind(CurrentUserInterface::class, CurrentUserService::class);

        // Employee
        $this->app->bind(EmployeeInterface::class, EmployeeService::class);
        $this->app->bind(EmployeeFetchInterface::class, EmployeeFetchService::class);

        // Building
        $this->app->bind(BuildingInterface::class, BuildingService::class);
        $this->app->bind(BuildingFetchInterface::class, BuildingFetchService::class);

        // Floor
        $this->app->bind(FloorInterface::class, FloorService::class);
        $this->app->bind(FloorFetchInterface::class, FloorFetchService::class);

        // Room
        $this->app->bind(RoomInterface::class, RoomService::class);
        $this->app->bind(RoomFetchInterface::class, RoomFetchService::class);

        // Rescue Request
        $this->app->bind(RescueRequestInterface::class, RescueRequestService::class);
        $this->app->bind(RescueRequestFetchInterface::class, RescueRequestFetchService::class);

        // Conversation
        $this->app->bind(ConversationInterface::class, ConversationService::class);
        $this->app->bind(ConversationFetchInterface::class, ConversationFetchService::class);

        // Message
        $this->app->bind(MessageInterface::class, MessageService::class);
        $this->app->bind(MessageFetchInterface::class, MessageFetchService::class);

        // Audit Trail
        $this->app->bind(AuditTrailInterface::class, AuditTrailService::class);
        $this->app->bind(AuditTrailFetchInterface::class, AuditTrailFetchService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
