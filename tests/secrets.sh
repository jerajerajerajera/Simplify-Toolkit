#!/usr/bin/env bash
# No secrets in the repository: API keys, tokens, private keys or passwords in any tracked file (run by tests/run.sh).
set -euo pipefail
cd "$(dirname "$0")/.."
patterns=(
    '-----BEGIN [A-Z ]*PRIVATE KEY-----'          # private keys
    'gh[pousr]_[A-Za-z0-9]{36,}'                  # GitHub tokens
    'github_pat_[A-Za-z0-9_]{40,}'
    'pnl_[A-Za-z0-9]{20,}'                         # Pelican API keys (Hub, panel)
    'ptl[ac]_[A-Za-z0-9]{20,}'
    'AKIA[0-9A-Z]{16}'                             # AWS access keys
    'sk-(ant-|proj-)?[A-Za-z0-9_-]{32,}'           # AI service keys
    'xox[baprs]-[A-Za-z0-9-]{10,}'                 # Slack tokens
    '\$2a\$10\$[A-Za-z0-9./]{53}'                  # CurseForge API keys
    'base64:[A-Za-z0-9+/]{43}='                    # Laravel APP_KEY
)
found=0
for pattern in "${patterns[@]}"; do
    # This file names the patterns, so it is not searched itself.
    if hits=$(git ls-files -z | grep -zv '^tests/secrets.sh$' | xargs -0 grep -nIE -- "$pattern" 2>/dev/null); then
        # Only the file and line, never the secret itself.
        echo "FAIL a secret in the repository (pattern $pattern):"
        echo "$hits" | cut -d: -f1,2
        found=1
    fi
done
[ "$found" = 0 ] || exit 1
echo "PASS no secrets in the repository"
