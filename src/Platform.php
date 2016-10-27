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

namespace Platform\Foundation;

use Closure;
use PDOException;
use RuntimeException;
use Illuminate\Container\Container;
use Cartalyst\Extensions\Extension;
use Cartalyst\Extensions\ExtensionBag;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Platform
{
    /**
     * The Platform version number.
     *
     * @constant
     */
    const PLATFORM_VERSION = '5.0.0';

    /**
     * The location of the Platform license file.
     *
     * @constant
     */
    const LICENSE_FILE = '../LICENSE';

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * The Extension Bag used by Platform.
     *
     * @var \Cartalyst\Extensions\ExtensionBag
     */
    protected $extensionBag;

    /**
     * An array of whitelisted URIs which will not throw
     * eligibility Exceptions for when uninstalled.
     *
     * @var array
     */
    protected $eligibilityWhitelist = [];

    /**
     * Flag for whether Platform has booted.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Container\Container  $app
     * @param  \Cartalyst\Extensions\ExtensionBag  $extensionBag
     * @return void
     */
    public function __construct(Container $app, ExtensionBag $extensionBag)
    {
        $this->app = $app;

        $this->extensionBag = $extensionBag;
    }

    /**
     * Fires the 'platform.booting' event.
     *
     * @return void
     */
    public function beforeBoot()
    {
        $this->fire('booting', [ $this ]);
    }

    /**
     * Boots up Platform and all its requirements.
     *
     * @return void
     */
    public function boot()
    {
        // Check running eligibility
        if ($this->checkRunningEligibility()) {
            $this->beforeBoot();

            $this->setupExtensions();

            if ($this->isInstalled()) {
                $this->bootExtensions();
            }

            $this->afterBoot();

            $this->booted = true;
        } else {
            if (! $this->app->runningInConsole()) {
                $this->fire('ineligible', [ $this ]);
            }
        }
    }

    /**
     * Fires the 'platform.booted' event.
     *
     * @return void
     */
    public function afterBoot()
    {
        $this->fire('booted', [ $this ]);
    }

    /**
     * Sets up the extensions environment for Platform.
     *
     * @return void
     */
    public function setupExtensions()
    {
        Extension::setMigrator($this->app['migrator']);

        Extension::setConnectionResolver($this->app['db']);

        $this->extensionBag->findAndRegisterExtensions();

        $this->extensionBag->sortExtensions();
    }

    /**
     * Boots all extensions associated with Platform.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function bootExtensions()
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException('Cannot use Extensions until Platform is installed.');
        }

        $allAttributes = $this->getAllExtensionsAttributes();

        foreach ($this->extensionBag as $extension) {
            if ($attributes = array_get($allAttributes, $extension->getSlug())) {
                $extension->setDatabaseAttributes($attributes);

                if ($extension->isEnabled()) {
                    $extension->boot();
                }
            }
        }
    }

    /**
     * Update all extensions associated with Platform.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function updateExtensions()
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException('Cannot use Extensions until Platform is installed.');
        }

        foreach ($this->extensionBag as $extension) {
            if ($extension->isInstalled() && $extension->needsUpgrade()) {
                $extension->upgrade();
            }
        }
    }

    /**
     * Returns whether Platform is installed, which is based off
     * the installed version in the configuration file.
     *
     * @return bool
     */
    public function isInstalled()
    {
        // Always return true for the testing environment.
        if ($this->app->runningInConsole() && $this->app->environment() === 'testing') {
            return true;
        }

        return (bool) $this->installedVersion();
    }

    /**
     * Returns if Platform has finished the booting process.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Returns the installed version of Platform.
     *
     * When this is behind the codebase version, Platform
     * needs to be upgraded.
     *
     * @return string
     */
    public function installedVersion()
    {
        return $this->app['config']->get('platform-foundation.installed_version');
    }

    /**
     * Returns the codebase version of Platform.
     *
     * @return string
     */
    public function codebaseVersion()
    {
        return self::PLATFORM_VERSION;
    }

    /**
     * Returns if Platform has upgrades available.
     *
     * @return bool
     */
    public function needsUpgrade()
    {
        return version_compare($this->installedVersion(), $this->codebaseVersion()) < 0;
    }

    /**
     * Checks the web eligibility for Platform. If true, Platform
     * is safe to run in the web.
     *
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function checkRunningEligibility()
    {
        // If we're running in console, we're always fine to
        // run Platform except for testing.
        if ($this->app->runningInConsole()) {
            return $this->app->environment() !== 'testing';
        }

        if ($this->isInstalled()) {
            // Now, let's check for database connectivity. If we have no
            // connectivity, the database connection is probably lost.
            // This means the service is in fact unavailable.
            try {
                $this->app['db']->connection();

                return true;
            } catch (PDOException $e) {
                throw new HttpException(503, 'Database connection could not be established.');
            }
        }

        // Check if the path is on the eligibility whitelist
        return in_array($this->app['request']->path(), $this->eligibilityWhitelist);
    }

    /**
     * Returns the Extension Bag.
     *
     * @return \Cartalyst\Extensions\ExtensionBag
     */
    public function getExtensionBag()
    {
        return $this->extensionBag;
    }

    /**
     * Adds an item to the eligibility whitelist.
     *
     * @param  string  $uri
     * @return void
     */
    public function addEligibilityWhitelist($uri)
    {
        $this->eligibilityWhitelist[] = $uri;
    }

    /**
     * Returns the whitelisted uris.
     *
     * @return array
     */
    public function getEligibilityWhitelist()
    {
        return $this->eligibilityWhitelist;
    }

    /**
     * Returns the license file for Platform.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getLicense()
    {
        $licenseFile = __DIR__.DIRECTORY_SEPARATOR.self::LICENSE_FILE;

        if (! $this->app['files']->exists($licenseFile)) {
            throw new RuntimeException("Platform license file is missing at [{$licenseFile}].");
        }

        return $this->app['files']->get($licenseFile);
    }

    /**
     * Registers a "platform.ineligible" callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function ineligible(Closure $callback)
    {
        $this->listen('ineligible', $callback);
    }

    /**
     * Registers a "platform.booting" callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function booting(Closure $callback)
    {
        $this->listen('booting', $callback);
    }

    /**
     * Registers a "platform.booted" callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function booted(Closure $callback)
    {
        $this->listen('booted', $callback);
    }

    /**
     * Listens to the given event.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return void
     */
    protected function listen($name, Closure $callback)
    {
        $this->app['events']->listen("platform.{$name}", $callback);
    }

    /**
     * Fires the given event.
     *
     * @param  string  $name
     * @param  mixed  $params
     * @return void
     */
    protected function fire($name, $params)
    {
        $this->app['events']->fire("platform.{$name}", $params);
    }

    /**
     * Returns all the installed extensions attributes.
     *
     * @return array
     */
    protected function getAllExtensionsAttributes()
    {
        $databaseAttributes = $this->app['db']->table('extensions')->get();

        $attributes = [];

        foreach ($databaseAttributes as $attribute) {
            $attribute = (array) $attribute;

            $attribute['enabled'] = (bool) array_get($attribute, 'enabled', false);

            $attributes[$attribute['slug']] = $attribute;
        }

        return $attributes;
    }
}
