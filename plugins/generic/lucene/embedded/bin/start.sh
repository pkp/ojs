#!/bin/bash

# Identify our plug-in base directory.
PLUGIN_DIR=`dirname "$0"`
PLUGIN_DIR=`readlink -f "$PLUGIN_DIR/../.."`

# OJS directories
OJS_DIR=`readlink -f "$PLUGIN_DIR/../../.."`
OJS_FILES=`grep '^[[:space:]]*files_dir[[:space:]]*=' "$OJS_DIR/config.inc.php" | sed 's/^[[:space:]]*files_dir[[:space:]]*=\s*//;s/\s*$//'`
if [ ! \( -d "$OJS_FILES" -a -w "$OJS_FILES" \) ]; then
  echo "We did not find the location of the OJS files directory or the files directory is not writable."
  exit 1
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

java $JAVA_OPTIONS -jar "$JETTY_HOME/start.jar" $JETTY_CONF
