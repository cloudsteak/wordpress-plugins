#!/usr/bin/env bash
# Compile Registration Guard translation files.
#
# Requires gettext (msgfmt). On macOS: brew install gettext
# On Debian/Ubuntu: sudo apt-get install gettext
#
# Usage:
#   ./languages/build-translations.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if ! command -v msgfmt >/dev/null 2>&1; then
	echo "Error: msgfmt not found. Install gettext to compile .mo files." >&2
	echo "  macOS:   brew install gettext" >&2
	echo "  Ubuntu:  sudo apt-get install gettext" >&2
	exit 1
fi

msgfmt "${SCRIPT_DIR}/registration-guard-hu_HU.po" -o "${SCRIPT_DIR}/registration-guard-hu_HU.mo"

echo "Compiled ${SCRIPT_DIR}/registration-guard-hu_HU.mo"
