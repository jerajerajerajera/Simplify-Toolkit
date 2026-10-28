#!/usr/bin/env bash
# Runs every check that needs no panel: php -l, the unit tests, the translations and the Pelican Hub checks.
set -euo pipefail
cd "$(dirname "$0")/.."
find $(ls -d src tests resources lang database 2>/dev/null) -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php tests/server-kind.php
php tests/game-settings.php
php tests/server-icon.php
php tests/sources.php
php tests/start-check.php
php tests/status-ping.php
php tests/addon.php
php tests/lang.php
python3 tests/hub-check.py
