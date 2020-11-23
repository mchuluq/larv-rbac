<?php

namespace Mchuluq\Larv\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class RbacServiceProvider extends ServiceProvider{

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'rbac');
        $this->app->make('Mchuluq\Larv\Rbac\Controllers\AccountController');
    }

    public function boot(){
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // register rbac-web for web guard
        Auth::extend('rbac-web', function ($app, $name, array $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
            $guard = new \Mchuluq\Larv\Rbac\SessionGuard($name, $provider, $app['session.store'], request(), $config['expire'] ?? null);
            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }
            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }
            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            }
            return $guard;
        });

        // // register rbac-token for api token guard
        // Auth::extend('rbac-token', function ($app, $name, array $config) {
        //     $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
        //     $request = app('request');
        //     return new TokenApiGuard($provider, $request, $config);
        // });

        // provide user provider
        Auth::provider('rbac-user', function ($app, array $config) {
            return new \Mchuluq\Larv\Rbac\UserProvider($app['hash'], $config['model']);
        });

        // // load migration and command
        // if ($this->app->runningInConsole()) {
        //     $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        //     include_once __DIR__ . '/../console/GroupCommand.php';
        //     include_once __DIR__ . '/../console/RoleCommand.php';
        //     include_once __DIR__ . '/../console/UserCommand.php';
        //     include_once __DIR__ . '/../console/rbacCommand.php';

        //     $this->commands([
        //         Console\GroupCommand::class,
        //         Console\RoleCommand::class,
        //         Console\UserCommand::class,
        //         Console\rbacCommand::class
        //     ]);
        // }

        // $this->publishes([
        //     // Config
        //     __DIR__ . '/../config/config.php' => config_path('rbac.php'),

        //     // Fields
        //     __DIR__ . '/../fields/groups.php' => app_path('Fields/groups.php'),
        //     __DIR__ . '/../fields/roles.php' => app_path('Fields/roles.php'),
        //     __DIR__ . '/../fields/users.php' => app_path('Fields/users.php'),
        // ], 'larv-rbac');

        if (config('rbac.route') == true) {
            require __DIR__ . '/Routes.php';
        }
    }
}
