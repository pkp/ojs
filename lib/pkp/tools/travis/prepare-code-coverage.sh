#!/bin/bash

# @file tools/travis/prepare-code-coverage.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to set up the Travis server for code coverage reports
#

set -xe

# Set the php auto append/prepend scripts up
LIB_PATH="${TRAVIS_BUILD_DIR}/lib/pkp";
echo "auto_append_file = ${LIB_PATH}/lib/vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/append.php" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "auto_prepend_file = ${LIB_PATH}/tests/prependCoverageReport.php" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "selenium_coverage_prepend_file = ${LIB_PATH}/lib/vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/prepend.php" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "phpunit_coverage_data_directory = ${LIB_PATH}/tests/results/coverage-tmp" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
