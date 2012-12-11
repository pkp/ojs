#!/bin/bash

#
# USAGE:
# runAllTests.sh [options]
#  -C	Include class tests in lib/pkp.
#  -P	Include plugin tests in lib/pkp.
#  -c	Include class tests in application.
#  -p	Include plugin tests in application.
#  -f	Include functional tests in application.
# If no options are specified, then all tests will be executed.
#

# Before executing tests for the first time please execute the
# following commands from the main ojs directory to install
# the default test environment.
# 
# NB: This will replace your database and files directory, so
# either use a separate OJS instance for testing or back-up
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
# 2) Configure OJS for testing (in 'config.inc.php'):
#   
#    [debug]
#    ...
#    show_stacktrace = On
#    deprecation_warnings = On
#    ...
#
#    ; Code Coverage Analysis (optional)
#    coverage_phpunit_dir = /usr/share/php/PHPUnit/            ; This points to the PHPUnit installation directory.
#    coverage_report_dir = .../coverage/                       ; This is an absolute path to a folder accessible by the web server which will contain the coverage reports.
#
#    ; Functional Test Configuration
#    webtest_base_url = http://localhost/...                   ; This points to the OJS base URL to be used for Selenium Tests.
#    webtest_admin_pw = ...                                    ; This is the OJS admin password used for Selenium Tests.
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
#      (similarly to what you do in a new OJS installation). In most
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
#
# 4) Don't forget to start your local selenium server before executing functional tests, i.e.:
#
#    > java -jar selenium-server.jar -browserSessionReuse


# Identify the tests directory.
TESTS_DIR=`dirname "$0"`
TESTS_DIR=`readlink -f "$TESTS_DIR/../lib/pkp/tests"`

# Shortcuts to the test environments.
TEST_CONF1="--configuration $TESTS_DIR/phpunit-env1.xml"
TEST_CONF2="--configuration $TESTS_DIR/phpunit-env2.xml"

### Command Line Options ###

# Run all types of tests by default, unless one or more is specified
DO_ALL=1

# Various types of tests
DO_PKP_CLASSES=0
DO_PKP_PLUGINS=0
DO_APP_CLASSES=0
DO_APP_PLUGINS=0
DO_APP_FUNCTIONAL=0

# Parse arguments
while getopts "CPcpf" opt; do
	case "$opt" in
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
	esac
done

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_PKP_CLASSES" -eq 1 \) ]; then
	phpunit $TEST_CONF1 lib/pkp/tests/classes
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_PKP_PLUGINS" -eq 1 \) ]; then
	phpunit $TEST_CONF2 lib/pkp/tests/plugins
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_CLASSES" -eq 1 \) ]; then
	phpunit $TEST_CONF1 tests/classes
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_PLUGINS" -eq 1 \) ]; then
	phpunit $TEST_CONF2 tests/plugins
fi

if [ \( "$DO_ALL" -eq 1 \) -o \( "$DO_APP_FUNCTIONAL" -eq 1 \) ]; then
	phpunit $TEST_CONF1 tests/functional
fi
