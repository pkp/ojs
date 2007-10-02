#!/bin/bash

#
# buildpkg.sh
#
# Copyright (c) 2003-2006 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> [<tag>]
#
# $Id$
#

CVSROOT=:pserver:anonymous@pkp.sfu.ca:/cvs
MODULE=ojs2
PRECOMPILE=1

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
dbscripts/xml/data/locale/te_ST					\
dbscripts/xml/data/sample.xml					\
docs/dev							\
docs/doxygen							\
lib/adodb/CHANGED_FILES						\
lib/adodb/diff							\
lib/smarty/CHANGED_FILES					\
lib/smarty/diff							\
locale/te_ST							\
cache/*.php							\
tools/buildpkg.sh						\
tools/cvs2cl.pl							\
tools/genLocaleReport.sh					\
tools/genTestLocale.php						\
tools/test"


cd $TMPDIR

echo -n "Exporting $MODULE with tag $TAG ... "
cvs -Q -d $CVSROOT export -r $TAG -d $BUILD $MODULE || exit 1
echo "Done"

cd $BUILD

echo -n "Preparing package ... "
cp config.TEMPLATE.inc.php config.inc.php
find . -name .cvsignore -exec rm {} \;
rm -r $EXCLUDE
echo "Done"

if [ ! -z "$PRECOMPILE" ]; then
	echo -n "Precompiling templates and cache files ... "
	php tools/preCompile.php
	echo "Done"
fi

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
rm -r $TMPDIR
