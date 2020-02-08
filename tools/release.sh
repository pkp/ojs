#!/bin/bash

#
# tools/release.sh
#
# Copyright (c) 2014-2020 Simon Fraser University
# Copyright (c) 2003-2020 John Willinsky
# Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
#
# Script to create an automated (incremental) release of OJS.
#
# Usage: release.sh <stable-branch> <distrib dir> <github access token>
#  <stable_branch>: the stable branch to release from (e.g. ojs-stable-2_4_5)
#  <distrib dir>: a directory containing prior tarballs to use in generating
#    patches
#  <github access token>: an API token from
#    https://github.com/settings/applications
#
# NOTE: This script CANNOT currently be used to release a first build of a
# revision (i.e. a -0 build). It can only be used to release -1 and subsequent
# builds of a revision.

# Fail on first error
set -e

# Check for proper usage
if [ -z "$3" ]; then
	echo "Usage: $0 <stable-branch> <distrib dir> <github access token>";
	exit 1;
fi
BRANCH=$1
DISTRIBDIR=$2
ACCESSTOKEN=$3

# Make sure we're at the head of the stable branch
git checkout ${BRANCH}
git pull
cd lib/pkp
git checkout ${BRANCH}
git pull
cd ../..

# Determine the tag of the last release on this branch (e.g. ojs-2_4_5-0)
LASTTAG=`git describe --tags --abbrev=0 $BRANCH`

# Parse the version number information from the tag
[[ $LASTTAG =~ ([a-z]+)-([0-9]+)_([0-9]+)_([0-9]+)-([0-9]+) ]] && APPLICATION="${BASH_REMATCH[1]}" && MAJOR="${BASH_REMATCH[2]}" && MINOR="${BASH_REMATCH[3]}" && REVISION="${BASH_REMATCH[4]}" && LASTBUILD="${BASH_REMATCH[5]}"
THISBUILD=$((LASTBUILD+1))

# Calculate the tag of the next release (e.g. ojs-2_4_5-1
THISTAG=`echo $LASTTAG | sed -r 's/(.*-)([0-9]+)/echo \1$((\2+1))/ge'`

# Other useful bits and pieces
BUILDDATE=`date +%Y-%m-%d`
APPLICATION_UPPER=`echo ${APPLICATION} | tr '[:lower:]' '[:upper:]'`

# Update the version descriptor
LASTPATCHSUFFIX="${MAJOR}.${MINOR}.${REVISION}"
[[ ${LASTBUILD} -ne "" ]] && LASTPATCHSUFFIX="${LASTPATCHSUFFIX}-${LASTBUILD}"
sed -i	-e "s/<tag>.*<\/tag>/<tag>${THISTAG}<\/tag>/" \
	-e "s/<release>.*<\/release>/<release>${MAJOR}.${MINOR}.${REVISION}.${THISBUILD}<\/release>/" \
	-e "s/<date>.*<\/date>/<date>${BUILDDATE}<\/date>/" \
	-e "s/<package>.*<\/package>/<package>http:\/\/pkp.sfu.ca\/${APPLICATION}\/download\/${APPLICATION}-${MAJOR}.${MINOR}.${REVISION}-${THISBUILD}.tar.gz<\/package>/" \
	-e "s/\(<patch .*_to_\).*\(\.patch\.gz.*\)/\1${MAJOR}.${MINOR}.${REVISION}-${THISBUILD}.patch.gz/" \
	dbscripts/xml/version.xml
git add dbscripts/xml/version.xml

# Update the upgrade and install descriptors
sed -i	-e "s/<install version=\".*\">/<install version=\"${MAJOR}.${MINOR}.${REVISION}.${THISBUILD}\">/" \
	dbscripts/xml/upgrade.xml dbscripts/xml/install.xml
git add dbscripts/xml/upgrade.xml dbscripts/xml/install.xml

# Update the README.md
sed -i	-e "s/=== Version: .*/=== Version: ${MAJOR}.${MINOR}.${REVISION}-${THISBUILD}/" \
	-e "s/=== GIT tag: .*/=== GIT tag: ${THISTAG}/" \
	-e "s/=== Release date: .*/=== Release date: ${BUILDDATE}/" \
	docs/README.md
git add docs/README.md

# Update the Doxygen config file
sed -i	-e "s/^\(PROJECT_NUMBER.*= \).*/\1${MAJOR}.${MINOR}.${REVISION}-${THISBUILD}/" \
	docs/dev/*.doxygen

# Generate the release notes
echo -n "Generating release notes"
echo "
Automated Build ${MAJOR}-${MINOR}-${REVISION}.${THISBUILD}
-----------------------
This automated build adds the following fixes to the base release of ${APPLICATION_UPPER} ${MAJOR}.${MINOR}.${REVISION}:
" >> docs/RELEASE

# Get the bug IDs and titles for issues referenced in this release
for ISSUENUM in `(git log --pretty=oneline ${LASTTAG}..HEAD && cd lib/pkp && git log --pretty=oneline ${LASTTAG}..HEAD) | sed -n -e "s/.*pkp\/pkp-lib#\([0-9]\+\).*/\1/p" | sort -n | uniq`; do
	ISSUETITLE=`wget --auth-no-challenge --user=${ACCESSTOKEN} --password=x-oauth-basic -q -O - "https://api.github.com/repos/pkp/pkp-lib/issues/${ISSUENUM}" | php -r "echo trim(json_decode(file_get_contents('php://stdin'))->title);"`
	echo "	#${ISSUENUM}: ${ISSUETITLE}" >> docs/RELEASE
	echo -n "." # Status
done
RELEASE_ALTCOPY="docs/release-notes/README-${MAJOR}.${MINOR}.${REVISION}"
cp docs/RELEASE ${RELEASE_ALTCOPY}
git add docs/RELEASE ${RELEASE_ALTCOPY}
echo " Done."

# Commit the last changes
git add lib/pkp
git commit -m "Automated commits for ${APPLICATION_UPPER} ${MAJOR}.${MINOR}.${REVISION}-${THISBUILD}"
cd lib/pkp
git tag ${THISTAG}
git push --tags
cd ../..
git tag ${THISTAG}
git push --tags

# Build the package
bash tools/buildpkg.sh ${MAJOR}.${MINOR}.${REVISION}-${THISBUILD} ${THISTAG} ${DISTRIBDIR}
