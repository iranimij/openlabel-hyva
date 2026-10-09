# Contributing

Thank you for helping. A few rules keep this module tiny and trustworthy.

## Scope

OpenLabel's scope per release is defined in the public roadmap. Features outside the current release are welcome as issues first, so the design can be discussed before code is written.

## Clean-room rule

All code must be written from scratch. Do not copy or adapt code, templates, CSS or assets from paid Magento extensions, even "just to see how they do it". Only Magento Open Source, Mage-OS, Hyvä (OSL) and other OSL/MIT/GPL code may be referenced.

## Workflow

- Branch from `main`, use [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `test:`, `docs:`, `chore:`).
- Every change ships with tests (PHPUnit unit and/or Magento integration tests).
- CI must be green: PHPCS (Magento2 standard), PHPStan level 6, unit and integration tests on the full matrix.
- Public classes and methods carry PHPDoc; `Api/` interfaces follow strict semver.

## Running the tests locally

From a Magento root where the module is installed:

```bash
vendor/bin/phpcs --standard=Magento2 vendor/iranimij/openlabel
vendor/bin/phpstan analyse -c vendor/iranimij/openlabel/phpstan.neon
vendor/bin/phpunit -c vendor/iranimij/openlabel/phpunit.xml.dist
cd dev/tests/integration && ../../../vendor/bin/phpunit -c phpunit.xml ../../../vendor/iranimij/openlabel/Test/Integration
```
