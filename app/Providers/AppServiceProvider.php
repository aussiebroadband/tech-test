<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Nbn\NbnClientInterface;
use App\Services\Nbn\MockNbnClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(NbnClientInterface::class, function () {
            if (app()->environment(['local', 'testing'])) {
                $mode = env('NBN_B2B_MOCK_RESPONSE', 'success');
                return new MockNbnClient($mode);
            }

            //return actual http nbn client in the future implementation
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
     
    }
}
