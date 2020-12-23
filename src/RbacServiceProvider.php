<?php

namespace Mchuluq\Larv\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Collection;

class RbacServiceProvider extends ServiceProvider{

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'rbac');
        $this->app->make('Mchuluq\Larv\Rbac\Controllers\AccountController');

        // register macros
        Collection::make(glob(__DIR__ . '/Macros/*.php'))->mapWithKeys(function ($path) {
            return [$path => pathinfo($path, PATHINFO_FILENAME)];
        })->each(function ($macro, $path) {
            require_once $path;
        });
    }

    public function boot(){
        // register rbac-web for web guard
        Auth::extend('rbac-web', function ($app, $name, array $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
            $guard = new \Mchuluq\Larv\Rbac\Guards\SessionGuard($name, $provider, $app['session.store'], request(), $config['expire'] ?? null);
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

        // provide user provider
        Auth::provider('rbac-user', function ($app, array $config) {
            return new \Mchuluq\Larv\Rbac\UserProvider($app['hash'], $config['model']);
        });

        // load migration and command
        if ($this->app->runningInConsole()) {
            include_once __DIR__ . '/../consoles/UserCommand.php';
            include_once __DIR__ . '/../consoles/OtpCommand.php';

            $this->commands([
                \Mchuluq\Larv\Rbac\Consoles\UserCommand::class,
                \Mchuluq\Larv\Rbac\Consoles\OtpCommand::class,
            ]);
        }

        // package publishes
        $this->publishes([
            // Config
            __DIR__ . '/../config/config.php' => config_path('rbac.php'),
            // migration
            __DIR__ . '/../database/migrations/create_rbac_tables.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_rbac_tables.php'),
        ], 'larv-rbac');

        // package routes
        if (config('rbac.route') == true) {
            require __DIR__ . '/Routes.php';
        }
    }
}
