# Changelog

All notable changes to `laravel-responsecache` will be documented in this file.

## 6.6.9 - 2021-03-30

- fix for issue #331 (#344)

## 6.6.8 - 2020-01-25

- use package service provider

## 6.6.7 - 2020-11-28

- add support for PHP 8

## 6.6.6 - 2020-09-27

- fix clearing tagged cache

## 6.6.5 - 2020-09-22

- fix tagged responsecache:clear (#316)

## 6.6.4 - 2020-09-09

- Support Laravel 8

## 6.6.3 - 2020-08-24

- replace Laravel/framework with individual packages (#304)

## 6.6.2 - 2020-06-03

- support JSON types other than application/json (#299)

## 6.6.1 - 2020-04-22

- change to the proper way of setting app URL on runtime (#290)

## 6.6.0 - 2020-03-02

- drop support for Laravel 6 to fix the test suite (namespace of `TestResponse` has changed)

## 6.5.0 - 2020-03-02

- add support for Laravel 7

## 6.4.0 - 2019-12-01

- drop support for all non-current PHP and Laravel versions

## 6.3.0 - 2019-09-01

- add support for custom serializers

## 6.2.1 - 2020-03-07

- make compatible with Laravel 7, so the package can be used on PHP 7.3

## 6.2.0 - 2019-09-01

- make compatible with Laravel 6

## 6.1.1 - 2019-08-08

- restore laravel 5.7 compatibility

## 6.1.0 - 2019-08-01

- add support for cache tags

## 6.0.2 - 2019-07-31

- make json responses cacheable

## 6.0.1 - 2019-07-09

- use Rfc2822S formatted date in cache time header

## 6.0.0 - 2019-05-20

- added support for replacers
- you can now swap out `RequestHasher` in favor of a custom one
- `CacheAllSuccessfulGetRequests` will only cache responses of which the content type starts with `text`
- removed deprecated `Flush` command
- `\Spatie\ResponseCache\ResponseCacheFacade` has been removed
- dropped support for carbon v1
- dropped support for PHP 7.2

## 5.0.3 - 2019-05-10

- make sure the request starts with the app url - fixes #177

## 5.0.2 - 2019-04-05

- make host specific caches

## 5.0.1 - 2019-03-15

- fix cache lifetime in config file

## 5.0.0 - 2019-02-27

- drop support for Laravel 5.7 and lower
- drop support for PHP 7.0 and lower
- change all cache time parameters to seconds (see UPGRADING.md)

## 4.4.5 - 2019-02-27

- add support for Laravel 5.8
- you can no longer add multiple `CacheResponse` middleware to one route

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
