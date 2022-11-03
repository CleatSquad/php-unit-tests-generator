# php Unit Generator

This package allows you to generate unit tests.
This package is recommended for magento 2. 

## Badges

[![Latest Stable Version](http://poser.pugx.org/cleatsquad/php-unit-tests-generator/v)](https://packagist.org/packages/cleatsquad/php-unit-tests-generator) 
[![Total Downloads](http://poser.pugx.org/cleatsquad/php-unit-tests-generator/downloads)](https://packagist.org/packages/cleatsquad/php-unit-tests-generator) 
[![Latest Unstable Version](http://poser.pugx.org/cleatsquad/php-unit-tests-generator/v/unstable)](https://packagist.org/packages/cleatsquad/php-unit-tests-generator) 
[![License](http://poser.pugx.org/cleatsquad/php-unit-tests-generator/license)](https://packagist.org/packages/cleatsquad/php-unit-tests-generator) 

## Getting Started

### Installing

Add dependency
```
composer require cleatsquad/php-unit-tests-generator --dev
```

## Examples

You can use it to generate tests for all classes in a folder

```php
bin/magento dev:tests:generate-unit /app/code/Vendor/Module/path
```
Or use it for a specified file

```php
bin/magento dev:tests:generate-unit /app/code/Vendor/Module/path/to/file.php
```
## Examples

@todo add origin class and generated test


## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/cleatsquad/php-unit-tests-generator/tags). 

## Authors

* **Mohamed El Mrabet** - *Initial work* - [mimou78](https://github.com/mimou78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
