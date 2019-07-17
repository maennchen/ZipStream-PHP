# CHANGELOG for ZipStream-PHP

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2019-07-11

### Added
- Option to flush output buffer after every write (#122)

## [1.1.0] - 2019-04-30

### Fixed
- Honor last-modified timestamps set via `ZipStream\Option\File::setTime()` (#106)
- Documentation regarding output of HTTP headers
- Test warnings with PHPUnit (#109)

### Added
- Test for FileNotReadableException (#114)
- Size attribute to File options (#113)
- Tests on PHP 7.3 (#108)

## [1.0.0] - 2019-04-17

### Breaking changes
- Mininum PHP version is now 7.1
- Options are now passed to the ZipStream object via the Option\Archive object. See the wiki for available options and code examples

### Added
- Add large file support with Zip64 headers

### Changed
- Major refactoring and code cleanup
