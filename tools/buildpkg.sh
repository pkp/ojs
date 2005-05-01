#!/bin/bash

#
# buildpkg.sh
#
# Copyright (c) 2005 The Public Knowledge Project
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to create an OJS package for distribution.
#
# Usage: buildpkg.sh <version> [<tag>]
#
# $Id$
#

CVSROOT=:pserver:anonymous@research2.csci.educ.ubc.ca:/cvs
MODULE=ojs2
PRECOMPILE=1

if [ -z "$1" ]; then
	echo "Usage: $0 <version> [<tag>]";
	exit 1;
fi

VERSION=$1
TAG=${2-HEAD}
BUILD=ojs-$VERSION
TMPDIR=`mktemp -d ojs.XXXXXX` || exit 1

EXCLUDE="dbscripts/xml/data/locale/en_US/sample.xml		\
dbscripts/xml/data/locale/te_ST					\
dbscripts/xml/data/sample.xml					\
docs/dev							\
lib/adodb/CHANGED_FILES						\
lib/adodb/diff							\
lib/smarty/CHANGED_FILES					\
lib/smarty/diff							\
locale/te_ST							\
tools/buildpkg.sh						\
tools/cvs2cl.pl							\
tools/genLocaleReport.sh					\
tools/genTestLocale.php"


cd $TMPDIR

echo -n "Exporting $MODULE with tag $TAG ... "
cvs -Q -d $CVSROOT export -r $TAG -d $BUILD $MODULE || exit 1
echo "Done"

cd $BUILD

echo -n "Preparing package ... "
mv config.TEMPLATE.inc.php config.inc.php
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

cd ..
rm -r $TMPDIR
