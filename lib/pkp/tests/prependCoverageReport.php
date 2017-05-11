<?php
/**
 * @file tests/prependCoverageReport.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup tests
 *
 * @brief This script needs to be called via the auto_prepend_file php.ini config
 * directive. It prepares the environment for the PHPUnit Selenium code
 * coverage scripts
 *
 * @see tools/runAllTests.sh
 */
$GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'] = get_cfg_var('phpunit_coverage_data_directory');
include get_cfg_var('selenium_coverage_prepend_file');

if (basename($_SERVER['SCRIPT_NAME']) == 'phpunit_coverage.php') {
	chdir(get_cfg_var('phpunit_coverage_data_directory'));
}
