#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FLOWDROP_DIR="${ROOT_DIR}/packages/flowdrop"
BUILD_DIR="${FLOWDROP_DIR}/build/flowdrop"
TARGET_DIR="${ROOT_DIR}/web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop"

if [[ ! -d "${FLOWDROP_DIR}" ]]; then
  echo "FlowDrop source not found at ${FLOWDROP_DIR}" >&2
  exit 1
fi

echo "Building FlowDrop (Drupal build)..."
(cd "${FLOWDROP_DIR}" && npm install && npm run build:drupal)

if [[ ! -d "${BUILD_DIR}" ]]; then
  echo "Expected build output not found at ${BUILD_DIR}" >&2
  exit 1
fi

echo "Syncing build outputs to Drupal library path..."
rm -rf "${TARGET_DIR}"
mkdir -p "${TARGET_DIR}"
cp -R "${BUILD_DIR}/." "${TARGET_DIR}/"

echo "Done."
