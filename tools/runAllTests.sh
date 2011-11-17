#!/bin/bash

export PKP_MOCK_ENV=lib/pkp/tests/mock
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/functional
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/classes

export PKP_MOCK_ENV=lib/pkp/tests/mock2
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php tests/plugins
phpunit --testdox-text lib/pkp/tests/results/testdox.txt --bootstrap lib/pkp/tests/phpunit-bootstrap.php lib/pkp/tests/plugins