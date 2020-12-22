# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
