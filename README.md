[![Build Status](https://github.com/sirn-se/phrity-net-uri/actions/workflows/acceptance.yml/badge.svg)](https://github.com/sirn-se/phrity-net-uri/actions)
[![Coverage Status](https://coveralls.io/repos/github/sirn-se/phrity-net-uri/badge.svg?branch=main)](https://coveralls.io/github/sirn-se/phrity-net-uri?branch=main)

# Introduction

Implementation of the [PSR-7 UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface)
and [PSR-17 UriFactoryInterface](https://www.php-fig.org/psr/psr-17/#26-urifactoryinterface) interfaces.

Nothing fancy. Just working. Because I need a URI implementation **not** hardwired to HTTP messaging.
And some extras. Allow all valid schemes.

## Installation

Install with [Composer](https://getcomposer.org/);
```
composer require phrity/net-uri
```

## Uri class methods

Implemts [PSR-7 UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface)
and provides some extra metods and options. [More info here](docs/Uri.md).

```php
use Phrity\Net\Uri;
$uri = new Uri('http://example.com/path/to/file.html?query1=1#fragment');

// PSR-7 getters
$uri->getScheme();
$uri->getHost();
$uri->getPort();
$uri->getPath();
$uri->getQuery();
$uri->getFragment();
$uri->getAuthority();
$uri->getUserInfo();

// PSR-7 setters
$uri->withScheme('https');
$uri->withHost('example2.com');
$uri->withPort(8080);
$uri->withPath('/path/to/another/file.html');
$uri->withQuery('query2=2');
$uri->withFragment('another-fragment');
$uri->withUserInfo('username', 'password');

// Additional methods
$uri->toString();
$uri->__toString();
$uri->jsonSerialize();
$uri->getQueryItems();
$uri->getQueryItem('query1');
$uri->withQueryItems(['query1' => '1', 'query2' => '2']);
$uri->withQueryItem('query1', '1');
$uri->getComponents();
$uri->withComponents(['scheme' => 'https', 'host' => 'example2.com']);
```

## UriFactory class methods

Implemts [PSR-17 UriFactoryInterface](https://www.php-fig.org/psr/psr-17/#26-urifactoryinterface)
and provides some extra metods and options. [More info here](docs/UriFactory.md).

```php
use Phrity\Net\UriFactory;
$factory = new UriFactory();
$factory->createUri('http://example.com/path/to/file.html');
$factory->createUriFromInterface(new GuzzleHttp\Psr7\Uri('http://example.com/path/to/file.html'));
```

## Modifiers

Out of the box, it will behave as specified by PSR standards.
To change behaviour, there are some modifiers available.
These can be added as last argument in all `get` and `with` methods, plus the `toString` method.

* `REQUIRE_PORT` - Attempt to show port, even if default
* `ABSOLUTE_PATH` - Will cause path to use absolute form, i.e. starting with `/`
* `NORMALIZE_PATH` - Will attempt to normalize path
* `IDN_ENCODE` / `IDN_DECODE` - Encode or decode IDN-format for non-ASCII host

### Examples

```php
$uri = new Uri('http://example.com');
$uri->getPort(Uri::REQUIRE_PORT); // => 80
$uri->toString(Uri::REQUIRE_PORT); // => 'http://example.com:80'

$uri = new Uri('a/./path/../to//something');
$uri->getPath(Uri::ABSOLUTE_PATH | Uri::NORMALIZE_PATH); // => '/a/to/something'
$uri->toString(Uri::ABSOLUTE_PATH | Uri::NORMALIZE_PATH); // => '/a/to/something'

$clone = $uri->withPath('path/./somewhere/else/..', Uri::ABSOLUTE_PATH | Uri::NORMALIZE_PATH);
$clone->getPath(); // => '/path/somewhere'

$uri = new Uri('https://ηßöø必Дあ.com');
$uri->getHost(Uri::IDN_ENCODE); // => 'xn--zca0cg32z7rau82strvd.com'
```

## Versions

| Version | PHP | |
| --- | --- | --- |
| `2.0` | `^8.0` | Query helpers, with([]) and getComponents() methods, IDN encode/decode |
| `1.3` | `^7.4\|^8.0` |  |
| `1.2` | `^7.4\|^8.0` | IDNA modifier |
| `1.1` | `^7.4\|^8.0` | Require port, Absolute path, Normalize path modifiers |
| `1.0` | `^7.4\|^8.0` | Initial version |
