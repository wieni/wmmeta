# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This changelog is incomplete. Pull requests with entries before 1.10.0
are welcome.

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

