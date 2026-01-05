<?php namespace Mchuluq\Larv\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

class RbacServiceProvider extends ServiceProvider{

    public function boot(){
        // register macros
        $this->registerMacros();
        
        // consoles
        $this->registerConsoles();

        // publish
        $this->publishMigrations();
        $this->publishConfig();
        $this->publishResources();
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rbac');

        // register routes
        $this->configureRoutes();
    }

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'rbac');
        // $this->app->make('Mchuluq\Larv\Rbac\Http\Controllers\AccountController');

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
        // $router->aliasMiddleware('rbac-auth', \Mchuluq\Larv\Rbac\Http\Middlewares\Authenticate::class);
        $router->aliasMiddleware('rbac-link-session', \Mchuluq\Larv\Rbac\Http\Middlewares\LinkSessionToDeviceToken::class);
        $router->aliasMiddleware('rbac-check-account', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckAccount::class);
        $router->aliasMiddleware('rbac-check-permission', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckPermission::class);
    }

    protected function configureRoutes(){
        // package routes
        if (config('rbac.route') == true) {
            require __DIR__ . '/Routes.php';
        }
    }
    protected function publishMigrations(){
        $migrations = array_merge(
            glob(__DIR__ . '/../resources/migrations/*.php') ?: [],
            glob(__DIR__ . '/../resources/migrations/*.php.stub') ?: []
        );

        $publishable = [];
        $time = time();

        foreach ($migrations as $index => $path) {
            $filename = basename($path);

            // Remove any existing timestamp prefix from package filename (e.g., 2025_12_30_123456_)
            $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);

            // If file ends with .stub (e.g., create_x_table.php.stub), drop the .stub so target ends with .php
            if (substr($name, -5) === '.stub') {
                $name = substr($name, 0, -5);
            }

            // Ensure the name ends with .php
            if (substr($name, -4) !== '.php') {
                $name .= '.php';
            }

            // Skip if migration already exists in the application's migrations
            if (count(glob(database_path("migrations/*_{$name}"))) > 0) {
                continue;
            }

            // Ensure unique timestamp per file by incrementing seconds for each entry
            $timestamp = date('Y_m_d_His', $time + $index);
            $publishable[$path] = database_path("migrations/{$timestamp}_{$name}");
        }

        if (!empty($publishable)) {
            $this->publishes($publishable, 'rbac-migrations');
        }
    }
    protected function publishConfig(){
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('rbac.php')
        ], 'rbac-config');        
    }
    protected function publishResources(){
        $this->publishes([
            __DIR__.'/../resources/lang' => base_path('resources/lang/vendor/rbac'),
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/rbac'),
        ],'rbac-view');
    }
    protected function registerMacros(){
        Collection::make(glob(__DIR__ . '/Macros/*.php'))->mapWithKeys(function ($path) {
            return [$path => pathinfo($path, PATHINFO_FILENAME)];
        })->each(function ($macro, $path) {
            require_once $path;
        });

    }
    protected function registerConsoles(){
        // load command
        if ($this->app->runningInConsole()) {
            include_once __DIR__ . '/../consoles/CleanupExpiredTokens.php';
            $this->commands([
                \Mchuluq\Larv\Rbac\Commands\CleanupExpiredTokens::class
            ]);
        }
    }

}
