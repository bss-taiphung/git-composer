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

use Illuminate\Support\Facades\Response;

if (! function_exists('datagrid')) {
    /**
     * Returns the evaluated datagrid response for the given data.
     *
     * @param  mixed  $data
     * @param  array  $columns
     * @param  array  $settings
     * @param  \Closure  $transformer
     * @return \Cartalyst\DataGrid\DataGrid
     */
    function datagrid($data, $columns = [], $settings = [], Closure $transformer = null)
    {
        return app('datagrid')->make($data, $columns, $settings, $transformer);
    }
}

if (! function_exists('input')) {
    /**
     * Returns an instance of the input.
     *
     * @param  string|null  $key
     * @param  string|null  $default
     * @return mixed
     */
    function input($key = null, $default = null)
    {
        if (! is_null($key)) {
            return app('request')->input($key, $default);
        }

        return app('request');
    }
}

if (! function_exists('redirect')) {
    /**
     * Returns an instance of the redirector.
     *
     * @param  string|null  $to
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    function redirect($to = null)
    {
        if (! is_null($to)) {
            return app('redirect')->to($to);
        }

        return app('redirect');
    }
}

if (! function_exists('request')) {
    /**
     * Returns an instance of the http request.
     *
     * @return \Illuminate\Http\Request
     */
    function request()
    {
        return app('request');
    }
}

if (! function_exists('session')) {
    /**
     * Returns an instance of the session.
     *
     * @param  string|null  $sessionId
     * @return string|\Illuminate\Session\SessionManager
     */
    function session($sessionId = null)
    {
        if (! is_null($sessionId)) {
            return app('session')->get($sessionId);
        }

        return app('session');
    }
}

if (! function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string|null  $path
     * @param  mixed  $parameters
     * @param  bool  $secure
     * @return \Illuminate\Routing\UrlGenerator
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        if (! is_null($path)) {
            return app('url')->to($path, $parameters, $secure);
        }

        return app('url');
    }
}

if (! function_exists('view')) {
    /**
     * Returns the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  array  $mergeData
     * @return \Illuminate\View\View
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        if (! is_null($view)) {
            return app('view')->make($view, $data, $mergeData);
        }

        return app('view');
    }
}
