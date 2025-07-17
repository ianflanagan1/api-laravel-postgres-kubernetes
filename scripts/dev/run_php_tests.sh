#!/bin/sh

set -o errexit
set -o nounset

SHOULD_MIGRATE="${1:-false}"        # Must be "true" to migrate and seed db
TEST_COVERAGE_THRESHOLD="${2:-}"    # 0-100
INCLUDE_OR_EXCLUDE="${3:-include}"  # Must be "include" or "exclude"
TESTSUITE="${4:-}"                  # Comma separated
SPECIFIED_TEST="${5:-}"

### CHECKS #####################################

if [ -n "${TEST_COVERAGE_THRESHOLD}" ]; then
    if ! php -m | grep -q "^xdebug$"; then
        echo "Error: Xdebug is not installed" >&2
        exit 1
    fi
    export XDEBUG_MODE=coverage
fi

if [ -n "${TESTSUITE}" ]; then
    if [ "${INCLUDE_OR_EXCLUDE}" != "include" ] && [ "${INCLUDE_OR_EXCLUDE}" != "exclude" ]; then
        echo "Error: Invalid value for INCLUDE_OR_EXCLUDE: '${INCLUDE_OR_EXCLUDE}'. Must be 'include' or 'exclude'" >&2
        exit 1
    fi
fi

### MIGRATE ####################################

cd /app || { echo "Error: Failed to cd into /app" >&2; exit 1; }

if [ "${SHOULD_MIGRATE}" = "true" ]; then
    echo "--- Migrating/seeding database ---"
    php artisan migrate:fresh --drop-views --drop-types --seed --env=testing --ansi
fi

### TESTS ######################################

set -- php artisan test

if [ -n "${TEST_COVERAGE_THRESHOLD}" ]; then
    set -- "$@" --coverage "--min=${TEST_COVERAGE_THRESHOLD}"
fi

if [ -n "${TESTSUITE}" ]; then
    case "${INCLUDE_OR_EXCLUDE}" in
        "include") set -- "$@" --testsuite "${TESTSUITE}";;
        "exclude") set -- "$@" --exclude-testsuite "${TESTSUITE}";;
    esac
fi

if [ -n "${SPECIFIED_TEST}" ]; then
    set -- "$@" "${SPECIFIED_TEST}"
fi

if [ ! -t 1 ]; then
    set -- "$@" --colors=always --ansi
fi

echo "--- Running PHPUnit tests ---"
"$@"
echo "--- All tests passed successfully ---"
