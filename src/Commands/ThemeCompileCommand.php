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

namespace Platform\Foundation\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class ThemeCompileCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $name = 'theme:compile';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Compile theme assets';

    /**
     * Holds all the routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Holds all the routes.
     *
     * @var array
     */
    protected $adminRoutes = [];

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        if (! $this->laravel['platform']->isInstalled()) {
            return;
        }

        $this->info('Compiling themes');

        $this->laravel['request']->setSession($this->laravel['session.store']);

        $this->pagesExtensionRoutes();

        $this->registeredRoutes();

        $this->settingsRoutes();

        $allRoutes = array_merge($this->routes, $this->adminRoutes);

        $progress = new ProgressBar($this->output, count($allRoutes));

        $progress->start();

        // Override document root for the
        // UriRewriteFilter
        $_SERVER['DOCUMENT_ROOT'] = public_path();
        $_SERVER['REQUEST_URI'] = $this->laravel['config']->get('app.url');

        if ($user = $this->laravel['sentinel']->findById(1)) {
            $this->laravel['sentinel']->login($user);
        }

        $this->laravel['Illuminate\Contracts\Http\Kernel']->disableRouteMiddleware();

        foreach ($allRoutes as $uri) {
            $this->laravel['theme.assets']->clearQueue('scripts');
            $this->laravel['theme.assets']->clearQueue('styles');

            $progress->advance();

            try {
                $request = $this->laravel['request']->create($uri, 'GET');

                @$this->laravel['router']->dispatch($request);
            } catch (\Exception $e) {
            }
        }

        $progress->finish();

        $this->line('');
    }

    /**
     * Retrieve all the routes registered by the pages extension.
     *
     * @return void
     */
    protected function pagesExtensionRoutes()
    {
        if ($extension = $this->laravel['extensions']->get('platform/pages')) {
            if ($extension->isEnabled()) {
                $pages = app('Platform\Pages\Repositories\PageRepositoryInterface');

                foreach ($pages->findAllEnabled() as $page) {
                    $this->laravel['router']->get($page->uri, [
                        'uses' => 'Platform\Pages\Controllers\Frontend\PagesController@page',
                    ]);

                    $this->routes[] = $page->uri;
                }
            }
        }
    }

    /**
     * Retrieve settings routes.
     *
     * @return void
     */
    protected function settingsRoutes()
    {
        $settings = app('Platform\Settings\Repositories\SettingRepositoryInterface');

        $settingsSections = $settings->findAllSections();

        foreach ($settingsSections as $section) {
            $this->adminRoutes[] = admin_uri()."/settings/{$section->id}";
        }
    }

    /**
     * Retrieve all the registered routes.
     *
     * @return void
     */
    protected function registeredRoutes()
    {
        $routes = app('router')->getRoutes();

        $adminUri = admin_uri();

        foreach ($routes as $uri) {
            $methods = $uri->getMethods();

            $uri = $uri->getUri();

            if (in_array('GET', $methods) && strpos($uri, '{') === false) {
                if (strpos($uri, $adminUri) !== false) {
                    $this->adminRoutes[] = $uri;
                } else {
                    $this->routes[] = $uri;
                }
            }
        }
    }
}
