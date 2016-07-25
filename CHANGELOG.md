# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.4.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#120](https://github.com/zfcampus/zf-mvc-auth/pull/120) fixes the
  `Module::onBootstrap()` method to re-introduce attachment of the
  `MvcRouteListener`.

## 1.4.0 - 2016-07-11

### Added

- [#114](https://github.com/zfcampus/zf-mvc-auth/pull/114) and
  [#116](https://github.com/zfcampus/zf-mvc-auth/pull/116) add support for both
  PHP 7 and version 3 components from Zend Framework (while retaining
  compatibility for version 2 components).

### Deprecated

- Nothing.

### Removed

- [#116](https://github.com/zfcampus/zf-mvc-auth/pull/116) removes support for
  PHP 5.5.

### Fixed

- Nothing.

## 1.3.2 - 2016-07-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#111](https://github.com/zfcampus/zf-mvc-auth/pull/111) adds a check for the
  `unset_refresh_token_after_use` configuration flag when creating an
  `OAuth2\Server` instance, passing it to the instance when discovered.
