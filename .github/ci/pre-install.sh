#!/usr/bin/env bash
# Runs inside the ExtDN containers after the local-source repository is configured and before Composer installs
# the Magento project. Keep it idempotent: the same script serves the unit, integration and PHPStan jobs.
set -e

# Authenticate Composer against the GitHub API (VCS repositories and dist downloads) to avoid the anonymous rate limit.
if [ -n "${GITHUB_TOKEN:-}" ]; then
    composer config -g github-oauth.github.com "$GITHUB_TOKEN"
fi

# Until the iranimij packages are on Packagist, CI resolves them from GitHub.
composer config repositories.iranimij-base vcs https://github.com/iranimij/module-base
composer config repositories.iranimij-openlabel vcs https://github.com/iranimij/openlabel
composer config minimum-stability dev
composer config prefer-stable true

# While stacked pull requests are open, test against the openlabel branch named in .github/ci/openlabel-ref
# ("main" or empty = the default branch), aliased 1.1.x-dev so it satisfies "^1.1" (openlabel main is aliased 1.1.x-dev too).
OPENLABEL_REF=""
for ref_file in "$(dirname "$0")/openlabel-ref" "${GITHUB_WORKSPACE:-/github/workspace}/.github/ci/openlabel-ref"; do
    if [ -f "$ref_file" ]; then
        OPENLABEL_REF="$(tr -d '[:space:]' < "$ref_file")"
        break
    fi
done
if [ -n "$OPENLABEL_REF" ] && [ "$OPENLABEL_REF" != "main" ]; then
    composer require --no-update --no-interaction "iranimij/openlabel:dev-${OPENLABEL_REF} as 1.1.x-dev"
fi

# PHPStan job only: the Magento-aware PHPStan extension (factories, proxies, generated classes).
if [ -n "${INPUT_PHPSTAN_LEVEL:-}" ]; then
    composer require --dev --no-interaction --no-progress --with-all-dependencies bitexpert/phpstan-magento
fi
