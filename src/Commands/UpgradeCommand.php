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
use Platform\Foundation\Platform;

class UpgradeCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $name = 'platform:upgrade';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Upgrade Platform Extensions';

    /**
     * Platform application instance.
     *
     * @var \Platform\Foundation\Platform
     */
    protected $platform;

    /**
     * Constructor.
     *
     * @param  \Platform\Foundation\Platform  $platform
     * @return void
     */
    public function __construct(Platform $platform)
    {
        parent::__construct();

        $this->platform = $platform;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->platform->isInstalled()) {
            $this->info('Upgrading Platform Extensions');

            $this->platform->updateExtensions();
        }
    }
}
