<?php

namespace Classiebit\Addchat;

use Illuminate\Foundation\AliasLoader;
use Classiebit\Addchat\Facades\Addchat as AddchatFacade;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

use  Classiebit\Addchat\Commands\InstallCommand;
use Config;

class AddchatServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Addchat', AddchatFacade::class);

        $this->app->singleton('addchat', function () {
            return new Addchat();
        });

        $this->registerConfigs();

        if ($this->app->runningInConsole()) {
            $this->registerPublishableResources();
            $this->registerConsoleCommands();
        }
    }

    /**
     * Bootstrap the application services.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router)
    {
        // load addchat language files publishable.lang
        $this->loadTranslationsFrom(realpath(__DIR__.'/../publishable/lang'), 'addchat');

        // load addchat database migrations
        if (config('addchat.database.autoload_migrations', true)) {
            $this->loadMigrationsFrom(realpath(__DIR__.'/../publishable/database/migrations'));
        }
    }

    /**
     * Register the publishable files.
     */
    private function registerPublishableResources()
    {
        $publishablePath = dirname(__DIR__).'/publishable';

        $publishable = [
            'addchat_config' => [
                "{$publishablePath}/config/addchat.php" => config_path('addchat.php'),
            ],
            'addchat_resources' => [
                "{$publishablePath}/lang" => resource_path('lang/vendor/addchat')
            ],

            /* Publish AddChat assets to public folder */
            'addchat_public' => [
                "{$publishablePath}/assets"     => public_path(),
            ],
        ];

        foreach ($publishable as $group => $paths) 
        {
            $this->publishes($paths, $group);
        }
    }

    public function registerConfigs()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/publishable/config/addchat.php', 'addchat'
        );
    }

    /**
     * Register the commands accessible from the Console.
     */
    private function registerConsoleCommands()
    {
        $this->commands(Commands\InstallCommand::class);
    }

    
}
