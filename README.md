# Monetivo PHP Client

## Requirements and dependencies

You need PHP 5.5 and later. Additionally, make sure that the following PHP extensions are installed on your server:
- [`curl`](https://secure.php.net/manual/en/book.curl.php),
- [`json`](https://secure.php.net/manual/en/book.json.php)


## Composer

You can install the client via [Composer](http://getcomposer.org/) by running the command:

```bash
composer require monetivo/monetivo-php
```

Then use Composer's [autoload](https://getcomposer.org/doc/00-intro.md#autoloading) mechanism:

```php
require_once('vendor/autoload.php');
```

## Manual Installation

If you do not wish to use Composer, you can download the [latest release](https://github.com/monetivo/monetivo-php/releases). Then include the `autoload.php` file.

```php
require_once('/path/to/monetivo-php/autoload.php');
```

## Getting Started

Basic usage looks like:

```php
<?php

try {
    // app token
    $app_token = 'apptoken';

    // merchant's login
    $login = 'merchant_test_12345';

    // merchant's password
    $password = 'very_strong_password';

    // init the library
    $api = new \Monetivo\MerchantApi($app_token);

    // try to authenticate
    $token = $api->auth($login, $password);

    // następne zapytania do API
}
catch(Monetivo\Exceptions\MonetivoException $e) {
  echo $e->getHttpCode();
  echo $e->getResponse();
}
```

## Documentation

This is just a README so please see https://docs.monetivo.com/ for up-to-date documentation.

## Issues

If you find any issues, please do not hesitate to file them via GitHub. You can also submit your ideas about improving our


## Support and integration
In case you have any troubles with the integration please contact our support. We offer several plugins for different e-commerce platforms to make your integration easy. 