#!/bin/bash

#
# buildpkg.sh
#
# Copyright (c) 2003-2010 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> [<tag>]
#
# $Id$
#

CVSROOT=:pserver:anonymous@lib-pkp.lib.sfu.ca:/cvs
OJSMODULE=ojs2
PKPMODULE=pkp

if [ -z "$1" ]; then
	echo "Usage: $0 <version> [<tag>] [<patch_dir>]";
	exit 1;
fi

VERSION=$1
TAG=${2-HEAD}
PATCHDIR=${3-}
PREFIX=ojs
BUILD=$PREFIX-$VERSION
TMPDIR=`mktemp -d $PREFIX.XXXXXX` || exit 1

EXCLUDE="dbscripts/xml/data/locale/en_US/sample.xml		\
dbscripts/xml/data/sample.xml					\
docs/dev							\
locale/te_ST							\
tools/buildpkg.sh						\
tools/genLocaleReport.sh					\
tools/genTestLocale.php						\
tools/test"


cd $TMPDIR

echo -n "Exporting $OJSMODULE with tag $TAG ... "
cvs -Q -d $CVSROOT export -r $TAG -d $BUILD $OJSMODULE || exit 1
echo "Done"

echo -n "Exporting $PKPMODULE with tag $TAG ... "
cvs -Q -d $CVSROOT export -r $TAG $PKPMODULE || exit 1
echo "Done"

if [ ! -d $BUILD/lib ]; then
	mkdir $BUILD/lib
fi

mv $PKPMODULE $BUILD/lib/$PKPMODULE

cd $BUILD

echo -n "Preparing package ... "
cp config.TEMPLATE.inc.php config.inc.php
find . -name .cvsignore -exec rm {} \;
rm -r $EXCLUDE
echo "Done"

cd ..

echo -n "Creating archive $BUILD.tar.gz ... "
tar -zcf ../$BUILD.tar.gz $BUILD
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

echo -n "Building doxygen documentation... "
doxygen docs/dev/ojs2.doxygen > /dev/null && cd docs/dev/doxygen && tar czf ../../../${BUILD}-doxygen.tar.gz html && cd ../../..

echo "Done"

rm -r $TMPDIR
