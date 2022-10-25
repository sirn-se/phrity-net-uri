[![Build Status](https://github.com/sirn-se/phrity-net-uri/actions/workflows/acceptance.yml/badge.svg)](https://github.com/sirn-se/phrity-net-uri/actions)
[![Coverage Status](https://coveralls.io/repos/github/sirn-se/phrity-net-uri/badge.svg?branch=main)](https://coveralls.io/github/sirn-se/phrity-net-uri?branch=main)

# Introduction

Implementation of the [PSR-7 Uri](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface)
and [PSR-17 UriFactory](https://www.php-fig.org/psr/psr-17/#26-urifactoryinterface) interfaces.

Nothing fancy. Just working. Because I need a URI implementation **not** related to HTTP messaging.
Allows all valid schemes.

## Installation

Install with [Composer](https://getcomposer.org/);
```
composer require phrity/net-uri
```

### The Uri class

```php
class Phrity\Net\Uri implements Psr\Http\Message\UriInterface
{
    // Constructor

    public function __construct(string $uri_string = '');

    // PSR-7 getters

    public function getScheme(): string;
    public function getAuthority(): string;
    public function getUserInfo(): string;
    public function getHost(): string;
    public function getPort(): ?int;
    public function getPath(): string;
    public function getQuery(): string;
    public function getFragment(): string;

    // PSR-7 setters

    public function withScheme($scheme): UriInterface;
    public function withUserInfo($user, $password = null): UriInterface;
    public function withHost($host): UriInterface;
    public function withPort($port): UriInterface;
    public function withPath($path): UriInterface;
    public function withQuery($query): UriInterface;
    public function withFragment($fragment): UriInterface;

    // PSR-7 string representation

    public function __toString(): string;
}
```

### The UriFactory class

```php
class Phrity\Net\UriFactory implements Psr\Http\Message\UriFactoryInterface
{
    // Constructor

    public function __construct();

    // PSR-17 factory

    public function createUri(string $uri = ''): UriInterface;
}
```


## Versions

| Version | PHP | |
| --- | --- | --- |
| `1.0` | `^7.4\|^8.0` | Initial version |
