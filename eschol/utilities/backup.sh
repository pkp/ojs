#!/usr/bin/env bash

set -e

# Grab the database information
set -e
DB_NAME=`egrep ^database ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
sed 's/mysql/mysqldump/' ~/.passwords/ojs_db_pw.mysql | egrep -v 'database=' > ~/.passwords/ojs_mysqldump.sql

echo "Dumping database to file."
mysqldump --defaults-file=~/.passwords/ojs_mysqldump.sql $DB_NAME --skip-lock-tables --skip-extended-insert > /apps/eschol/ojs/db_backup/ojs_db_dump.sql
rm ~/.passwords/ojs_mysqldump.sql
echo "Compressing."
gzip -c /apps/eschol/ojs/db_backup/ojs_db_dump.sql > /apps/eschol/ojs/db_backup/dump_`date "+%Y-%m-%dT%H:%M:%S"`.gz
echo "Removing old backups."
find /apps/eschol/ojs/db_backup -name 'dump_2*.gz' -mtime +45 -exec rm {} \;
