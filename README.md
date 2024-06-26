# Statamic Autocache

> Statamic Autocache is an addon that automatically caches the content of any partials for a faster response time and selective invalidation


## How to Install

Run the following command from your project root:

``` bash
composer require tv2regionerne/statamic-cache
```

Then run the migrations:

```bash
php artisan migrate
```

You can also optionally publish the config:

```bash
php artisan vendor:publish --tag=statamic-cache-config
```

## How to Use

The addon should work automatically in most cases. It adds hooks to partial, nav and collection tags, as well as augmentation of entries and globals to determine what content is included in what partials. This data is then added to a database store that is used to determine what cached data should be invalidated at what times.

The default cache is used, or you can specify a cache store called `statamic_autocache` if you want to have more control over when it is cleared.

### Middleware
The autocache middleware will automatically be added to your `web` middleware stack. If you want to include it to other stacks simply add:

`\Tv2regionerne\StatamicCache\Http\Middleware\Autocache::class`

### Store
The addon comes with a Facade for interacting with the Store:
`\Tv2regionerne\StatamicCache\Facades\Store`

If you want to add extra data to the store you can do so using in your AppServiceProvider, eg:

```php
MyTag::hook('init', function () {
	Store::mergeTags(['my_tag:id']);
});
```

Make sure you also invalidate the tag in a listener:

```php
Store::invalidateContent(['my_tag:id']);
```

### Custom cache driver with database index
Run the migrations to add the static_cache table.  
Add this section to your AppServiceProvider's register function.
```php
$this->app->booting(function () {
    StaticCache::extend('redis_with_database', function ($app, $config) {
        return new \Tv2regionerne\StatamicCache\Cacher\Cacher(StaticCache::cacheStore(), $config);
    });
});
```

Change the half measure static cache driver to use `redis_with_database`
```php
'half' => [
    'driver' => 'redis_with_database',
    'expiry' => null,
],

```
