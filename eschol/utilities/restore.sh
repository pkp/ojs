#!/usr/bin/env bash

echo "Restoring database from backup file."
./singleToMultiTrans.rb ~/ojs/db_backup/ojs_db_dump.sql | mysql --defaults-extra-file=~/.passwords/ojs_dba_pw.mysql 
