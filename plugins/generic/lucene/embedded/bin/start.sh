#!/bin/bash

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
#JETTY_CONF="$DEPLOYMENT_DIR/etc/jetty-logging.xml $DEPLOYMENT_DIR/etc/jetty.xml"
JETTY_CONF="$DEPLOYMENT_DIR/etc/jetty.xml"

# The Jetty home directory
JETTY_HOME="$PLUGIN_DIR/lib/jetty"
JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.home=$JETTY_HOME"

# The Jetty log directory
JETTY_LOGS="$OJS_FILES/lucene/log"
if [ ! -d "$JETTY_LOGS" ]; then
  mkdir -p "$JETTY_LOGS"
fi
JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.logs=$JETTY_LOGS"

# solr home
SOLR_HOME="$DEPLOYMENT_DIR/solr"
JAVA_OPTIONS="$JAVA_OPTIONS -Dsolr.solr.home=$SOLR_HOME"

# solr index data directory
SOLR_DATA="$OJS_FILES/lucene/data"
if [ ! -d "$SOLR_DATA" ]; then
  mkdir "$SOLR_DATA"
fi
JAVA_OPTIONS="$JAVA_OPTIONS -Dsolr.data.dir=$SOLR_DATA"

# Logging configuration
JAVA_OPTIONS="$JAVA_OPTIONS -Djava.util.logging.config.file=$DEPLOYMENT_DIR/etc/logging.properties"

# The system's temporary directory
if [ -z "$TMP" ]; then
  TMP=/tmp
fi
JAVA_OPTIONS="$JAVA_OPTIONS -Djava.io.tmpdir=$TMP"

java $JAVA_OPTIONS -jar "$JETTY_HOME/start.jar" $JETTY_CONF >>$OJS_FILES/lucene/solr-java.log 2>&1 &

# Remember the PID of the process we just started.
SOLR_PID=$!
echo $SOLR_PID>$SOLR_PIDFILE

echo "Started solr."
