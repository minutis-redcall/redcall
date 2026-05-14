#!/usr/bin/env bash
# Wipes var/cache/ and rebuilds the dev cache. Useful after upgrading
# vendor packages that need a fresh container compile.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."
find var/cache -mindepth 1 -delete 2>/dev/null || true
php bin/console cache:clear --env=dev 2>&1
