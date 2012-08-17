#!/bin/bash

# Before executing tests for the first time please execute the
# following commands from the main ojs directory to install
# the default test environment.
# 
# NB: This will replace your database and files directory, so
# either use a separate OJS instance for testing or back-up
# your original database and files before you execute tests!
#
# > rm -r files
# > tar xzf tests/functional/files.tar.gz
# > chgrp -R www-data files                                   # exchange www-data for your web server's group
# > chmod -R g+w files
# > rm cache/*.php
# > mysql -u ... -p... ... <tests/functional/testserver.sql   # exchange ... for your database access data
#
# And don't forget to start your local selenium server, i.e.:
# > java -jar selenium-server.jar -browserSessionReuse

export PKP_MOCK_ENV=env1
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/functional
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/classes
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/classes

export PKP_MOCK_ENV=env2
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/plugins
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/plugins