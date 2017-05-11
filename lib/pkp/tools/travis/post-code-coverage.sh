#!/bin/bash

# @file tools/travis/post-code-coverage.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to to merge code coverage reports and upload them to web server
#

set -e

COVERAGE_TMP=lib/pkp/tests/results/coverage-tmp
COVERAGE_HTML=lib/pkp/tests/results/coverage-html

# Merge coverage reports
COVERAGE_REPORTS=$(find $COVERAGE_TMP -name "coverage-*.php")
echo "Merging coverage reports $COVERAGE_REPORTS"
php lib/pkp/tests/mergeCoverageReportTool.php $COVERAGE_HTML $COVERAGE_REPORTS > /dev/null 2>&1

# Copy the coverage reports to pkp-www.lib.sfu.ca
if [[ -n "$COVERAGE_UPLOAD_SECRET" ]]; then
	echo "Uploading coverage reports to https://pkp.sfu.ca/test-coverage/${TRAVIS_REPO_SLUG}/${TRAVIS_BRANCH}"
	sudo apt-get install -y --force-yes sshpass
	export SSHPASS=$COVERAGE_UPLOAD_SECRET
	rsync -av --rsh='sshpass -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -l pkp_testing' $COVERAGE_HTML/ pkp-www.lib.sfu.ca:html/${TRAVIS_REPO_SLUG}/${TRAVIS_BRANCH}/
fi
