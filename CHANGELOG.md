# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2022-01-11
### Changed
- Increase minimum wieni/wmmodel_factory version to 2.0
- Increase minimum Drupal core version to 9.3 due to wieni/wmmodel_factory
- Increase minimum PHP requirement to 7.3 due to Drupal core PHP requirement

### Removed
- Remove implicit wieni/wmmodel dependency

## Added
- Add `drush/drush` & `wieni/wmcontent` as dev dependencies to prevent autoloading errors when running Rector
- Add Rector

## Changed
- Apply coding standard fixes
- Update `wieni/wmcodestyle`
- Set Composer 2 as minimum version

## [3.0.0] - 2020-12-22
## Added
- Track generated entities using new entity type instead of using state
- Add PHPStan
- Add PHP 8 support

## Changed
- Rewrite to use [`wieni/wmmodel_factory`](https://github.com/wieni/wmmodel_factory) behind the scenes
- Move html generator methods from trait to Faker provider
- Change the minimum version of core to 8.7.7
- Replace `fzaninotto/faker` dependency with `fakerphp/faker`

## Removed
- Removed the ability to provide generators per langcode
