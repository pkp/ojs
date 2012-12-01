#!/bin/bash

# Execute this script on *nix systems to stop the solr server. Please read
# the README file that comes with this plugin first to understand how to install
# and configure Solr. You'll find usage examples there, too.
#
# Usage: stop.sh

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

if [ ! -e $SOLR_PIDFILE ]; then
  echo "Solr PID-file not found. Is Solr stopped? Has the PID-file been deleted?"
  exit 1
fi

# Stop the solr process.
SOLR_PID=`cat $SOLR_PIDFILE`
if [ ! -z "$SOLR_PID" -a -e "/proc/$SOLR_PID" ]; then
  kill $SOLR_PID
  echo "Stopped solr."
else
  echo "Solr not running."
  exit 1
fi
