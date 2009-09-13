<?php
// Configure PKP error handling for tests
define('DONT_DIE_ON_ERROR', true);
ini_set('error_log', getcwd() . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'report-results' . DIRECTORY_SEPARATOR . 'error.log');

// Include required classes and functions
require_once 'PHPUnit/Extensions/OutputTestCase.php';
require_once 'includes/driver.inc.php';

abstract class OjsBaseTestCase extends PHPUnit_Extensions_OutputTestCase {
}
?>
