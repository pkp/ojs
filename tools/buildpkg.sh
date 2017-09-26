#!/bin/bash

#
# tools/buildpkg.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2003-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> [<tag>]
#
#

GITREP=git://github.com/pkp/ojs.git

if [ -z "$1" ]; then
	echo "Usage: $0 <version> [<tag>-<branch>]";
	exit 1;
fi

VERSION=$1
TAG=$2
PREFIX=ojs
BUILD=$PREFIX-$VERSION
TMPDIR=`mktemp -d $PREFIX.XXXXXX` || exit 1

EXCLUDE="dbscripts/xml/data/locale/en_US/sample.xml		\
dbscripts/xml/data/sample.xml					\
docs/dev							\
tests								\
tools/buildpkg.sh						\
tools/genLocaleReport.sh					\
tools/genTestLocale.php						\
tools/test							\
lib/pkp/tools/travis						\
lib/pkp/plugins/*/*/tests					\
plugins/*/*/tests						\
plugins/auth/ldap						\
plugins/generic/announcementFeed				\
plugins/generic/backup						\
plugins/generic/browse						\
plugins/generic/coins						\
plugins/generic/cookiesAlert					\
plugins/generic/counter						\
plugins/generic/customLocale					\
plugins/generic/driver						\
plugins/generic/externalFeed					\
plugins/generic/lucene						\
plugins/generic/openAIRE					\
plugins/generic/phpMyVisites					\
plugins/generic/recommendBySimilarity				\
plugins/generic/translator					\
plugins/importexport/sample					\
plugins/importexport/duracloud					\
plugins/reports/subscriptions					\
plugins/blocks/relatedItems					\
plugins/oaiMetadataFormats/jats					\
tests								\
lib/pkp/tests							\
.git								\
.openshift							\
.scrutinizer.yml						\
.travis.yml							\
lib/pkp/.git							\
lib/pkp/lib/components/*.js					\
lib/pkp/lib/components/*.css					\
lib/pkp/lib/vendor/components					\
lib/pkp/lib/vendor/composer					\
lib/pkp/lib/vendor/ezyang/htmlpurifier/art			\
lib/pkp/lib/vendor/ezyang/htmlpurifier/benchmarks		\
lib/pkp/lib/vendor/ezyang/htmlpurifier/configdog		\
lib/pkp/lib/vendor/ezyang/htmlpurifier/docs			\
lib/pkp/lib/vendor/ezyang/htmlpurifier/extras			\
lib/pkp/lib/vendor/ezyang/htmlpurifier/maintenance		\
lib/pkp/lib/vendor/ezyang/htmlpurifier/smoketests		\
lib/pkp/lib/vendor/ezyang/htmlpurifier/tests			\
lib/pkp/lib/vendor/kriswallsmith				\
lib/pkp/lib/vendor/leafo/lessphp/tests				\
lib/pkp/lib/vendor/leafo/lessphp/docs				\
lib/pkp/lib/vendor/moxiecode/plupload/examples			\
lib/pkp/lib/vendor/phpmailer/phpmailer/docs			\
lib/pkp/lib/vendor/phpmailer/phpmailer/examples			\
lib/pkp/lib/vendor/phpmailer/phpmailer/test			\
lib/pkp/lib/vendor/robloach					\
lib/pkp/lib/vendor/smarty/smarty/demo				\
lib/pkp/lib/vendor/symfony					\
lib/pkp/lib/vendor/phpunit					\
lib/pkp/lib/vendor/phpspec					\
lib/pkp/lib/vendor/phpdocumentor				\
lib/pkp/lib/vendor/doctrine					\
lib/pkp/js/lib/pnotify/build-tools				\
lib/pkp/lib/vendor/sebastian					\
lib/pkp/lib/vendor/oyejorge/less.php/test			\
lib/pkp/lib/vendor/webmozart					\
lib/pkp/tools/travis						\
lib/pkp/lib/swordappv2/.git					\
lib/pkp/lib/swordappv2/.git					\
lib/pkp/lib/swordappv2/test					\
node_modules      \
.babelrc          \
.editorconfig     \
.eslintignore     \
.eslintrc.js      \
.postcssrc.js     \
package.json      \
webpack.config.js \
lib/ui-library"


cd $TMPDIR

echo -n "Cloning $GITREP and checking out tag $TAG ... "
git clone -b $TAG --depth 1 -q -n $GITREP $BUILD || exit 1
cd $BUILD
git checkout -q $TAG || exit 1
echo "Done"

echo -n "Checking out corresponding submodules ... "
git submodule -q update --init --recursive >/dev/null || exit 1
echo "Done"

echo "Installing composer dependencies:"
echo -n " - lib/pkp ... "
cd lib/pkp
composer.phar update
cd ../..
echo "Done"

echo -n " - plugins/paymethod/paypal ... "
cd plugins/paymethod/paypal
composer.phar install
cd ../../..
echo "Done"

echo -n "Installing node dependencies... "
npm install
echo "Done"

echo -n "Running webpack build process... "
npm run build
echo "Done"

echo -n "Preparing package ... "
cp config.TEMPLATE.inc.php config.inc.php
find . \( -name .gitignore -o -name .gitmodules -o -name .keepme \) -exec rm '{}' \;
rm -rf $EXCLUDE
echo "Done"

cd ..

echo -n "Creating archive $BUILD.tar.gz ... "
tar -zhcf ../$BUILD.tar.gz $BUILD
echo "Done"

cd ..

rm -r $TMPDIR
