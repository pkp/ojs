#!/bin/bash

# This script is ensures that the embedded configuration works on *nix servers.
# Please execute it once after creating the 'sorl' and 'jetty' symlinks in
# 'plugins/generic/lucene/lib' as described in the README file that comes
# together with this plugin.
#
# Usage: chkconfig.sh

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

# (Re-)create links.
ln -sf  ../lib/solr/contrib/ "$PLUGIN_DIR/embedded/contrib"
ln -sf ../lib/solr/dist/ "$PLUGIN_DIR/embedded/dist"
ln -sf ../lib/solr/example/webapps/ "$PLUGIN_DIR/embedded/webapps"

ERROR=false

# Check availability of jetty.
if [ ! -e "$PLUGIN_DIR/lib/jetty/start.jar" ]; then
  echo "Jetty was not correctly installed. Please make sure that the jetty"
  echo "installation is available in"
  echo "'$PLUGIN_DIR/lib/jetty'."
  echo "This directory should contain the file 'start.jar'."
  ERROR=true
fi

# Check availability of solr.
if [ ! -e "$PLUGIN_DIR/embedded/webapps/solr.war" ]; then
  echo "Solr was not correctly installed. Please make sure that the solr"
  echo "installation is available in"
  echo "'$PLUGIN_DIR/lib/solr'."
  echo "The directory 'example/webapps' therein should contain the file"
  echo "'solr.war'."
  ERROR=true
fi

# If we got an error then let the user know what to do.
if [ "$ERROR" = "true" ]; then
  echo
  echo "Please correct the errors and then re-run this script."
else
  echo "Everything ok. You should be able to start Solr now."
fi