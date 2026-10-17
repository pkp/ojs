#!/usr/bin/env bash
#
# tools/testServer.sh
#
# Copyright (c) 2014-2026 Simon Fraser University
# Copyright (c) 2003-2026 John Willinsky
# Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
#
# Start (or restart) the PHP built-in server for the Playwright e2e test install.
#
# The two environment variables below are the whole contract with the app:
#   PKP_CONFIG_FILE  selects config.test.inc.php instead of config.inc.php
#   TEST_API_KEY     enables and gates the /api/v1/_test/* namespace
#
# Both must reach PHP's process environment; `php -S` inherits it, which is why
# they are exported here rather than passed on a command line the server ignores.
#
# Usage: tools/testServer.sh [start|stop|restart] [port]

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ACTION="${1:-restart}"
PORT="${2:-${TEST_SERVER_PORT:-8000}}"
HOST="${TEST_SERVER_HOST:-127.0.0.1}"

export PKP_CONFIG_FILE="${PKP_CONFIG_FILE:-$APP_DIR/config.test.inc.php}"
export TEST_API_KEY="${TEST_API_KEY:-playwright-test-key}"

LOG_FILE="${TEST_SERVER_LOG:-$(dirname "$APP_DIR")/files-test/server.log}"

stop_server() {
    local pids
    pids="$(lsof -ti ":$PORT" || true)"
    if [ -n "$pids" ]; then
        echo "Stopping process(es) on port $PORT: $pids"
        kill $pids
        sleep 1
    fi
}

start_server() {
    if [ ! -f "$PKP_CONFIG_FILE" ]; then
        echo "ERROR: $PKP_CONFIG_FILE does not exist." >&2
        exit 1
    fi
    echo "Starting php -S $HOST:$PORT -t $APP_DIR"
    echo "  PKP_CONFIG_FILE=$PKP_CONFIG_FILE"
    echo "  TEST_API_KEY=<set>"
    nohup php -S "$HOST:$PORT" -t "$APP_DIR" >"$LOG_FILE" 2>&1 &
    sleep 1
    echo "Server pid $! (log: $LOG_FILE)"
}

case "$ACTION" in
    stop) stop_server ;;
    start) start_server ;;
    restart) stop_server; start_server ;;
    *) echo "Usage: $0 [start|stop|restart] [port]" >&2; exit 1 ;;
esac
