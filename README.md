# Laravel N+1 Query Detector

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beyondcode/laravel-query-detector.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-query-detector)
[![Build Status](https://img.shields.io/travis/beyondcode/laravel-query-detector/master.svg?style=flat-square)](https://travis-ci.org/beyondcode/laravel-query-detector)
[![Quality Score](https://img.shields.io/scrutinizer/g/beyondcode/laravel-query-detector.svg?style=flat-square)](https://scrutinizer-ci.com/g/beyondcode/laravel-query-detector)
[![Total Downloads](https://img.shields.io/packagist/dt/beyondcode/laravel-query-detector.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-query-detector)

The Laravel N+1 query detector helps you to increase your application's performance by reducing the number of queries it executes. This package monitors your queries in real-time, while you develop your application and notify you when you should add eager loading (N+1 queries).

![Example alert](https://beyondco.de/github/n+1/alert.png)

## Installation

You can install the package via composer:

```bash
composer require beyondcode/laravel-query-detector --dev
```

The package will automatically register itself.

## Usage

If you run your application in the `debug` mode, the query monitor will be automatically active. So there is nothing you have to do.

By default, this package will display an `alert()` message to notify you about an N+1 query found in the current request. 

If you rather want this information to be written to your `laravel.log` file, written to your browser's console log as a warning or listed in a new tab for the [Laravel Debugbar (barryvdh/laravel-debugbar)](https://github.com/barryvdh/laravel-debugbar), you can publish the configuration and change the output behaviour (see example below).

You can publish the package's configuration using this command:

```bash
php artisan vendor:publish --provider=BeyondCode\QueryDetector\QueryDetectorServiceProvider
```

This will add the `querydetector.php` file in your config directory with the following contents:

```php
<?php

return [
    /*
     * Enable or disable the query detection.
     * If this is set to "null", the app.debug config value will be used.
     */
    'enabled' => env('QUERY_DETECTOR_ENABLED', null),

    /*
     * Threshold level for the N+1 query detection. If a relation query will be
     * executed more then this amount, the detector will notify you about it.
     */
    'threshold' => 1,

    /*
     * Here you can whitelist model relations.
     *
     * Right now, you need to define the model relation both as the class name and the attribute name on the model.
     * So if an "Author" model would have a "posts" relation that points to a "Post" class, you need to add both
     * the "posts" attribute and the "Post::class", since the relation can get resolved in multiple ways.
     */
    'except' => [
        //Author::class => [
        //    Post::class,
        //    'posts',
        //]
    ],

    /*
     * Define the output format that you want to use. Multiple classes are supported.
     * Available options are:
     *
     * Alert:
     * Displays an alert on the website
     * \BeyondCode\QueryDetector\Outputs\Alert::class
     *
     * Console:
     * Writes the N+1 queries into your browsers console log
     * \BeyondCode\QueryDetector\Outputs\Console::class
     *
     * Clockwork: (make sure you have the itsgoingd/clockwork package installed)
     * Writes the N+1 queries warnings to Clockwork log
     * \BeyondCode\QueryDetector\Outputs\Clockwork::class
     *
     * Debugbar: (make sure you have the barryvdh/laravel-debugbar package installed)
     * Writes the N+1 queries into a custom messages collector of Debugbar
     * \BeyondCode\QueryDetector\Outputs\Debugbar::class
     *
     * JSON:
     * Writes the N+1 queries into the response body of your JSON responses
     * \BeyondCode\QueryDetector\Outputs\Json::class
     *
     * Log:
     * Writes the N+1 queries into the Laravel.log file
     * \BeyondCode\QueryDetector\Outputs\Log::class
     */
    'output' => [
        \BeyondCode\QueryDetector\Outputs\Log::class,
        \BeyondCode\QueryDetector\Outputs\Alert::class,
    ]

];
```

If you use **Lumen**, you need to copy the config file manually and register the Lumen Service Provider in `bootstrap/app.php` file

```php
$app->register(\BeyondCode\QueryDetector\LumenQueryDetectorServiceProvider::class);
```

If you need additional logic to run when the package detects unoptimized queries, you can listen to the `\BeyondCode\QueryDetector\Events\QueryDetected` event and write a listener to run your own handler. (e.g. send warning to Sentry/Bugsnag, send Slack notification, etc.)

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email marcel@beyondco.de instead of using the issue tracker.

## Credits

- [Marcel Pociot](https://github.com/mpociot)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
