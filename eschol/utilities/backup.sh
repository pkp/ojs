#!/usr/bin/env bash

set -e

# Grab the database information
set -e
DB_HOST=`egrep ^host ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PORT=`egrep ^port ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_USER=`egrep ^user ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PASS=`egrep ^password ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_NAME=`egrep ^database ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`

echo "Dumping database to file."
mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER --password=$DB_PASS $DB_NAME --skip-lock-tables --skip-extended-insert > /apps/eschol/ojs/db_backup/ojs_db_dump.sql
echo "Adding/removing files for hg."
hg -R /apps/eschol/ojs addremove
echo "Committing to hg."
hg -R /apps/eschol/ojs commit -m "Auto-commit"
