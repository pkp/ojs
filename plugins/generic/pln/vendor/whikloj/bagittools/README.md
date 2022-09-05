# BagItTools

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg?style=flat-square)](https://php.net/)
[![Github Actions](https://github.com/whikloj/BagItTools/workflows/Build/badge.svg?branch=main)](https://github.com/whikloj/BagItTools/actions?query=workflow%3A%22Build%22+branch%3Amain)
[![LICENSE](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](./LICENSE)
[![codecov](https://codecov.io/gh/whikloj/BagItTools/branch/main/graph/badge.svg)](https://codecov.io/gh/whikloj/BagItTools)

## Introduction

BagItTools is a PHP implementation of the BagIt v1.0 specification ([RFC-8493](https://tools.ietf.org/html/rfc8493)).

Features:

* Create new bag
* Load existing directory as a bag.
* Load archive file (*.zip, *.tar, *.tar.gz, *.tgz, *.tar.bz2)
* Validate a bag
* Add/Remove files
* Add/Remove fetch urls
* Add/Remove hash algorithms (md5, sha1, sha224, sha256, sha384, sha512, sha3-224, sha3-256, sha3-384, sha3-512)
* Generate payload for all data/ files for all hash algorithms (depending on PHP support)
* Generate tag manifests for all root level files and any additional tag directories/files.
* Add/Remove tags from bag-info.txt files, maintains ordering of tags loaded.
* Generates/updates payload-oxum and bagging-date.
* Passes all bagit-conformance-suite tests.
* Create an archive (zip, tar, tar.gz, tgz, tar.bz2)
* In-place upgrade of bag from v0.97 to v1.0

## Installation

**Composer**

```bash
composer require "whikloj/bagittools"
```

**Clone from Github**

```bash
git clone https://github.com/whikloj/BagItTools
cd BagItTools
composer install --no-dev
```

## Dependencies

All dependencies are installed or identified by composer. 

Some PHP extensions are required and this library will not install if they cannot be found in the default PHP installation (the one used by composer).

The required extensions are:

* [Client URL Library](https://www.php.net/manual/en/book.curl.php)
* [Internationalization functions](https://www.php.net/manual/en/book.intl.php)
* [Multibyte string](https://www.php.net/manual/en/book.mbstring.php)
* [Zip](https://www.php.net/manual/en/book.zip.php)

## Usage

You can integrate BagItTools into your own code as a library using the [API](#api), or use the CLI commands for 
some simple functionality.

### Command line

#### Validating a bag

```bash
./bin/console validate <path to bag>
```

This will output a message as to whether the bag is or is NOT valid. It will also respond with
an appropriate exit code (0 == valid, 1 == invalid).

If you add the `-v` flag it will also print any errors or warnings.

This can command can be used with the [bagit-conformance-suite](https://github.com/LibraryOfCongress/bagit-conformance-suite)
like this

```bash
./test-harness <path to BagItTools>/bin/console -- -v validate
```

### API 

[API Documentation](https://whikloj.github.io/BagItTools/namespaces/whikloj.html)

#### Create a new bag

As this is a v1.0 implementation, by default bags created use the UTF-8 file encoding and the SHA-512 hash algorithm.

```php

require_once './vendor/autoload.php';

use \whikloj\BagItTools\Bag;

$dir = "./newbag";

// Create new bag as directory $dir
$bag = Bag::create($dir);

// Add a file
$bag->addFile('../README.md', 'data/documentation/myreadme.md');

// Add another algorithm
$bag->addAlgorithm('sha1');

// Add a fetch url
$bag->addFetchFile('http://www.google.ca', 'data/mywebsite.html');

// Add some bag-info tags
$bag->addBagInfoTag('Contact-Name', 'Jared Whiklo');
$bag->addBagInfoTag('CONTACT-NAME', 'Additional admins');

// Check for tags.
if ($bag->hasBagInfoTag('contact-name')) {

    // Get tags
    $tags = $bag->getBagInfoByTag('contact-name');
    
    var_dump($tags); // array(
                     //    'Jared Whiklo',
                     //    'Additional admins',
                     // )

    // Remove a specific tag value using array index from the above listing.
    $bag->removeBagInfoTagIndex('contact-name', 1); 
    
    // Get tags
    $tags = $bag->getBagInfoByTag('contact-name');
    
    var_dump($tags); // array(
                     //    'Jared Whiklo',
                     // )

    // Remove all values for the specified tag.
    $bag->removeBagInfoTag('contact-name');
}

// Write bagit support files (manifests, bag-info, etc)
$bag->update();

// Write the bag to the specified path and filename using the expected archiving method.
$bag->package('./archive.tar.bz2');

```

## Maintainer

[Jared Whiklo](https://github.com/whikloj)

## License

[MIT](./LICENSE)

## Development

To-Do:

* CLI interface to handle simple bag CRUD (CReate/Update/Delete) functions.
