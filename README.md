# Open Journal Systems

> Open Journal Systems (OJS) has been developed by the Public Knowledge Project. For general information about OJS and other open research systems, visit the [PKP web site][pkp].

[![Build Status](https://travis-ci.org/pkp/ojs.svg?branch=master)](https://travis-ci.org/pkp/ojs)

## Documentation

You will find detailed guides in [docs](docs) folder.

## Using Git development source

Checkout submodules and copy default configuration :

    git submodule update --init --recursive
    cp config.TEMPLATE.inc.php config.inc.php

If your PHP version supports built-in development server :

    php -S localhost:8000

See [Wiki][wiki-dev] for more complete development guide.

## Running Tests

Specific requirements : [PHPunit][php-unit]. Installing with pear for example :

    pear channel-discover pear.phpunit.de
    pear install phpunit/PHPUnit

Setting up test environment :

    mysql -u root -e 'create database ojs_test'
    mysql -u root ojs_test < tests/functional/testserver.sql
    mv config.TRAVIS.inc.php config.inc.php

Running the tests :

    ./lib/pkp/tools/runAllTests.sh -C

## License

This software is released under the the [GNU General Public License][gpl-licence].

See the file [COPYING][gpl-licence] included with this distribution for the terms
of this license.

Third parties are welcome to modify and redistribute OJS in entirety or parts
according to the terms of this license. PKP also welcomes patches for
improvements or bug fixes to the software.

[pkp]: http://pkp.sfu.ca/
[readme]: docs/README
[wiki-dev]: http://pkp.sfu.ca/wiki/index.php/HOW-TO_check_out_PKP_applications_from_git
[php-unit]: http://phpunit.de/
[gpl-licence]: docs/COPYING
