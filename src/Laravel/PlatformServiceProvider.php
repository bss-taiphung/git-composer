<?php

/**
 * Part of the Platform Foundation extension.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Cartalyst PSL License.
 *
 * This source file is subject to the Cartalyst PSL License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Platform Foundation extension
 * @version    4.0.1
 * @author     Cartalyst LLC
 * @license    Cartalyst PSL
 * @copyright  (c) 2011-2016, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Platform\Foundation\Laravel;

use Platform\Foundation\Platform;
use Cartalyst\Extensions\Extension;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Platform\Foundation\Commands\UpgradeCommand;
use Platform\Foundation\Commands\CheckEnvFileCommand;
use Platform\Foundation\Commands\ThemeCompileCommand;

class PlatformServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        // Set the event dispatcher for extensions
        Extension::setEventDispatcher($this->app['events']);

        // Load the hooks
        $hooks = $this->app->path().'/hooks.php';

        if ($this->app['files']->exists($hooks)) {
            require $hooks;
        }

        // Load the widget mappings
        $widgets = $this->app->path().'/widgets.php';

        if ($this->app['files']->exists($widgets)) {
            require $widgets;
        }

        $this->app['platform']->boot();
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->prepareResources();

        $this->registerPlatform();

        $this->registerCheckEnvFileCommand();

        $this->registerThemeCompileCommand();

        $this->registerUpgradeCommand();

        $this->registerAlerts();

        // Register the Settings service provider.
        $this->app->register('Cartalyst\Settings\Laravel\SettingsServiceProvider');

        // Register the 'platform' form on the settings
        $this->app['cartalyst.settings']->form('platform');
    }

    /**
     * Prepare the package resources.
     *
     * @return void
     */
    protected function prepareResources()
    {
        // Publish config
        $config = realpath(__DIR__.'/../config/config.php');

        $this->mergeConfigFrom($config, 'platform-foundation');

        $this->publishes([
            $config => config_path('platform-foundation.php'),
        ], 'config');
    }

    /**
     * Register platform.
     *
     * @return void
     */
    protected function registerPlatform()
    {
        $this->app['platform'] = $this->app->share(function ($app) {
            return new Platform($app, $app['extensions']);
        });

        $this->app->alias('platform', 'Platform\Foundation\Platform');
    }

    /**
     * Register the check env file command.
     *
     * @return void
     */
    protected function registerCheckEnvFileCommand()
    {
        $this->app['command.platform.check.env.file'] = $this->app->share(function ($app) {
            return new CheckEnvFileCommand($app);
        });

        $this->commands('command.platform.check.env.file');
    }

    /**
     * Register the theme compile command.
     *
     * @return void
     */
    protected function registerThemeCompileCommand()
    {
        $this->app['command.theme.compile'] = $this->app->share(function ($app) {
            return new ThemeCompileCommand;
        });

        $this->commands('command.theme.compile');
    }

    /**
     * Register the upgrade command.
     *
     * @return void
     */
    protected function registerUpgradeCommand()
    {
        $this->app['command.platform.upgrade'] = $this->app->share(function ($app) {
            return new UpgradeCommand($app['platform']);
        });

        $this->commands('command.platform.upgrade');
    }

    /**
     * Register the alerts package.
     *
     * @return void
     */
    protected function registerAlerts()
    {
        // Register the Alerts service provider.
        $this->app->register('Cartalyst\Alerts\Laravel\AlertsServiceProvider');

        AliasLoader::getInstance()->alias('Alert', 'Cartalyst\Alerts\Laravel\Facades\Alert');
    }
}
