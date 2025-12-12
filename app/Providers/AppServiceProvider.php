<?php

namespace App\Providers;

use App\Interfaces\AccountInterface;
use App\Interfaces\AuthInterface;
use App\Interfaces\CurrentUserInterface;
use App\Interfaces\DepartmentInterface;
use App\Interfaces\Fetches\DepartmentFetchInterface;
use App\Interfaces\Fetches\PropertyFetchInterface;
use App\Interfaces\Fetches\ScanHistoryFetchInterface;
use App\Interfaces\Fetches\ScanProfileFetchInterface;
use App\Interfaces\ProfileInterface;
use App\Interfaces\ProfileMealScheduleInterface;
use App\Interfaces\PropertyInterface;
use App\Interfaces\LocationInterface;
use App\Interfaces\Fetches\LocationFetchInterface;
use App\Interfaces\ScanHistoryInterface;
use App\Interfaces\ScanProcessInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\EmployeeInterface;
use App\Interfaces\Fetches\EmployeeFetchInterface;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CurrentUserService;
use App\Services\DepartmentService;
use App\Services\Fetches\DepartmentFetchService;
use App\Services\Fetches\PropertyFetchService;
use App\Services\Fetches\LocationFetchService;
use App\Services\LocationService;
use App\Services\Fetches\ScanHistoryFetchService;
use App\Services\Fetches\ScanProfileFetchService;
use App\Services\ProfileMealScheduleService;
use App\Services\ProfileService;
use App\Services\PropertyService;
use App\Services\ScanHistoryService;
use App\Services\ScanProcessService;
use App\Services\UserService;
use App\Services\EmployeeService;
use App\Services\Fetches\EmployeeFetchService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserInterface::class, UserService::class);
        $this->app->bind(ProfileInterface::class, ProfileService::class);
        $this->app->bind(AccountInterface::class, AccountService::class);

        // property
        $this->app->bind(PropertyInterface::class, PropertyService::class);
        $this->app->bind(PropertyFetchInterface::class, PropertyFetchService::class);

        // department
        $this->app->bind(DepartmentFetchInterface::class, DepartmentFetchService::class);
        $this->app->bind(DepartmentInterface::class, DepartmentService::class);

        // location
        $this->app->bind(LocationInterface::class, LocationService::class);
        $this->app->bind(LocationFetchInterface::class, LocationFetchService::class);

        // scan history
        $this->app->bind(ScanHistoryInterface::class, ScanHistoryService::class);
        $this->app->bind(ScanHistoryFetchInterface::class, ScanHistoryFetchService::class);
        // scan
        $this->app->bind(ScanProcessInterface::class, ScanProcessService::class);

        //scan profile
        $this->app->bind(ScanProfileFetchInterface::class, ScanProfileFetchService::class);

        //profile meal schedule
        $this->app->bind(ProfileMealScheduleInterface::class, ProfileMealScheduleService::class);

        $this->app->bind(AuthInterface::class, AuthService::class);
        $this->app->bind(CurrentUserInterface::class, CurrentUserService::class);

        $this->app->bind(EmployeeInterface::class, EmployeeService::class);
        $this->app->bind(EmployeeFetchInterface::class, EmployeeFetchService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
