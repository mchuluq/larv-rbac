<?php

namespace Mchuluq\Laravel\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

class RbacServiceProvider extends ServiceProvider{

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'uac');
        $this->app->make('Mchuluq\Laravel\Uac\Auth\AccountController');
    }

    public function boot(){
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // // register uac-web for web guard
        // Auth::extend('uac-web', function ($app, $name, array $config) {
        //     $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
        //     $guard = new SessionGuard($name, $provider, $app['session.store'], request(), $config['expire'] ?? null);
        //     if (method_exists($guard, 'setCookieJar')) {
        //         $guard->setCookieJar($app['cookie']);
        //     }
        //     if (method_exists($guard, 'setDispatcher')) {
        //         $guard->setDispatcher($app['events']);
        //     }
        //     if (method_exists($guard, 'setRequest')) {
        //         $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
        //     }
        //     return $guard;
        // });

        // // register uac-token for api token guard
        // Auth::extend('uac-token', function ($app, $name, array $config) {
        //     $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
        //     $request = app('request');
        //     return new TokenApiGuard($provider, $request, $config);
        // });

        // // provide user provider
        // Auth::provider('uac-user', function ($app, array $config) {
        //     return new UacUserProvider($app['hash'], $config['model']);
        // });

        // // load migration and command
        // if ($this->app->runningInConsole()) {
        //     $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        //     include_once __DIR__ . '/../console/GroupCommand.php';
        //     include_once __DIR__ . '/../console/RoleCommand.php';
        //     include_once __DIR__ . '/../console/UserCommand.php';
        //     include_once __DIR__ . '/../console/UacCommand.php';

        //     $this->commands([
        //         Console\GroupCommand::class,
        //         Console\RoleCommand::class,
        //         Console\UserCommand::class,
        //         Console\UacCommand::class
        //     ]);
        // }

        // $this->publishes([
        //     // Config
        //     __DIR__ . '/../config/config.php' => config_path('uac.php'),

        //     // Fields
        //     __DIR__ . '/../fields/groups.php' => app_path('Fields/groups.php'),
        //     __DIR__ . '/../fields/roles.php' => app_path('Fields/roles.php'),
        //     __DIR__ . '/../fields/users.php' => app_path('Fields/users.php'),
        // ], 'larv-uac');

        // if (config('uac.route') == true) {
        //     require __DIR__ . '/UacRoutes.php';
        // }
    }
}
