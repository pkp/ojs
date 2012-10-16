#!/bin/bash

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
  echo 'Server is running.'
  exit 0
else
  echo 'Server is stopped (last PID no longer active).'
  exit 1
fi
