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

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Container;

class CheckEnvFileCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $name = 'platform:check-env-file';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Checks if Platform has a default .env file.';

    /**
     * The Illuminate container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $laravel;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Container\Container  $laravel
     * @return void
     */
    public function __construct(Container $laravel)
    {
        parent::__construct();

        $this->laravel = $laravel;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $envFile = $this->laravel['path.base'].'/.env';

        if (! $this->laravel['files']->exists($envFile)) {
            $contents = 'APP_KEY='.Str::random(32);

            $this->laravel['files']->put($envFile, $contents);
        }
    }
}
