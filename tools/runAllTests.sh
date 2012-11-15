#!/bin/bash

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
#    - See plugins/generic/lucene/README to install the dependencies
#      required for an embedded Solr server. The Lucene/Solr tests
#      assume such a server to be present on the test machine. Also see
#      the "Troubleshooting" section there in case your tests fail.
#
#
# 4) Don't forget to start your local selenium server before executing functional tests, i.e.:
#
#    > java -jar selenium-server.jar -browserSessionReuse


export PKP_MOCK_ENV=env1
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/functional
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/classes
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/classes

export PKP_MOCK_ENV=env2
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/plugins
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/plugins
