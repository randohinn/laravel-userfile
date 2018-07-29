<?php

namespace Randohinn\Userfile;

use Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class PackageServiceProvider extends ServiceProvider
{

    public function boot()
    {
        Auth::provider('userfiles', function($app, array $config) {
            return new UserProvider();
        });

         $this->publishes([
            __DIR__.'/../config/userfile.php' => config_path('userfile.php'),
        ]);
    }

    public function register() {
        app()->config["auth.guards.userfile"] = [
            'driver' => 'session',
            'provider' => 'userfiles',
        ];

        app()->config["auth.providers.userfiles"] = [
            'driver' => 'userfiles',
            'model' => App\User::class,
        ];

        app()->config["filesystems.disks.userfile"] = [
            'driver' => 'local',
            'root' => storage_path('userfile'),
        ];

    }
}
