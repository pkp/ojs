## OJS(OPEN JOURNALS SYSTEM) Framework

    ===================================
	=== Open Journal Systems
	=== The Public Knowledge Project
	=== Version: 3.2.1.4
	=== GIT tag: stable-3_2_1
	===================================

Open Journal Systems (OJS) is a journal management and publishing system that has been developed by the [Public Knowledge Project](https://pkp.sfu.ca/) through its federally funded efforts to expand and improve access to research.

The images in this repository are built on top of [Alpine Linux](https://alpinelinux.org/) and come in several variants. We are currently using version 3.2.1.4

## Installation

For CDLI version, the docker version of config file has been made.

### 1. Running using the docker container 

OJS Setup is a three step process:

# STEP-1: 
To clone ojs submodules:-
 
1.1 Shift to the ojs directory:
            
        cd framework/app/tools/ojs
             
1.2 Initialize your local configuration file

        git submodule init
	
1.3 Fetch all the data from that submodules

        git submodule update

# STEP-2:
To build ojs container:-

    ```
        ./cdlidev -- build ojs
    ```
 
# STEP-3;
The ojs database is imported directly in phpmyadmin using the volume 

./conf/init:/docker-entrypoint-initdb.d/:ro

Alternative way is to the import the ojs database manually.

RUN the framework using following command 

        ./dev/cdlidev.py up

If it runs fine, you will be having the ojs framework running at http://127.0.0.1:8081

### 2. Running independently without docker

Checkout submodules and copy default configuration :

    git submodule update --init --recursive
    cp config.TEMPLATE.inc.php config.inc.php

Install or update dependencies via Composer (https://getcomposer.org/):

  Install composer 

    php7 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php7 composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
   
  Update dependencies via Composer 
    
    composer --working-dir=lib/pkp install
    composer --working-dir=plugins/paymethod/paypal install
    composer --working-dir=plugins/generic/citationStyleLanguage install

Install or update dependencies via [NPM](https://www.npmjs.com/):

  # install [nodejs](https://nodejs.org/en/) if you don't already have it
    npm install
    npm run build

If your PHP version supports built-in development server :

    php -S localhost:8000

See the [Documentation Hub][doc-hub] for a more complete development guide.

## Environment Variables

The image understand the following environment variables:

| NAME            | Default   | Info                 |
|:---------------:|:---------:|:---------------------|
| SERVERNAME      | localhost | Used to generate httpd.conf and certificate            |
| OJS_CLI_INSTALL | 1         | Used to install ojs automatically when start container |
| OJS_DB_HOST     | mariadb   | Database host        |
| OJS_DB_USER     | root      | Database             |
| OJS_DB_PASSWORD |           | Database password    |
| OJS_DB_NAME     | ojs_db    | Database name        |

## Running Tests

We recommend using [Travis](https://travis-ci.org/) for continuous-integration
based testing. Review the Travis configuration file (`.travis.yml`) as a
reference for running the test locally, should you choose to do so.

The tests include an integration test suite that builds a data environment from
scratch, including the installation process. (This is the `-b` flag to the test
script `lib/pkp/tools/runAllTests.sh`; this is also executed in the Travis
environment.)

## Bugs / Issues

See https://github.com/pkp/pkp-lib/#issues for information on reporting issues.

## License

This software is released under the the [GNU General Public License][gpl-licence].

See the file [COPYING][gpl-licence] included with this distribution for the terms
of this license.

Third parties are welcome to modify and redistribute OJS in entirety or parts
according to the terms of this license. PKP also welcomes patches for
improvements or bug fixes to the software.

[pkp]: https://pkp.sfu.ca/
[readme]: docs/README.md
[doc-hub]: https://docs.pkp.sfu.ca/
[php-unit]: https://phpunit.de/
[gpl-licence]: docs/COPYING
