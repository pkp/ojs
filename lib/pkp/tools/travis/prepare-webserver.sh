#!/bin/bash

# @file tools/travis/prepare-webserver.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to prepare the webserver for Travis testing.
#

set -xe

# Start apache and configure a virtual host.
sudo apt-get update > /dev/null
sudo apt-get install -y --force-yes apache2 php5-cgi libapache2-mod-fastcgi apache2-suexec-custom apache2-mpm-prefork php5-curl php5-mysql php5-pgsql php5-intl php5-xsl
sudo a2enmod actions fastcgi suexec

# Prepare FastCGI/suEXEC environment: Apache2 config for FastCGI/suEXEC
echo "Action application/x-httpd-php /cgi-bin/php.fcgi
SuexecUserGroup travis travis" | sudo tee /etc/apache2/sites-enabled/php-fcgi.conf

# Prepare FastCGI/suEXEC environment: FastCGI wrapper
mkdir cgi-bin
echo "#!/bin/sh
export PHP_FCGI_CHILDREN=4
export PHP_FCGI_MAX_REQUESTS=200
exec /usr/bin/php5-cgi" > cgi-bin/php.fcgi
chmod -R 755 cgi-bin

# Edit configuration files
sudo sed -i -e "s,#FastCgiWrapper /usr/lib/apache2/suexec,FastCgiWrapper /usr/lib/apache2/suexec,g" /etc/apache2/mods-enabled/fastcgi.conf
sudo sed -i -e "s,/var/www,$(pwd),g" /etc/apache2/sites-available/default
sudo sed -i -e "s,/usr/lib/cgi-bin,$(pwd)/cgi-bin,g" /etc/apache2/sites-available/default
sudo sed -i -e "s,\${APACHE_LOG_DIR},$(pwd),g" /etc/apache2/sites-available/default
sudo sed -i -e "s,/var/www,$(pwd)/,g" /etc/apache2/suexec/www-data

# Restart Apache2
sudo /etc/init.d/apache2 restart
