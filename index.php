<?php

/**
 * @file ojs/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Bootstrap code for OJS site. Loads required files and then calls the
 * dispatcher to delegate to the appropriate request handler.
 */

use APP\core\Application;
use PKP\config\Config;

// Initialize global environment
define('INDEX_FILE_LOCATION', __FILE__);
require_once './lib/pkp/includes/bootstrap.php';

// Temporarly enable enable_new_submission_listing for OJS, until OMP&OPS catch up
// Its still possible to disable it with explicitely setting it to 'Off'
if(Config::getVar('features', 'enable_new_submission_listing') === null) {
    $configData = & Config::getData();
    $configData['features']['enable_new_submission_listing'] = true;
}


// Serve the request
Application::get()->execute();
