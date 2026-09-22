#!/bin/bash
set -euo pipefail

PHP=/usr/bin/php
TRANSFORMER_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="${TRANSFORMER_DIR}/sync-events.env"

if [[ ! -x "$PHP" ]]; then
  echo "PHP not found at $PHP" >&2
  exit 1
fi

if [[ ! -f "${TRANSFORMER_DIR}/run/Event.php" || ! -f "${TRANSFORMER_DIR}/run/Event.legacy.php" ]]; then
  echo "Expected run/Event and run/Event.legacy in ${TRANSFORMER_DIR}" >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing ${ENV_FILE}. Copy sync-events.env.example and fill in TYPESENSE_API_KEY." >&2
  exit 1
fi

set -a
source "$ENV_FILE"
set +a

if [[ -z "${TYPESENSE_API_KEY:-}" || "$TYPESENSE_API_KEY" = "replace-me" ]]; then
  echo "TYPESENSE_API_KEY is not set in ${ENV_FILE}" >&2
  exit 1
fi

WP_LEGACY_EVENTS_PAGINATOR="${WP_LEGACY_EVENTS_PAGINATOR:-}"

if [[ -n "$WP_LEGACY_EVENTS_PAGINATOR" && "$WP_LEGACY_EVENTS_PAGINATOR" != "get-param" && "$WP_LEGACY_EVENTS_PAGINATOR" != "wordpress" ]]; then
  echo "WP_LEGACY_EVENTS_PAGINATOR must be 'get-param' or 'wordpress' in ${ENV_FILE}, got: $WP_LEGACY_EVENTS_PAGINATOR" >&2
  exit 1
fi

legacy_args=("--target=typesense")
if [[ -n "$WP_LEGACY_EVENTS_PAGINATOR" ]]; then
  legacy_args+=("--paginator=$WP_LEGACY_EVENTS_PAGINATOR")
fi

cd "$TRANSFORMER_DIR"

echo "[$(date -Iseconds)] Starting Event.legacy"
"$PHP" "${TRANSFORMER_DIR}/run/Event.legacy.php" "${legacy_args[@]}"

echo "[$(date -Iseconds)] Starting Event (headless)"
"$PHP" "${TRANSFORMER_DIR}/run/Event.php" "--target=typesense"

echo "[$(date -Iseconds)] Done"
