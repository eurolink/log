# Logger

A simple [PSR-3 compliant](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) logging class for PHP.

```php
use Unit6\Log\Logger;

$path = __DIR__ . '/logs';
$logger = new Logger($path);
$logger->info('Test info message.');
$logger->debug('Test debug message.');
```

### Log levels

The eight [RFC 5424](http://tools.ietf.org/html/rfc5424#section-6.2.1) levels of logs are supported, in cascading order:

 Code | Severity  | Description
------|-----------|-----------------------------------------------------------------
   0  | Emergency | System level failure (not application level)
   1  | Alert     | Failure that requires immediate attention
   2  | Critical  | Serious failure at the application level
   3  | Error     | Runtime errors, used to log unhandled exceptions
   4  | Warning   | May indicate that an error will occur if action is not taken
   5  | Notice    | Events that are unusual but not error conditions
   6  | Info      | Normal operational messages (no action required)
   7  | Debug     | Verbose info useful to developers for debugging purposes (default)

### License

This project is licensed under the MIT license -- see the `LICENSE` for the full license details.

### Acknowledgements

Some inspiration has been taken from the following projects:

- [katzgrau/KLogger](https://github.com/katzgrau/KLogger)
- [Seldaek/monolog](https://github.com/Seldaek/monolog)
- [frqnck/apix-log](https://github.com/frqnck/apix-log)
- [geoffroy-aubry/Logger](https://github.com/geoffroy-aubry/Logger)
- [pgoergler/Logging](https://github.com/pgoergler/Logging)