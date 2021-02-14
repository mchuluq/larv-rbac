<?php

namespace Mchuluq\Larv\Rbac;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Collection;

use Illuminate\Http\Request;
use Cose\Algorithm\Signature;
use Webauthn\PublicKeyCredentialLoader;
use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

class RbacServiceProvider extends ServiceProvider{

    const WEBAUTHN_COOKIE = 'X-WebAuthn';

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

        // register macros
        Collection::make(glob(__DIR__ . '/Macros/*.php'))->mapWithKeys(function ($path) {
            return [$path => pathinfo($path, PATHINFO_FILENAME)];
        })->each(function ($macro, $path) {
            require_once $path;
        });

        // register macros request
        Request::macro('hasCredential', function () {
            return $this->cookies->has(RbacServiceProvider::WEBAUTHN_COOKIE);
        });

        $this->configurePublishes();
        $this->configureRoutes();
    }

    public function register(){
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'rbac');
        $this->app->make('Mchuluq\Larv\Rbac\Http\Controllers\AccountController');

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

        // register webauth lib
        $this->registerWebauthnLib();
    }

    protected function registerMiddlewares(){
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('rbac-auth', \Mchuluq\Larv\Rbac\Http\Middlewares\Authenticate::class);
        $router->aliasMiddleware('rbac-check-permission', \Mchuluq\Larv\Rbac\Http\Middlewares\CheckPermission::class);
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

    protected function registerWebauthnLib(){
        $this->app->bind(CoseAlgorithmManager::class, static function () {
            return tap(new CoseAlgorithmManager, function ($manager) {
                array_map(fn ($algo) => $manager->add(new $algo), [
                    Signature\ECDSA\ES256::class,
                    Signature\ECDSA\ES512::class,
                    Signature\EdDSA\EdDSA::class,
                    Signature\ECDSA\ES384::class,
                    Signature\EdDSA\Ed25519::class,
                    Signature\RSA\RS1::class,
                    Signature\RSA\RS256::class,
                    Signature\RSA\RS512::class,
                ]);
            });
        });

        $this->app->singleton(AttestationStatementSupportManager::class, static function ($app) {
            return tap(new AttestationStatementSupportManager, function ($attestationStatementSupportManager) use ($app) {
                $attestationStatementSupportManager->add(new NoneAttestationStatementSupport);
                $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($app[CoseAlgorithmManager::class]));
            });
        });

        $this->app->singleton(AttestationObjectLoader::class, static function ($app) {
            return new AttestationObjectLoader(
                $app[AttestationStatementSupportManager::class],
                null,
                $app['log']
            );
        });

        $this->app->singleton(PublicKeyCredentialLoader::class, static function ($app) {
            return new PublicKeyCredentialLoader(
                $app[AttestationObjectLoader::class],
                $app['log']
            );
        });

        $this->app->bind(AuthenticatorAttestationResponseValidator::class, static function ($app) {
            return new AuthenticatorAttestationResponseValidator(
                $app[AttestationStatementSupportManager::class],
                new CredentialSource,
                new IgnoreTokenBindingHandler,
                new ExtensionOutputCheckerHandler,
                null,
                $app['log']
            );
        });

        $this->app->bind(AuthenticatorAssertionResponseValidator::class, static function ($app) {
            return new AuthenticatorAssertionResponseValidator(
                new CredentialSource,
                new IgnoreTokenBindingHandler,
                new ExtensionOutputCheckerHandler,
                $app[CoseAlgorithmManager::class],
                null,
                $app['log']
            );
        });
    }
}
