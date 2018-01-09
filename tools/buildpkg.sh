#!/bin/bash

#
# tools/buildpkg.sh
#
# Copyright (c) 2013-2018 Simon Fraser University
# Copyright (c) 2003-2018 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> <tag-branch> <patch_dir>
#  <version>: The version of OJS to release (e.g. 2.4.5 or 2.4.5-1)
#  <tag-branch>: The tag or branch to use to build the package
#  <patch-dir>: A directory containg .tar.gz files of previous releases to create patches from
#

GITREP=git://github.com/pkp/ojs.git

if [ -z "$3" ]; then
	echo "Usage: $0 <version> <tag>-<branch> <patch_dir>";
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
plugins/importexport/duracloud/lib/DuraCloud-PHP/.git		\
tests								\
tools/buildpkg.sh						\
tools/genLocaleReport.sh					\
tools/genTestLocale.php						\
tools/startSubmodulesTRAVIS.sh					\
tools/test							\
lib/pkp/tests							\
.git								\
.travis.yml							\
lib/pkp/.git							\
lib/pkp/tools/travis						\
lib/pkp/tools/mergePullRequest.sh				\
lib/password_compat/.git					\
lib/pkp/lib/swordappv2/.git					\
lib/pkp/lib/swordappv2/test					\
plugins/reports/counter/classes/COUNTER/.git			\
plugins/generic/pdfJsViewer/.git"


cd $TMPDIR

echo -n "Cloning $GITREP and checking out tag $TAG ... "
git clone -b $TAG --depth 1 -q -n $GITREP $BUILD || exit 1
cd $BUILD
git checkout -q $TAG || exit 1
echo "Done"

echo -n "Checking out submodules ... "
git submodule -q update --init --recursive >/dev/null || exit 1
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
	for FILE in $PATCHDIR/*.tar.gz; do
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
