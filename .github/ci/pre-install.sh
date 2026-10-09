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

# PHPStan job only: the Magento-aware PHPStan extension (factories, proxies, generated classes).
if [ -n "${INPUT_PHPSTAN_LEVEL:-}" ]; then
    composer require --dev --no-interaction --no-progress --with-all-dependencies bitexpert/phpstan-magento
fi
