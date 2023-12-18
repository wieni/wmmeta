# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This changelog is incomplete. Pull requests with entries before 1.10.0
are welcome.

## [2.0.0] - 2023-12-18
### Changed [BC]
- replace use of `wieni/wmmodel` with [`drupal/entity_model`](https://www.drupal.org/project/entity_model)
   - Read the [upgrade guide](https://github.com/wieni/wmmodel/blob/main/UPGRADING.md) of `wieni/wmmodel` to migrate to `drupal/entity_model`

## [1.11.0] - 2023-07-20
### Changed
- Rely on `MetaService` to preview title, description and image metadata

## [1.10.4] - 2023-06-29
### Added
- `og:url` and `og:type`
- restrict access to preview modal route:
  - Now only those who have permission `access wmmeta preview` can access this route.
  - Update hook has been added to grant this permission to all roles (except anonymous and authenticated)
- validate publish and unpublish dates

### Removed
- Removed some Scheduler.php logging
  - start scheduler for an entity type has been removed
  - logging end scheduler per language has been removed

## [1.10.3] - 2022-01-20
### Changed
- Add support for wmmodel ^2.0
- remove use of `WmModel` and `WmModelInterface` as they are removed now

## [1.10.2] - 2021-10-05
### Changed
- Add support for wmmodel ^0.2 and ^0.3

## [1.10.1] - 2021-07-28
### Added
- Add image module dependency

## [1.10.0] - 2021-07-07
### Added
- Add _OG image_ image style

### Changed
- Translate meta status labels
- Use field helpers trait on meta model
- Add coding style fixers
- Add Composer 2 dev dependency

### Fixed
- Fix error when entity does not have a created field

### Removed
- Remove `wieni/imgix` dependency. If you plan to keep using the `imgix` module, make sure to install v9 since previous
  versions don't support image styles.
- Remove `wieni/wmcontroller` dependency. Support hasn't been removed, but the dependency is now optional.
- Remove `wieni/wmmedia` dependency. Support hasn't been removed, but the dependency is now optional.
- Remove `wieni/maxlength` dependency. Support hasn't been removed, but the dependency is now optional.

