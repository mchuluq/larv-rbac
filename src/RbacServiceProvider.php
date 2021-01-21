<?php

namespace Mchuluq\Larv\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Collection;

class RbacServiceProvider extends ServiceProvider{

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'rbac');
        $this->app->make('Mchuluq\Larv\Rbac\Http\Controllers\AccountController');

        // register macros
        Collection::make(glob(__DIR__ . '/Macros/*.php'))->mapWithKeys(function ($path) {
            return [$path => pathinfo($path, PATHINFO_FILENAME)];
        })->each(function ($macro, $path) {
            require_once $path;
        });

        // register rbac-web-guard for web guard
        Auth::extend('rbac-web-guard', function ($app, $name, array $config) {
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

        // register middlewares
        $this->registerMiddlewares();
    }

    protected function registerMiddlewares(){
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('rbac-auth', \Mchuluq\Larv\Rbac\Http\Middlewares\Authenticate::class);
        $router->aliasMiddleware('rbac-check-group', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckGroup::class);
        $router->aliasMiddleware('rbac-check-permission', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckPermission::class);
        $router->aliasMiddleware('rbac-check-role', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckRole::class);
    }

    public function boot(){       
        // load command
        if ($this->app->runningInConsole()) {
            include_once __DIR__ . '/../consoles/UserCommand.php';
            include_once __DIR__ . '/../consoles/OtpCommand.php';

            $this->commands([
                \Mchuluq\Larv\Rbac\Consoles\UserCommand::class,
                \Mchuluq\Larv\Rbac\Consoles\OtpCommand::class,
            ]);
        }

        $this->configurePublishes();
        $this->configureRoutes();
    }

    protected function configurePublishes(){
        // package publishes
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('rbac.php')
        ], 'config');        
        $this->publishes([
            __DIR__ . '/../resources/migrations/create_rbac_tables.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_rbac_tables.php')
        ], 'migration');
        $this->publishes([
            __DIR__.'/../resources/lang' => base_path('resources/lang/vendor/rbac'),
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/rbac'),
        ],'view');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rbac');
    }

    protected function configureRoutes(){
        // package routes
        if (config('rbac.route') == true) {
            require __DIR__ . '/Routes.php';
        }
    }
}
