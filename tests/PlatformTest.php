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

use Mockery as m;
use Platform\Foundation\Platform;
use Cartalyst\Extensions\ExtensionBag;
use Cartalyst\Testing\IlluminateTestCase;
use Illuminate\Config\Repository as ConfigRepository;

class PlatformTest extends IlluminateTestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Additional IoC bindings
        $this->app['extensions'] = m::mock('Cartalyst\Extensions\ExtensionBag');
        $this->app['db']         = m::mock('Illuminate\Database\DatabaseManager');

        // Platform
        $this->platform = new Platform($this->app, $this->app['extensions']);
    }

    /** @test */
    public function it_can_fire_event_before_booting()
    {
        $this->app['events'] = new Illuminate\Events\Dispatcher;
        $this->app['events']->listen('platform.booting', function () {
            $_SERVER['__platform.booting'] = true;
        });

        $this->platform->beforeBoot();

        $this->assertTrue($_SERVER['__platform.booting']);
        unset($_SERVER['__platform.booting']);
    }

    /** @test */
    public function it_can_fire_event_after_booting()
    {
        $this->app['events'] = new Illuminate\Events\Dispatcher;
        $this->app['events']->listen('platform.booted', function () {
            $_SERVER['__platform.booted'] = true;
        });

        $this->platform->afterBoot();

        $this->assertTrue($_SERVER['__platform.booted']);
        unset($_SERVER['__platform.booted']);
    }

    /** @test */
    public function it_can_fire_platform_ineligible_event()
    {
        $this->consoleEligibility(false, false);

        $this->app['events'] = new Illuminate\Events\Dispatcher;
        $this->app['events']->listen('platform.ineligible', function () {
            $_SERVER['__platform.ineligible'] = true;
        });

        $this->platform->boot();

        $this->assertTrue($_SERVER['__platform.ineligible']);

        unset($_SERVER['__platform.ineligible']);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_an_exception_on_boot_if_not_installed()
    {
        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions'],
        ]);

        $this->platform->shouldReceive('isInstalled')
            ->once()
            ->andReturn(false);

        $this->platform->bootExtensions();
    }

    /** @test */
    public function it_can_boot()
    {
        $this->consoleEligibility();

        $this->shouldFetchExtensions();

        $this->app['config']->shouldIgnoreMissing('5.0.0');
        $this->app['extensions']->shouldIgnoreMissing();
        $this->app['extensions']->shouldReceive('getIterator')
            ->once()
            ->andReturn($iterator = m::mock('Iterator'));

        $iterator->shouldIgnoreMissing();

        $this->platform->boot();
    }

    /** @test */
    public function it_can_boot_extensions()
    {
        $this->app['extensions'] = new ExtensionBag($this->app['files'], m::mock('Cartalyst\Extensions\FinderInterface'));

        // Platform
        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions']
        ]);

        $this->platform->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        // Extension
        $extension = m::mock('Cartalyst\Extensions\Extension');

        $extension->shouldReceive('getSlug')
            ->once()
            ->andReturn('foo/bar');

        $extension->shouldReceive('setDatabaseAttributes')
            ->once();

        $extension->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $extension->shouldReceive('boot');

        // Add the extension to the bag
        $this->app['extensions']->put('foo', $extension);

        $allAttributes = [
            'foo/bar' => [
                'slug'    => 'foo/bar',
                'version' => '1.0.0',
                'enabled' => true,
            ],
        ];

        // Fetch extensions from the database
        $this->shouldFetchExtensions($allAttributes);

        // Boot extensions
        $this->platform->bootExtensions();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_an_exception_if_platform_is_not_installed()
    {
        $this->app['extensions'] = new ExtensionBag($this->app['files'], m::mock('Cartalyst\Extensions\FinderInterface'));

        // Platform
        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions']
        ]);

        $this->platform->shouldReceive('isInstalled')
            ->once()
            ->andReturn(false);

        $this->platform->updateExtensions();
    }

    /** @test */
    public function it_can_update_extensions()
    {
        $this->app['extensions'] = new ExtensionBag($this->app['files'], m::mock('Cartalyst\Extensions\FinderInterface'));

        // Platform
        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions']
        ]);

        $this->platform->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        // Extension
        $extension = m::mock('Cartalyst\Extensions\Extension');

        $extension->shouldReceive('isInstalled')
            ->once()
            ->andReturn(true);

        $extension->shouldReceive('needsUpgrade')
            ->once()
            ->andReturn(true);

        $extension->shouldReceive('upgrade');

        // Add the extension to the bag
        $this->app['extensions']->put('foo', $extension);

        $allAttributes = [
            'foo/bar' => [
                'slug'    => 'foo/bar',
                'version' => '1.0.0',
                'enabled' => true,
            ],
        ];

        // Update extensions
        $this->platform->updateExtensions();
    }

    /** @test */
    public function it_returns_true_for_testing_env()
    {
        $this->consoleEligibility(true, true);

        $this->app->shouldReceive('environment')
            ->once()
            ->andReturn('testing');

        $this->assertTrue($this->platform->isInstalled());
    }

    /** @test */
    public function it_can_retrieve_the_booted_status()
    {
        $this->assertFalse($this->platform->isBooted());
    }

    /** @test */
    public function it_can_retrieve_the_codebase_version()
    {
        $this->assertEquals('5.0.0', $this->platform->codebaseVersion());
    }

    /** @test */
    public function it_can_determine_if_it_needs_upgrade()
    {
        $this->app['config']->shouldIgnoreMissing('5.0.0');

        $this->assertFalse($this->platform->needsUpgrade());
    }

    /** @test */
    public function it_can_check_for_running_eligibility()
    {
        $this->checkEligibility();

        $this->app['db']->shouldIgnoreMissing();

        $this->assertTrue($this->platform->checkRunningEligibility());
    }

    /** @test */
    public function it_can_check_for_running_eligibility_in_cli()
    {
        $this->baseConsole();

        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions'],
        ]);

        $this->app->shouldReceive('runningInConsole')
            ->twice()
            ->andReturn(true);

        $this->app->shouldReceive('environment')
            ->once()
            ->andReturn('local');

        $this->assertTrue($this->platform->checkRunningEligibility());

        $this->app->shouldReceive('environment')
            ->once()
            ->andReturn('testing');

        $this->assertFalse($this->platform->checkRunningEligibility());
    }

    /** @test */
    public function it_can_check_for_running_eligibility_when_not_installed()
    {
        $this->checkEligibility(false, false);

        $this->app['request'] = m::mock('Illuminate\Http\Request');
        $this->app['request']->shouldReceive('path')
            ->once();

        $this->assertFalse($this->platform->checkRunningEligibility());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function it_throws_an_exception_on_eligibility_check_if_db_connection_cannot_be_established()
    {
        $this->checkEligibility(false, true);

        $this->app['db']->shouldReceive('connection')
            ->once()
            ->andThrow(new PDOException);

        $this->platform->checkRunningEligibility();
    }

    /** @test */
    public function it_can_retrieve_the_extensions_bag()
    {
        $this->assertInstanceOf('Cartalyst\Extensions\ExtensionBag', $this->platform->getExtensionBag());
    }

    /** @test */
    public function it_can_add_and_retrieve_whitelisted_uris()
    {
        $expected = [
            'foo',
        ];

        $this->platform->addEligibilityWhitelist('foo');

        $this->assertSame($expected, $this->platform->getEligibilityWhitelist());
    }

    /** @test */
    public function it_can_get_the_license()
    {
        $this->app['files']->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->app['files']->shouldReceive('get')
            ->once();

        $this->platform->getLicense();
    }

    /** @test */
    public function it_can_listen_to_the_ineligible_event()
    {
        $callback = function () {};

        $this->app['events']->shouldReceive('listen')
            ->with('platform.ineligible', $callback)
            ->once();

        $this->platform->ineligible($callback);
    }

    /** @test */
    public function it_can_listen_to_the_booting_event()
    {
        $callback = function () {};

        $this->app['events']->shouldReceive('listen')
            ->with('platform.booting', $callback)
            ->once();

        $this->platform->booting($callback);
    }

    /** @test */
    public function it_can_listen_to_the_booted_event()
    {
        $callback = function () {};

        $this->app['events']->shouldReceive('listen')
            ->with('platform.booted', $callback)
            ->once();

        $this->platform->booted($callback);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_an_exception_if_license_file_is_not_found()
    {
        $this->app['files']->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->platform->getLicense();
    }

    /**
     * In console and eligibility expectations.
     *
     * @param  bool  $console
     * @param  bool  $eligibility
     * @return void
     */
    protected function consoleEligibility($console = false, $eligibility = true)
    {
        $this->baseConsole();

        $this->platform = m::mock('Platform\Foundation\Platform[checkRunningEligibility]', [
            $this->app,
            $this->app['extensions'],
        ]);

        $this->app->shouldReceive('runningInConsole')
            ->andReturn($console);

        $this->platform->shouldReceive('checkRunningEligibility')
            ->andReturn($eligibility);
    }

    /**
     * Running eligibility expectation.
     *
     * @param  bool  $console
     * @param  bool  $installed
     * @return void
     */
    protected function checkEligibility($console = false, $installed = true)
    {
        $this->baseConsole();

        $this->platform = m::mock('Platform\Foundation\Platform[isInstalled]', [
            $this->app,
            $this->app['extensions'],
        ]);

        $this->app->shouldReceive('runningInConsole')
            ->once()
            ->andReturn($console);

        $this->platform->shouldReceive('isInstalled')
            ->once()
            ->andReturn($installed);
    }

    /**
     * Fetching and caching extensions expectation.
     *
     * @return void
     */
    protected function shouldFetchExtensions($return = [])
    {
        $this->app['db']->shouldIgnoreMissing($builder = m::mock('Illuminate\Database\Query\Builder'));

        // $this->app['cache']->shouldReceive('rememberForever')
        // 	->with('cartalyst.extensions')
        // 	->once()
        // 	->andReturn($builder);

        $builder->shouldReceive('get')
            ->once()
            ->andReturn($return);
    }

    /**
     * Base mocks with partial runningInConsole.
     *
     * @return void
     */
    protected function baseConsole()
    {
        $this->app               = m::mock('Illuminate\Container\Container[runningInConsole]');
        $this->app['config']     = m::mock('Illuminate\Config\Repository');
        $this->app['db']         = m::mock('Illuminate\Database\DatabaseManager');
        $this->app['events']     = new Illuminate\Events\Dispatcher;
        $this->app['extensions'] = m::mock('Cartalyst\Extensions\ExtensionBag');
        $this->app['migrator']   = m::mock('Illuminate\Database\Migrations\Migrator');
    }
}
