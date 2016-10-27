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

namespace Platform\Foundation\Controllers;

use Illuminate\Support\Facades\View;
use Cartalyst\Themes\Laravel\Facades\Theme;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;

class Controller extends \Illuminate\Routing\Controller
{
    /**
     * The current logged in user instance.
     *
     * @var \Cartalyst\Sentinel\Users\UserInterface
     */
    protected $currentUser;

    /**
     * The alerts instance.
     *
     * @var \Cartalyst\Alerts\Alerts
     */
    protected $alerts;

    /**
     * The current active theme area used by this controller.
     *
     * @var string
     */
    protected $activeThemeArea = 'frontend';

    /**
     * The current fallback theme area used by this controller.
     *
     * @var string
     */
    protected $fallbackThemeArea = 'frontend';

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        // Set the active theme area
        $this->setActiveThemeArea();

        // Set the fallback theme area
        $this->setFallbackThemeArea();

        $this->alerts = app('alerts');

        $this->currentUser = Sentinel::getUser();

        View::share([ 'currentUser' => $this->currentUser ]);
    }

    /**
     * Sets the active theme area.
     *
     * @return void
     */
    protected function setActiveThemeArea()
    {
        if ($theme = $this->activeThemeArea) {
            if ($active = config("platform-themes.active.{$theme}")) {
                Theme::setActive($active);
            }
        }
    }

    /**
     * Sets the fallback theme area.
     *
     * @return void
     */
    protected function setFallbackThemeArea()
    {
        if ($theme = $this->fallbackThemeArea) {
            if ($fallback = config("platform-themes.fallback.{$theme}")) {
                Theme::setFallback($fallback);
            }
        }
    }
}
