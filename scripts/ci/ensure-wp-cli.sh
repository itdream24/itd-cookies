#!/usr/bin/env bash
# CI only. Official release checksum is pinned, never learned from the download.
set -Eeuo pipefail

version=2.12.0
sha256=ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c
url="https://github.com/wp-cli/wp-cli/releases/download/v${version}/wp-cli-${version}.phar"

if [[ "${WP_CLI_FORCE_FALLBACK:-0}" == 1 ]] || ! command -v wp >/dev/null 2>&1; then
  echo "Installing verified WP-CLI ${version} fallback"
  scratch=$(mktemp -d "${RUNNER_TEMP:?RUNNER_TEMP is required}/itd-wp-cli.XXXXXX")
  download="$scratch/wp.phar"
  trap 'rm -f -- "$download"; rmdir -- "$scratch"' EXIT
  curl --fail --location --proto '=https' --proto-redir '=https' --tlsv1.2 \
    --connect-timeout 15 --max-time 40 --retry 3 --retry-delay 2 \
    --retry-max-time 90 --retry-all-errors --output "$download" "$url"
  printf '%s  %s\n' "$sha256" "$download" | sha256sum --check --strict -
  bin="${RUNNER_TEMP}/itd-wp-cli-bin"
  mkdir -p "$bin"
  install -m 0755 "$download" "$bin/wp"
  export PATH="$bin:$PATH"
  printf '%s\n' "$bin" >> "${GITHUB_PATH:?GITHUB_PATH is required}"
  hash -r
else
  echo 'Using WP-CLI installed by setup-php'
fi

command -v wp
wp --info
[[ "$(wp cli version)" == "WP-CLI ${version}" ]] || {
  echo "Expected WP-CLI ${version}" >&2
  exit 1
}
