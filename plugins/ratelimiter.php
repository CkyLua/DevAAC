<?php
/**
 * Developer: Daniel
 * Date: 2/15/14
 * Time: 10:12 PM
 */

//return; // UNCOMMENT TO DISABLE THIS PLUGIN

// THIS PLUGIN CURRENTLY SUPPORTS APC ONLY
if(!extension_loaded('apc') or !ini_get('apc.enabled'))
    return array('name' => 'Rate limiter',
        'description' => 'Rate limiter is disabled because APC or APCu is missing.',
        'version' => '0.1',
        'author' => 'Don Daniello',
        'link' => 'http://blablabal',
        'enabled' => false);

// DEFAULT CONFIG
defined('RATELIMITER_RULES') or define('RATELIMITER_RULES', serialize(array(
    // DEFINE RULES WITHOUT ROUTES_PREFIX
    // PATH -> NUMBER OF SECONDS TO WAIT BETWEEN REQUESTS
    'GET' => array(
        '/plugins' => 5
    ),
    'POST' => array(
        '/' => 5 // for Simple AAC (plugin)
    )
)));
// SHOULD WE RESET THE TIMER ON EVERY ATTEMPT?
defined('RATELIMITER_PENALIZE') or define('RATELIMITER_PENALIZE', false);

// http://docs.slimframework.com/#How-to-Use-Hooks
$DevAAC->hook('slim.before.dispatch', function () use ($DevAAC) {

    $rules = unserialize(RATELIMITER_RULES);

    // $DevAAC->router->currentRoute is NULL in this hook plus it's prtoected
    // we cannot use route names (even if we assigned them)
    // unfortunately we need to base on path

    $req = $DevAAC->request;
    $path = $req->getPath();
    $method = $req->getMethod();

    // REMOVE ROUTES_PREFIX FROM PATH
    if (substr($path, 0, strlen(ROUTES_PREFIX)) == ROUTES_PREFIX)
        $path = substr($path, strlen(ROUTES_PREFIX));

    // DO WE HAVE A RULE?
    if( array_key_exists($method, $rules) && array_key_exists($path, $rules[$method]) ) {
        // every path for every IP is a separate object to be thread safe
        $objname = $req->getIp() . '_' . $path;

        if(apc_fetch($objname) + $rules[$method][$path] > time()) {
            $DevAAC->halt(429, 'Too many requests.');

            if(!RATELIMITER_PENALIZE)
                return;
        }
        apc_store($objname, time());
        $DevAAC->expires('+'.$rules[$method][$path].' seconds');
    }
});

return array('name' => 'Rate limiter',
             'description' => 'Rate limiter requires APC user cache (APC or APCu)',
             'version' => '0.1',
             'author' => 'Don Daniello',
             'link' => 'http://blablabal',
             'time' => date(DATE_ATOM));