#!/bin/bash -e

# This script is used to load article metadata XML files queued
# in the staging area. It polls the staging area and immediately
# loads any file appearing there. You should schedule it via cronjob.
# We recommend running this script at least every 15 minutes so that
# the lag between staging and loading meta-data is not too long.
#
# Usage:
#   load.sh [path/to/pull.conf]
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

# If the configured staging directory does not exist then
# exit.
if [ ! -r "$PULL_STAGING_DIR" ]; then
  echo "The staging directory '$PULL_STAGING_DIR' is not readable"
  exit 1
fi

# Make sure that the configured reject directory
# exists or can be created.
if [ ! -e "$PULL_REJECT_DIR" ]; then
  # Try to create the reject directory.
  if mkdir -p "$PULL_REJECT_DIR" >/dev/null 2>&1 ; then
    true;
  else
    echo "The reject directory '$PULL_REJECT_DIR' is missing"
    echo "and its parent directory is not writable so that we cannot create it."
    exit 1
  fi
fi

# Make sure that the reject directory is writable.
if [ ! -w "$PULL_REJECT_DIR" ]; then
  echo "The reject directory '$PULL_REJECT_DIR' is not writable"
  exit 1
fi

# Make sure that the configured archive directory
# exists or can be created.
if [ ! -e "$PULL_ARCHIVE_DIR" ]; then
  # Try to create the archive directory.
  if mkdir -p "$PULL_ARCHIVE_DIR" >/dev/null 2>&1 ; then
    true;
  else
    echo "The archive directory '$PULL_ARCHIVE_DIR' is missing"
    echo "and its parent directory is not writable so that we cannot create it."
    exit 1
  fi
fi

# Make sure that the archive directory is writable.
if [ ! -w "$PULL_ARCHIVE_DIR" ]; then
  echo "The archive directory '$PULL_ARCHIVE_DIR' is not writable"
  exit 1
fi

# List all files in the staging area. Files
# have to be listed in lexicographic order as load
# order matters. We use a filename pattern that should
# produce an order that corresponds to the exact order
# of download. We thereby implement a FIFO queue.
for FILENAME in `ls "$PULL_STAGING_DIR"`; do
  echo -n "Processing $FILENAME ... "
  
  # Get the full file name.
  FULL_FILENAME="$PULL_STAGING_DIR/$FILENAME"
  
  # Count the number of articles in the file.
  ARTICLES_FOUND=`sed 's%<article %\n<article %g' "$FULL_FILENAME" \
      | grep -c '<article '` || true
  
  if ARTICLES_PROCESSED=`curl -s -H 'Content-type:text/xml; charset=utf-8' --data-binary "@$FULL_FILENAME" \
      "$DIH_ENDPOINT?command=full-import&clean=false" \
      | grep -o '<str name="Total Documents Processed">[0-9]\+</str>' | grep -o '[0-9]\+'`; then
    true
  else
    echo "configured DIH endpoint not available ... terminating"
    exit 1
  fi
  echo -n "$ARTICLES_PROCESSED of $ARTICLES_FOUND articles processed ... "
  
  if [ "$ARTICLES_FOUND" = "$ARTICLES_PROCESSED" ]; then
    # Move the file to the arquive
    mv "$FULL_FILENAME" "$PULL_ARCHIVE_DIR"
    echo "file arquived."
  else
    # Reject the file
    mv "$FULL_FILENAME" "$PULL_REJECT_DIR"
    echo "file rejected."
  fi
done
