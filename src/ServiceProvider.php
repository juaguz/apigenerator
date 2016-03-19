<?php namespace JuaGuz\ApiGenerator;

use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;

use ApiGenerator\Console\GenerateApi;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        #$configPath = __DIR__ . '/../config/debugbar.php';
        #$this->mergeConfigFrom($configPath, 'debugbar');
        
        #$this->app->alias();
        
        /*$this->app->singleton('debugbar', function ($app) {
                $debugbar = new LaravelDebugbar($app);

                if ($app->bound(SessionManager::class)) {
                    $sessionManager = $app->make(SessionManager::class);
                    $httpDriver = new SymfonyHttpDriver($sessionManager);
                    $debugbar->setHttpDriver($httpDriver);
                }

                return $debugbar;
            }
        );*/
        
        #$this->app->alias('debugbar', 'Barryvdh\Debugbar\LaravelDebugbar');

        $this->app['command.api.generate'] = $this->app->share(
            function ($app) {
                return new GenerateApi();
            }
        );

        $this->commands(array('command.api.generate'));
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('apigenerator', 'command.debugbar.clear');
    }
}
