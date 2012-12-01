#!/bin/bash

# Execute this script on *nix systems to start the solr server. Please read
# the README file that comes with this plugin first to understand how to install
# and configure Solr. You'll find usage examples there, too.
#
# Usage: start.sh

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

# Check whether solr is already running.
if [ -e $SOLR_PIDFILE ]; then
  SOLR_PID=`cat $SOLR_PIDFILE`
  if [ ! -z "$SOLR_PID" -a -e "/proc/$SOLR_PID" ]; then
    echo "Solr is already running!"
    exit 1
  fi
fi

# The deployment directory
DEPLOYMENT_DIR="$PLUGIN_DIR/embedded"
JAVA_OPTIONS="-Dsolr.deployment=$DEPLOYMENT_DIR"

# Jetty configuration
JETTY_CONF="$DEPLOYMENT_DIR/etc/jetty.xml"
# Use the following line instead if you want extra logging.
#JETTY_CONF="$DEPLOYMENT_DIR/etc/jetty-logging.xml $DEPLOYMENT_DIR/etc/jetty.xml"

# The Jetty home directory
JETTY_HOME="$PLUGIN_DIR/lib/jetty"
JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.home=$JETTY_HOME"

# Solr home
SOLR_HOME="$DEPLOYMENT_DIR/solr"
JAVA_OPTIONS="$JAVA_OPTIONS -Dsolr.solr.home=$SOLR_HOME"

# Solr index data directory
SOLR_DATA="$LUCENE_FILES/data"
if [ ! -d "$SOLR_DATA" ]; then
  mkdir "$SOLR_DATA"
fi
JAVA_OPTIONS="$JAVA_OPTIONS -Dsolr.data.dir=$SOLR_DATA"

# Logging configuration
JAVA_OPTIONS="$JAVA_OPTIONS -Djava.util.logging.config.file=$DEPLOYMENT_DIR/etc/logging.properties -Djetty.logs=$LUCENE_FILES"

# The system's temporary directory
if [ -z "$TMP" ]; then
  TMP=/tmp
fi
JAVA_OPTIONS="$JAVA_OPTIONS -Djava.io.tmpdir=$TMP"

java $JAVA_OPTIONS -jar "$JETTY_HOME/start.jar" $JETTY_CONF >>$LUCENE_FILES/solr-java.log 2>&1 &

# Remember the PID of the process we just started.
SOLR_PID=$!
echo $SOLR_PID>$SOLR_PIDFILE

echo "Started solr."
