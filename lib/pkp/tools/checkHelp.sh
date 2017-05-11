#!/bin/bash

# @file tools/checkHelp.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to check help file mappings from code to Markdown.
#
# Usage: lib/pkp/tools/checkHelp.sh
#

# Look for help filenames referred to in templates and check that they all exist (in English)
ERRORS=0
for filename in `find . -name \*.tpl -exec sed -n -e "s/.*{help[^}]file=\"\([^\"#]\+\)[#\"].*/\1/p" "{}" ";"`; do
	if [ ! -f docs/manual/en/$filename ]; then
		echo "Help file \"$filename\" referred to in template does not exist!"
		ERRORS=1
	fi
done
if [ $ERRORS -ne 0 ]; then
	exit -1
fi

# Generate a quick report of the differences between the files listed in templates and the available files.
find . -name \*.tpl -exec sed -n -e "s/.*{help[^}]file=\"\([^\"]\+\)\".*/\1/p" "{}" ";" | sort | uniq > /tmp/template-help-references.txt
cat docs/manual/en/SUMMARY.md | sed -n -e "s/.*(\([^)]\+\))/\1/p" | sort | uniq > /tmp/help-files.txt
echo "Unreferenced help files:"
diff /tmp/template-help-references.txt /tmp/help-files.txt | grep -e "^>"

# Successful completion
exit 0
