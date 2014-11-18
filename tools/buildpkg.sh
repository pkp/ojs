#!/bin/bash

#
# buildpkg.sh
#
# Copyright (c) 2014 Simon Fraser University Library
# Copyright (c) 2003-2014 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> [<tag>] [<patch_dir>]
#
#

GITREP=git://github.com/pkp/ojs.git

if [ -z "$1" ]; then
	echo "Usage: $0 <version> [<tag>-<branch>] [<patch_dir>]";
	exit 1;
fi

VERSION=$1
TAG=$2
PATCHDIR=${3-}
PREFIX=ojs
BUILD=$PREFIX-$VERSION
TMPDIR=`mktemp -d $PREFIX.XXXXXX` || exit 1

EXCLUDE="dbscripts/xml/data/locale/en_US/sample.xml		\
dbscripts/xml/data/sample.xml					\
docs/dev							\
locale/te_ST							\
tests								\
tools/buildpkg.sh						\
tools/genLocaleReport.sh					\
tools/genTestLocale.php						\
tools/test							\
lib/pkp/tools/travis						\
lib/pkp/plugins/*/*/tests					\
plugins/*/*/tests						\
tests								\
lib/pkp/tests							\
.git								\
.openshift							\
lib/pkp/.git							\
lib/pkp/js/lib/pnotify/build-tools				\
lib/pkp/js/lib/pnotify/includes					\
lib/pkp/lib/swordappv2/.git					\
lib/pkp/lib/swordappv2/test"


cd $TMPDIR

echo -n "Cloning $GITREP and checking out tag $TAG ... "
git clone -q -n $GITREP $BUILD || exit 1
cd $BUILD
git checkout -q $TAG || exit 1
echo "Done"

echo -n "Checking out corresponding submodule ... "
git submodule -q update --init >/dev/null || exit 1
echo "Done"

echo -n "Checking out submodule submodules ... "
cd lib/pkp
git submodule -q update --init >/dev/null || exit 1
cd ../..
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

if [ ! -z "$PATCHDIR" ]; then
	echo "Creating patches in $BUILD.patch ..."
	[ -e "../${BUILD}.patch" ] || mkdir "../$BUILD.patch"
	for FILE in $PATCHDIR/*; do
		OLDBUILD=$(basename $FILE)
		OLDVERSION=${OLDBUILD/$PREFIX-/}
		OLDVERSION=${OLDVERSION/.tar.gz/}
		echo -n "Creating patch against ${OLDVERSION} ... "
		tar -zxf $FILE
		diff -urN $PREFIX-$OLDVERSION $BUILD | gzip -c > ../${BUILD}.patch/$PREFIX-${OLDVERSION}_to_${VERSION}.patch.gz
		echo "Done"
	done
	echo "Done"
fi

cd ..

rm -r $TMPDIR
