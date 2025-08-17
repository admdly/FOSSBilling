<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

use FOSSBilling\Kernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . DIRECTORY_SEPARATOR . 'load.php';

// TODO: Re-integrate DebugBar with the new Kernel
// $debugBar = new DebugBar\StandardDebugBar();
// ...

/*
 * Workaround: Session IDs get reset when using PGs like PayPal because of the `samesite=strict` cookie attribute, resulting in the client getting logged out.
 * Internally the return and cancel URLs get a restore_session GET parameter attached to them with the proper session ID to restore, so we do so here.
 */
if (!empty($_GET['restore_session'])) {
    session_id($_GET['restore_session']);
}

$di['session'];

// Determine the environment and debug mode
// TODO: Use a .env file for this in the future
$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ($env !== 'prod'));

$kernel = new Kernel($env, $debug);

// Create a request object from the PHP globals
$request = Request::createFromGlobals();

// Rewrite for custom pages to maintain backwards compatibility
if (str_starts_with($request->getPathInfo(), '/page/')) {
    $path = substr_replace($request->getPathInfo(), '/custompages/', 0, 6);
    $request->server->set('REQUEST_URI', $path);
}

// Handle the request
$response = $kernel->handle($request);

// Send the response back to the browser
$response->send();

// Terminate the request/response cycle
$kernel->terminate($request, $response);
