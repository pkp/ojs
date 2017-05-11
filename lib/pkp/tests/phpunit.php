#!/usr/bin/env php
<?php
/**
 * PKP-specific phpunit file.
 *
 * Integrates PHPUnit with the PKP application environment
 * and enables running/debugging tests from within Eclipse or
 * other CASE tools.
 *
 * Requires PEAR to be on the path (e.g. installed as a user
 * library in Eclipse).
 */

// This script may not be executed remotely.
if (isset($_SERVER['SERVER_NAME'])) {
	die('This script can only be executed from the command-line');
}

if (extension_loaded('xdebug')) {
    xdebug_disable();
}

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

require 'PHPUnit' . DIRECTORY_SEPARATOR . 'Autoload.php';

PHPUnit_TextUI_Command::main();
