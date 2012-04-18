#!/usr/bin/env bash

# Grab the database information
DB_HOST=`egrep ^host ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PORT=`egrep ^port ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_USER=`egrep ^user ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PASS=`egrep ^password ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_NAME=`egrep ^database ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`

set -x
mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER --password=$DB_PASS $DB_NAME --skip-extended-insert > /apps/subi/ojs/db_backup/ojs_db_dump.sql
hg -R /apps/subi/ojs addremove
hg -R /apps/subi/ojs commit -m "Auto-commit"
