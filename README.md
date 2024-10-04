# Laravel N+1 Query Detector

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beyondcode/laravel-query-detector.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-query-detector)
[![Total Downloads](https://img.shields.io/packagist/dt/beyondcode/laravel-query-detector.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-query-detector)

The Laravel N+1 query detector helps you to increase your application's performance by reducing the number of queries it executes. This package monitors your queries in real-time, while you develop your application and notify you when you should add eager loading (N+1 queries).

![Example alert](https://beyondco.de/github/n+1/alert.png)


## Installation

You can install the package via composer:

```bash
composer require beyondcode/laravel-query-detector --dev
```

The package will automatically register itself.

## Documentation

You can find the documentation on our [website](http://beyondco.de/docs/laravel-query-detector).


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
