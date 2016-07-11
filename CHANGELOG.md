# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
