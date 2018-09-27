# Changelog

All notable changes to `laravel-responsecache` will be documented in this file.

## 4.4.4 - 2018-09-23

- fix for caching urls with query parameters

## 4.4.3 - 2018-09-23

- fix for forgetting a specific url

## 4.4.2 - 2018-08-24

- add support for Laravel 5.7

## 4.4.1 - 2018-07-26

- fix for issue #123

## 4.4.0 - 2018-04-30

- add support for Lumen

## 4.3.0 - 2018-03-01

- add `forget`

## 4.2.1 - 2018-02-08

- add support for L5.6

## 4.2.0 - 2018-01-30

- Added: `clear()` method and `responsecache:clear` command
- Deprecated: `flush()` method and `responsecache:flush` command

Deprecated features will still work until the next major version.

## 4.1.1 - 2018-01-30
- Added: Better exception handling when something goes wrong unserializing the response

## 4.1.0 - 2017-09-26
- Added: Support for specific lifetimes on routes

## 4.0.1 - 2017-08-30
- Fixed: Artisan command registration

## 4.0.0 - 2017-08-30
- Added: Support for Laravel 5.5
- Removed: Support for all older Laravel versions
- Changed: Renamed facade class

## 3.2.0 - 2017-06-19
- Added: Support for `BinaryFileResponse`

## 3.1.0 - 2017-04-28
- Added: Support for taggable cache

## 3.0.1 - 2017-03-16
- Fixed: Php version dependency in `composer.json`

## 3.0.0 - 2017-03-16
- Added: `enabled` method on cache profiles
- Added: Events
- Changed: Middleware won't automatically be registered anymore
- Changed: Renamed config file
- Changed: Renamed various methods for readability
- Removed: Dropped PHP 5.6 support

## 2.0.0 - 2017-01-24
- Added: Support for Laravel 5.4
- Removed: Dropped support for all older Laravel versions

## 1.1.7 - 2016-10-10
- Added: Usage of `RESPONSE_CACHE_LIFETIME` env var to config file

## 1.1.6 - 2016-08-07
- Changed: Debug headers will not be sent when `APP_DEBUG` is set to false

## 1.1.5 - 2015-08-28
- Fixed: Issue where the cache middleware couldn't correctly determine the currently authenticated user

## 1.1.4 - 2015-08-12
- Fixed: An issue where cached request were still served even if the package was disabled via the config file

## 1.1.3 - 2015-08-11
- Fixed: An issue where the cache header could not be set

## 1.1.2 - 2015-07-22
- Fixed: BaseCacheProfile has been made abstract (as it should have been all along)

## 1.1.1 - 2015-07-20
- Fixed: Default cachetime

## 1.1.0 - 2015-07-20
- Added: A command to flush the response cache

## 1.0.0 - 2015-07-20
- Initial release
