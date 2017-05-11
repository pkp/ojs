#!/bin/bash

#
# USAGE:
# runAllTests.sh [options]
#  -b	Include data build tests in application.
#  -C	Include class tests in lib/pkp.
#  -P	Include plugin tests in lib/pkp.
#  -c	Include class tests in application.
#  -p	Include plugin tests in application.
#  -f	Include functional tests in application.
#  -H   Generate HTML code coverage report.
#  -d   Display debug output from phpunit.
# If no options are specified, then all tests will be executed.
#
# Some tests will certain require environment variables in order to cnfigure
# the environment. In particular...
#  DUMMY_PDF=dummy.pdf: Path to dummy PDF file to use for document uploads
#  DUMMY_ZIP=dummy.zip: Path to dummy ZIP file to use for document uploads
#  BASEURL="http://localhost/omp": Full URL to base URL, excluding index.php
#  DBHOST=localhost: Hostname of database server
#  DBNAME=yyy: Database name
#  DBUSERNAME=xxx: Username for database connections
#  DBPASSWORD=zzz: Database password
#  FILESDIR=files: Pathname to use for storing server-side submission files
#  DBTYPE=MySQL: Name of database driver (MySQL or PostgreSQL)
#  TIMEOUT=30: Selenium timeout; optional, 30 seconds by default
#

set -e # Fail on first error

# Before executing tests for the first time please execute the
# following commands from the main ojs directory to install
# the default test environment.
#
# NB: This will replace your database and files directory, so
# either use a separate application instance for testing or back-up
# your original database and files before you execute tests!
#
# 1) Set up test data for functional tests:
#
#    > rm -r files
#    > tar xzf tests/functional/files.tar.gz
#    > sudo chown -R testuser:www-data files cache             # exchange www-data for your web server's group
#                                                              # and testuser for the user that executes tests.
#    > chmod -R ug+w files cache
#    > rm cache/*.php
#    > mysql -u ... -p... ... <tests/functional/testserver.sql # exchange ... for your database access data
#
#
# 2) Configure application for testing (in 'config.inc.php'):
#
#    [debug]
#    ...
#    show_stacktrace = On
#    deprecation_warnings = On
#    ...
#
#    ; Configuration for DOI export tests
#    webtest_datacite_pw = ...                                 ; To test Datacite export you need a Datacite test account.
#    webtest_medra_pw = ...                                    ; To test Medra export you need a Medra test account.
#
#    ; Configuration for Citation Manager tests
#    worldcat_apikey = ...                                     ; To test WorldCat citation look-up you need a WorldCat API key.
#
#
# 3) Install external dependencies
#
#    - If you want to execute ConfigTest you'll have to make local copies
#      of lib/pkp/tests/config/*.TEMPLATE.* without the "TEMPLATE" extension
#      (similarly to what you do in a new installation). In most
#      cases it should be enough to just adapt the database access data in
#      there.
#
#    - See plugins/generic/lucene/README to install the dependencies
#      required for an embedded Solr server. The Lucene/Solr tests
#      assume such a server to be present on the test machine. Also see
#      the "Troubleshooting" section there in case your tests fail.
#
#      To get a working test environment, you should execute
#      - ./plugins/generic/lucene/embedded/bin/start.sh
#      - php tools/rebuildSearchIndex.php -d
#      - You may have to repeat the chown/chmod commands above
#        to make sure that new files, created by start.sh will
#        will have the right permissions.
#
#	- To get code coverage reports for selenium tests working you need to
#	  install the dependencies for phpunit-selenium:
#		configure php auto_prepend/append
#
#	  	- sudo vi /etc/php5/mods-available/selenium-coverage.ini
#	  	- insert:
#			auto_append_file=[path_to_ojs]/lib/pkp/lib/vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/append.php
#			auto_prepend_file=[path_to_ojs]/lib/pkp/tests/prependCoverageReport.php
#			selenium_coverage_prepend_file=[path_to_ojs]/lib/pkp/lib/vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/prepend.php
#			phpunit_coverage_data_directory=[path_to_ojs]/lib/pkp/tests/results/coverage-tmp
#		- cd /etc/php5/apache2/conf.d/
#		- sudo ln -s ../../mods-available/selenium-coverage.ini 99-selenium-coverage.ini
#		- sudo /etc/init.d/apache2 restart
#
#		Make sure to have xdebug installed
#
#		Make sure that the web server can write to the output and temporary directories:
#		- lib/pkp/tests/results/coverage-tmp
#		- lib/pkp/tests/results/coverage-html
#
# 4) Don't forget to start your local selenium server before executing functional tests, i.e.:
#
#    > java -jar selenium-server.jar -browserSessionReuse


# Identify the tests directory.
TESTS_DIR=`readlink -f "lib/pkp/tests"`

# Shortcuts to the test environments.
TEST_CONF1="--configuration $TESTS_DIR/phpunit-env1.xml"
TEST_CONF2="--configuration $TESTS_DIR/phpunit-env2.xml"

### Command Line Options ###

# Run all types of tests by default, unless one or more is specified
DO_ALL=1

# Various types of tests
DO_APP_DATA=0
DO_PKP_CLASSES=0
DO_PKP_PLUGINS=0
DO_APP_CLASSES=0
DO_APP_PLUGINS=0
DO_APP_FUNCTIONAL=0
DO_COVERAGE=0
DEBUG=""

# Parse arguments
while getopts "bCPcpfdH" opt; do
	case "$opt" in
		b)	DO_ALL=0
			DO_APP_DATA=1
			;;
		C)	DO_ALL=0
			DO_PKP_CLASSES=1
			;;
		P)	DO_ALL=0
			DO_PKP_PLUGINS=1
			;;
		c)	DO_ALL=0
			DO_APP_CLASSES=1
			;;
		p)	DO_ALL=0
			DO_APP_PLUGINS=1
			;;
		f)	DO_ALL=0
			DO_APP_FUNCTIONAL=1
			;;
		H)	DO_COVERAGE=1
			;;
		d)	DEBUG="--debug"
			;;
	esac
done
phpunit='php lib/pkp/lib/vendor/phpunit/phpunit/phpunit'
REPORT_SWITCH=''
REPORT_TMP="$TESTS_DIR/results/coverage-tmp"
if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_DATA" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-APP_DATA.php"
	fi
	$phpunit $DEBUG $TEST_CONF1 --debug -v --stop-on-failure --stop-on-skipped $REPORT_SWITCH tests/data
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_PKP_CLASSES" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-PKP_CLASSES.php"
	fi
	$phpunit $DEBUG $TEST_CONF1 --debug -v $REPORT_SWITCH lib/pkp/tests/classes
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_PKP_PLUGINS" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-PKP_PLUGINS.php"
	fi
	$phpunit $DEBUG $TEST_CONF2 --debug -v $REPORT_SWITCH lib/pkp/plugins
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_CLASSES" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-APP_CLASSES.php"
	fi
	$phpunit $DEBUG $TEST_CONF1 --debug -v $REPORT_SWITCH tests/classes
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_PLUGINS" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-APP_PLUGINS.php"
	fi
	$phpunit $DEBUG $TEST_CONF2 --debug -v $REPORT_SWITCH plugins
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_FUNCTIONAL" -eq 1 \) ]; then
	if [ \( "$DO_COVERAGE" -eq 1 \) ]; then
		REPORT_SWITCH="--coverage-php $REPORT_TMP/coverage-APP_FUNCTIONAL.php"
	fi
	$phpunit $DEBUG $TEST_CONF1 --debug -v $REPORT_SWITCH tests/functional
fi
