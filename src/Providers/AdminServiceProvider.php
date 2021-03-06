<?php

namespace Kizi\Admin\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Kizi\Admin\Facades\Admin;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $commands = [
        'Kizi\Admin\Commands\MakeCommand',
        'Kizi\Admin\Commands\MenuCommand',
        'Kizi\Admin\Commands\InstallCommand',
        'Kizi\Admin\Commands\UninstallCommand',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'admin.auth'       => \Kizi\Admin\Middleware\Authenticate::class,
        'admin.pjax'       => \Kizi\Admin\Middleware\PjaxMiddleware::class,
        'admin.log'        => \Kizi\Admin\Middleware\OperationLog::class,
        'admin.permission' => \Kizi\Admin\Middleware\PermissionMiddleware::class,
        'admin.bootstrap'  => \Kizi\Admin\Middleware\BootstrapMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'admin' => [
            'admin.auth',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
        ],
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../views', 'admin');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang/', 'admin');

        $this->publishes([__DIR__ . '/../../config/admin.php' => config_path('admin.php')], 'laravel-admin');
        $this->publishes([__DIR__ . '/../../assets' => public_path('packages/admin')], 'laravel-admin');

        Admin::registerAuthRoutes();

        if (file_exists($routes = admin_path('routes.php'))) {
            require $routes;
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();

            $loader->alias('Admin', \Kizi\Admin\Facades\Admin::class);

            if (is_null(config('auth.guards.admin'))) {
                $this->setupAuth();
            }
        });

        $this->registerRouteMiddleware();

        $this->commands($this->commands);
    }

    /**
     * Setup auth configuration.
     *
     * @return void
     */
    protected function setupAuth()
    {
        config([
            'auth.guards.admin.driver'    => 'session',
            'auth.guards.admin.provider'  => 'admin',
            'auth.providers.admin.driver' => 'eloquent',
            'auth.providers.admin.model'  => 'Kizi\Admin\Auth\Database\Administrator',
        ]);
    }

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->middleware($key, $middleware);
        }

        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            app('router')->middlewareGroup($key, $middleware);
        }
    }
}
