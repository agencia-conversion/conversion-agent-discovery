#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT_DIR}/dist/build"
ZIP_PATH="${ROOT_DIR}/dist/conversion-agent-discovery.zip"

rm -rf "${BUILD_DIR}" "${ZIP_PATH}"
mkdir -p "${BUILD_DIR}/conversion-agent-discovery" "${ROOT_DIR}/dist"

rsync -a --delete \
  --exclude-from="${ROOT_DIR}/.distignore" \
  "${ROOT_DIR}/" \
  "${BUILD_DIR}/conversion-agent-discovery/"

(cd "${BUILD_DIR}" && zip -qr "${ZIP_PATH}" conversion-agent-discovery)

echo "${ZIP_PATH}"
