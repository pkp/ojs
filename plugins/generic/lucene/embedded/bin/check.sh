#!/bin/bash

# Execute this script on *nix systems to check whether the solr server is
# running. Please read the README file that comes with this plugin first to
# understand how to install and configure Solr.
#
# Usage: check.sh [UID]
#
# Options:
#    UID: If given, the running server's UID will be compared to the given UID.
#         When the two are different, then the script will exit with a nonzero
#         return code.

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

# If we don't find a PID-file we assume that the server is stopped.
if [ ! -e $SOLR_PIDFILE ]; then
  echo 'Server is stopped (no PID file found).'
  exit 1
fi

SOLR_PID=`cat $SOLR_PIDFILE`

# Check whether we got a PID at all.
if [ -z "$SOLR_PID" ]; then
  echo 'Server is stopped (no PID found in PID file).'
  exit 1
fi

# Check the PID to find out whether the server is running.
if [ -e "/proc/$SOLR_PID" ]; then
  # Check whether the UID of the process is the one given as
  # an argument.
  if [ -n "$1" ]; then
    # Get the UID of the process.
    SOLR_UID=`ps --no-heading -o uid $SOLR_PID | sed 's/^ *//'`
    if [ "$SOLR_UID" -ne "$1" ]; then
      echo "Server is running under UID $SOLR_UID and not under UID $1 as requested."
      exit 1
    fi
  fi
  echo 'Server is running.'
  exit 0
else
  echo 'Server is stopped (last PID no longer active).'
  exit 1
fi
