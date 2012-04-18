#!/usr/bin/env bash

function die {
  echo "Aborted."
  exit 1
}

# First, clear out the database if needed
echo -n "Clear database first? "
read RESP
if [ "$RESP" == "y" -o "$RESP" == "Y" ]; then
  (mysql --defaults-extra-file=~/.passwords/ojs_db_pw.mysql --batch --execute "show tables;" | egrep -v 'Tables_in' | awk '{printf("drop table %s;\n", $1)}' > /tmp/drop_cmds) || die
  cat /tmp/drop_cmds
  echo -n "Ready to clear? "
  read RESP
  if [ "$RESP" == "y" -o "$RESP" == "Y" ]; then
    (cat /tmp/drop_cmds | mysql --defaults-extra-file=~/.passwords/ojs_db_pw.mysql) || die
  else
    die
  fi
fi

# Grab the database information
DB_HOST=`egrep ^host ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PORT=`egrep ^port ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_USER=`egrep ^user ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PASS=`egrep ^password ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_NAME=`egrep ^database ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`

# Grab the admin password
ADMIN_PASS=`cat ~/.passwords/ojs_admin_pw.txt`

# Generate an "expect" script to drive the OJS command-line installation program
cat > /tmp/initdb_script.exp <<DONE
set timeout -1
spawn php -c /apps/subi/apache/conf/php.ini tools/install.php
match_max 100000
expect "Select (en_US): "
send -- "\r"
expect "Additional locales\r"
expect "Select (None): "
send -- "\r"
expect "Client character set\r"
expect "Select (utf-8): "
send -- "utf-8\r"
expect "Connection character set\r"
expect "Select (None): "
send -- "utf8\r"
expect "Database character set\r"
expect "Select (None): "
send -- "utf8\r"
expect "Directory for uploads: "
send -- "$HOME/ojs/files\r"
expect -exact "Do not create required subdirectories (only useful for a manual installation) \[y/N\] "
send -- "y\r"
expect "Password encryption algorithm\r"
expect "Select (md5): "
send -- "sha1\r"
expect "Administrator Account\r"
expect "Email Address/Username: "
send -- "ojsadmin@escholarship.org\r"
expect "Password: "
send -- "$ADMIN_PASS\r"
expect "Repeat password: "
send -- "$ADMIN_PASS\r"
expect "Email: "
send -- "ojsadmin@escholarship.org\r"
expect "Database driver\r"
expect "Select: "
send -- "mysql\r"
expect "Host (None): "
send -- "$DB_HOST:$DB_PORT\r"
expect "Username (None): "
send -- "$DB_USER\r"
expect "Password (None): "
send -- "$DB_PASS\r"
expect "Database name: "
send -- "$DB_NAME\r"
expect -exact "Create new database \[Y/n\] "
send -- "n\r"
expect "OAI repository identifier: "
send -- "eschol-oai\r"
expect -exact "Manual Install \[y/N\] "
send -- "n\r"
expect -exact "Install Open Journal Systems \[y/N\] "
send -- "y\r"
expect EOF
DONE

echo ""
echo "------------------ Running OJS installer ---------------------"
pushd ../..
perl -p -i.bak -e "s/installed = On/installed = Off/" config.inc.php
expect /tmp/initdb_script.exp || die
popd
rm /tmp/initdb_script.exp

#echo ""
#echo "------------------ Running upgrade tool --------------------"
#php -c /apps/subi/apache/conf/php.ini tools/upgrade.php upgrade || die

echo ""
echo "------------------ Inserting default users ---------------------"
(cat ~/.passwords/default_ojs_users.sql | mysql --defaults-extra-file=~/.passwords/ojs_db_pw.mysql) || die

echo ""
echo "------------------ Done ---------------------"
echo ""
echo "Done."

