# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## 6.0.0

If you're using the default settings you can upgrade without any problems.

- By default the `CsrfTokenReplacer` will be applied before caching the request. For most users, this will be harmless
- The `flush` method has been removed, use `clear` instead
- The `Flush` command has been removed. Use `ClearCommand` instead


## 5.0.0

As of Laravel 5.8 defining cache time in seconds is supported. All mentions of response cache time should be changed from minutes to seconds:

- If you've published to config file, change `cache_lifetime_in_minutes` to `cache_lifetime_in_seconds`
- If you're using the `responseCache` middleware, change the optional time parameter to seconds (value * 60)
- If you're extending `CacheResponse`, `ResponseCacheRepository`, `BaseCacheProfile` or `CacheResponse` you should check the relevant methods
