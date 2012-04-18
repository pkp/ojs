#!/usr/bin/env bash

# Grab the database information
DB_HOST=`egrep ^host ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PORT=`egrep ^port ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_USER=`egrep ^user ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_PASS=`egrep ^password ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`
DB_NAME=`egrep ^database ~/.passwords/ojs_db_pw.mysql | sed 's/.*=//'`

rm -f raw_dump.sql db_structure.sql db_autopop.sql db_customizations.sql db_data.sql tmp1 tmp2

set -x
mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER --password=$DB_PASS $DB_NAME --skip-extended-insert --skip-add-drop-table --skip-add-locks --skip-disable-keys --skip-comments --skip-set-charset > raw_dump.sql

sed 's/ AUTO_INCREMENT=[0-9]*//' raw_dump.sql | egrep -v '^$|character_set_client' > tmp1
egrep -v 'INSERT INTO|bepress_reviewers' tmp1 > db_structure.sql

DATA_TABLES="access_keys|article.*|authors|author_settings|bepress_reviewers.*|completed_payments|controlled_vocab.*|custom_.*_orders|edit_assignments|edit_decisions|email_templates|email_templates_data|issues|issue_settings|journals|journal_settings|notification_settings|notifications|published_articles|queued_payments|review_assignments|review_form.*|review_rounds|roles|rt_contexts|rt_.*|scheduled_tasks|section_editors|section_settings|sections|sessions|signoffs|subscriptions|subscription_type.*|temporary_files|user_settings|users"
fgrep 'INSERT INTO' tmp1 | egrep -v "INSERT INTO \`($DATA_TABLES)\`" > tmp2
AUTOPOP_TABLES="email_templates.*|filters|filter_settings|site|plugins|plugin_settings|versions"
egrep "INSERT INTO \`($AUTOPOP_TABLES)\`" tmp2 > db_autopop.sql
egrep "INSERT INTO \`site_settings\`" tmp2 | egrep "'(title|contactEmail|contactName)'" >> db_autopop.sql
egrep -v "INSERT INTO \`($AUTOPOP_TABLES|site_settings)\`" tmp2 > db_customizations.sql
egrep "INSERT INTO \`site_settings\`" tmp2 | egrep -v "'(title|contactEmail|contactName)'" >> db_customizations.sql
fgrep 'INSERT INTO' tmp1 | egrep "INSERT INTO \`($DATA_TABLES)\`" > db_data.sql

rm -f tmp1 tmp2

