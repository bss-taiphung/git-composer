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

return [

    /*
    |			                                      ,,~~--___---,
    |			                                     /            .~,
    |			                               /  _,~             )
    |			                              (_-(~)   ~, ),,,(  /'
    |			                      A Goat   Z6  .~`' ||     \ |
    |			                               /_,/     ||      ||
    |---------------------------------------------------W`------W`-------------
    | Installed Version
    |--------------------------------------------------------------------------
    |
    | This variable holds the current installed version of Platform.
    |
    | You are highly discouraged from touching this, ever.
    |
    | How It Works:
    |
    |  - On a blank installation, the installed version will be FALSE, which
    |    means Platform isn't installed.
    |
    |  - We'll check this version against the PLATFORM_VERSION constant and
    |    if this version is less, it means you have upgraded the Platform
    |    codebase, we'll then lock out the application and send you to
    |    the installer where you'll be taken through the upgrade process.
    |
    */

    'installed_version' => false,

    /*
    |--------------------------------------------------------------------------
    | Release Name
    |--------------------------------------------------------------------------
    |
    | Platform follows semantic versioning (Major.Minor.Patch).
    |
    | Major releases are given a code name.
    |
    */

    'release_name' => 'Optimistic Octopus',

];
