# CHANGELOG for ZipStream-PHP

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Honor last-modified timestamps set via `ZipStream\Option\File::setTime()`

## [1.0.0] - 2019-04-17

### Breaking changes
- Mininum PHP version is now 7.1
- Options are now passed to the ZipStream object via the Option\Archive object. See the wiki for available options and code examples

### Added
- Add large file support with Zip64 headers

### Changed
- Major refactoring and code cleanup
