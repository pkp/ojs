#!/bin/bash -e

# This script is used to pull article metadata XML files from
# OJS installations. You should schedule it via cronjob. We
# recommend running this script once a day during off-hours.
#
# Usage:
#   pull.sh [path/to/pull.conf]
#
# If no configuration file is given then the file will use
# the lucene plugin's default configuration file:
# "plugins/generic/lucene/embedded/etc/pull.conf". For more
# information see the inline comments in the default configuration
# file.

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

# Identify the configuration file.
if [ -z "$1" ]; then
  CONFIG_FILE="$PLUGIN_DIR/embedded/etc/pull.conf"
else
  CONFIG_FILE="$1"
fi

# If no configuration file can be found then exit.
if [ ! -r "$CONFIG_FILE" ]; then
  echo "Configuration file '$CONFIG_FILE' not found."
  exit 1
fi

# Get the configuration.
source "$CONFIG_FILE"

# Make sure that the configured staging directory
# exists or can be created.
if [ ! -e "$PULL_STAGING_DIR" ]; then
  # Try to create the staging directory.
  if mkdir -p "$PULL_STAGING_DIR" >/dev/null 2>&1 ; then
    true;
  else
    echo "The staging directory '$PULL_STAGING_DIR' is missing"
    echo "and its parent directory is not writable so that we cannot create it."
    exit 1
  fi
fi

# Make sure that the staging directory is writable.
if [ ! -w "$PULL_STAGING_DIR" ]; then
  echo "The staging directory '$PULL_STAGING_DIR' is not writable"
  exit 1
fi

# Make sure we have a valid endpoint URLs.
PULL_URLS=`echo $PULL_ENDPOINTS | sed 's/|/ /g'`
for PULL_URL in $PULL_URLS; do
  if echo "$PULL_URL" | egrep -i "$URL_PATTERN" >/dev/null; then
    true;
  else
    echo "Invalid pull endpoint URL '$PULL_URL' found in configuration"
    echo "file '$CONFIG_FILE'."
    echo "Please configure a valid URL."
    exit 1
  fi
done

for PULL_URL in $PULL_URLS; do
  echo "Accessing '$PULL_URL':"
  TIMESTAMP=`date '+%Y%m%d%H%M%S'`
  PREFIX=`echo $PULL_URL | sed -r 's%^https?://%%;s%/index.php%%;s%[/.:]%-%g'`
  HAS_MORE=yes
  COUNTER=0
  while [ "$HAS_MORE" = yes ]; do
    SUFFIX=`printf %03d $COUNTER`
    FILENAME="$PULL_STAGING_DIR/$PREFIX-$TIMESTAMP-$SUFFIX.xml"
    curl -s "$PULL_URL/index/lucene/pullChangedArticles" >"$FILENAME"
    echo " - pulling '$FILENAME'"
    HAS_MORE=`cat "$FILENAME" | egrep '<articleList[^>]* hasMore="(yes|no)"' | sed -r 's/^.*hasMore="(yes|no)".*$/\1/'`
    let COUNTER=COUNTER+1
  done
  echo " - $COUNTER file(s) pulled from '$PULL_URL'"
  echo
done
