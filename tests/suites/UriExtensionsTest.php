<?php

/**
 * Tests for Net\Uri class.
 * @package Phrity > Net > Uri
 */

declare(strict_types=1);

namespace Phrity\Net;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Phrity\Comparison\IncomparableException;
use Phrity\Util\ErrorHandler;
use Psr\Http\Message\UriInterface;
use JsonSerializable;
use Stringable;

class UriExtensionsTest extends TestCase
{
    public function testPortRequired(): void
    {
        // Specified port is default
        $uri = new Uri('http://domain.tld:80');
        $this->assertNull($uri->getPort());
        $this->assertSame(80, $uri->getPort(Uri::REQUIRE_PORT));
        $this->assertSame('domain.tld', $uri->getAuthority());
        $this->assertSame('domain.tld:80', $uri->getAuthority(Uri::REQUIRE_PORT));
        $this->assertSame('http://domain.tld', $uri->toString());
        $this->assertSame('http://domain.tld:80', $uri->toString(Uri::REQUIRE_PORT));

        // Specified port is cloned
        $clone = $uri->withScheme('https');
        $this->assertSame(80, $clone->getPort());
        $this->assertSame(80, $clone->getPort(Uri::REQUIRE_PORT));

        // Unspecified port, use default
        $uri = new Uri('http://domain.tld');
        $this->assertNull($uri->getPort());
        $this->assertSame(80, $uri->getPort(Uri::REQUIRE_PORT));
        $this->assertSame('domain.tld', $uri->getAuthority());
        $this->assertSame('domain.tld:80', $uri->getAuthority(Uri::REQUIRE_PORT));
        $this->assertSame('http://domain.tld', $uri->toString());
        $this->assertSame('http://domain.tld:80', $uri->toString(Uri::REQUIRE_PORT));

        // Unspecified port is not cloned
        $clone = $uri->withScheme('https');
        $this->assertNull($clone->getPort());
        $this->assertSame(443, $clone->getPort(Uri::REQUIRE_PORT));

        // Unspecified port is cloned
        $clone = $uri->withScheme('https', Uri::REQUIRE_PORT);
        $this->assertSame(80, $clone->getPort());
        $this->assertSame(80, $clone->getPort(Uri::REQUIRE_PORT));
    }

    public function testAbsolutePath(): void
    {
        // Empty path
        $uri = new Uri('');
        $this->assertSame('', $uri->getPath());
        $this->assertSame('/', $uri->getPath(Uri::ABSOLUTE_PATH));
        $this->assertSame('', $uri->toString());
        $this->assertSame('/', $uri->toString(Uri::ABSOLUTE_PATH));

        // Relative path
        $uri = new Uri('path/to/something');
        $this->assertSame('path/to/something', $uri->getPath());
        $this->assertSame('/path/to/something', $uri->getPath(Uri::ABSOLUTE_PATH));
        $this->assertSame('path/to/something', $uri->toString());
        $this->assertSame('/path/to/something', $uri->toString(Uri::ABSOLUTE_PATH));

        // Absolute path
        $uri = new Uri('/path/to/something');
        $this->assertSame('/path/to/something', $uri->getPath());
        $this->assertSame('/path/to/something', $uri->getPath(Uri::ABSOLUTE_PATH));
        $this->assertSame('/path/to/something', $uri->toString());
        $this->assertSame('/path/to/something', $uri->toString(Uri::ABSOLUTE_PATH));

        // Should not change path on clone
        $clone = $uri->withPath('something/else');
        $this->assertSame('something/else', $clone->getPath());

        // Should change path on clone
        $clone = $uri->withPath('something/else', Uri::ABSOLUTE_PATH);
        $this->assertSame('/something/else', $clone->getPath());

        // Should not change path on clone
        $clone = $uri->withPath('');
        $this->assertSame('', $clone->getPath());

        // Should change path on clone
        $clone = $uri->withPath('', Uri::ABSOLUTE_PATH);
        $this->assertSame('/', $clone->getPath());
    }

    public function testNormalizedPath(): void
    {
        // Relative path
        $uri = new Uri('./path/to/../something/./else/..');
        $this->assertSame('./path/to/../something/./else/..', $uri->getPath());
        $this->assertSame('path/something/', $uri->getPath(Uri::NORMALIZE_PATH));
        $this->assertSame('./path/to/../something/./else/..', $uri->toString());
        $this->assertSame('path/something/', $uri->toString(Uri::NORMALIZE_PATH));

        // Absolute path
        $uri = new Uri('/path/to/../something/./else/..');
        $this->assertSame('/path/to/../something/./else/..', $uri->getPath());
        $this->assertSame('/path/something/', $uri->getPath(Uri::NORMALIZE_PATH));
        $this->assertSame('/path/to/../something/./else/..', $uri->toString());
        $this->assertSame('/path/something/', $uri->toString(Uri::NORMALIZE_PATH));

        // Not fully resolvable
        $uri = new Uri('../a/../..');
        $this->assertSame('../..', $uri->getPath(Uri::NORMALIZE_PATH));

        // Root
        $uri = new Uri('///.//.//.');
        $this->assertSame('/', $uri->getPath(Uri::NORMALIZE_PATH));
        $uri = new Uri('.///.//.//');
        $this->assertSame('/', $uri->getPath(Uri::NORMALIZE_PATH));

        // No ending slash
        $uri = new Uri('/path/to/../something/./else');
        $this->assertSame('/path/something/else', $uri->getPath(Uri::NORMALIZE_PATH));

        // No ending slash
        $uri = new Uri('/path.with.dot/to/../something.with.dot/../file.html');
        $this->assertSame('/path.with.dot/file.html', $uri->getPath(Uri::NORMALIZE_PATH));

        // Should not change path on clone
        $clone = $uri->withPath('./path/to/../something/./else/..');
        $this->assertSame('./path/to/../something/./else/..', $clone->getPath());

        // Should change path on clone
        $clone = $uri->withPath('./path/to/../something/./else/..', Uri::NORMALIZE_PATH);
        $this->assertSame('path/something/', $clone->getPath());
    }

    public function testIdnEncodeHost(): void
    {
        // Get converted host
        $uri = new Uri('https://ηßöø必Дあ.com');
        $this->assertSame('ηßöø必дあ.com', $uri->getHost());
        $this->assertSame('xn--zca0cg32z7rau82strvd.com', $uri->getHost(Uri::IDN_ENCODE));
        $this->assertSame('https://ηßöø必дあ.com', $uri->toString());
        $this->assertSame('https://xn--zca0cg32z7rau82strvd.com', $uri->toString(Uri::IDN_ENCODE));

        // Should convert host on clone
        $clone = $uri->withHost('ηßöø必дあ.com', Uri::IDN_ENCODE);
        $this->assertSame('xn--zca0cg32z7rau82strvd.com', $clone->getHost());
        $this->assertSame('https://xn--zca0cg32z7rau82strvd.com', $clone->__toString());

        // Should not attempt conversion
        $clone = $uri->withHost('', Uri::IDN_ENCODE);
        $this->assertSame('', $clone->getHost());
    }

    public function testIdnDecodeHost(): void
    {
        // Get converted host
        $uri = new Uri('https://xn--zca0cg32z7rau82strvd.com');
        $this->assertSame('xn--zca0cg32z7rau82strvd.com', $uri->getHost());

        $this->assertSame('ηßöø必дあ.com', $uri->getHost(Uri::IDN_DECODE));
        $this->assertSame('https://xn--zca0cg32z7rau82strvd.com', $uri->toString());
        $this->assertSame('https://ηßöø必дあ.com', $uri->toString(Uri::IDN_DECODE));

        // Should convert host on clone
        $clone = $uri->withHost('xn--zca0cg32z7rau82strvd.com', Uri::IDN_DECODE);
        $this->assertSame('ηßöø必дあ.com', $clone->getHost());
        $this->assertSame('https://ηßöø必дあ.com', $clone->__toString());

        // Should not attempt conversion
        $clone = $uri->withHost('', Uri::IDN_DECODE);
        $this->assertSame('', $clone->getHost());
    }

    public function testWithComponentsMethod(): void
    {
        $uri = new Uri('http://domain.tld:80/path?query=1#fragment');
        $clone = $uri->withComponents([
            'scheme' => 'https',
            'userInfo' => ['user', 'password'],
            'host' => 'new.domain.tld',
            'port' => 8080,
            'path' => 'new/path',
            'query' => 'new_query=2',
            'fragment' => 'new_fragment',
        ]);

        $this->assertSame(
            'https://user:password@new.domain.tld:8080/new/path?new_query=2#new_fragment',
            $clone->toString()
        );
    }

    public function testWithComponentsMethodInvalidComponent(): void
    {
        $uri = new Uri('http://domain.tld:80/path?query=1#fragment');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid URI component: 'invalid'");
        $clone = $uri->withComponents([
            'invalid' => 'invalid',
        ]);
    }

    public function testStringable(): void
    {
        $uri = new Uri('http://domain.tld:80/path?query=1#fragment');
        $this->assertInstanceOf(Stringable::class, $uri);
        $this->assertSame('http://domain.tld/path?query=1#fragment', $uri->__toString());
    }

    public function testJsonSerializable(): void
    {
        $uri = new Uri('http://domain.tld:80/path?query=1#fragment');
        $this->assertInstanceOf(JsonSerializable::class, $uri);
        $this->assertSame('http://domain.tld/path?query=1#fragment', $uri->jsonSerialize());
        $this->assertSame('"http:\/\/domain.tld\/path?query=1#fragment"', json_encode($uri));
    }

    public function testComponents(): void
    {
        $uri_str = 'http://domain.tld:80/path?query=1#fragment';
        $uri = new Uri($uri_str);
        $this->assertEquals([
            'scheme' => 'http',
            'host' => 'domain.tld',
            'port' => 80,
            'path' => '/path',
            'query' => 'query=1',
            'fragment' => 'fragment',
        ], $uri->getComponents());
        $this->assertEquals(parse_url($uri_str), $uri->getComponents());
    }

    public function testQueryHelperNonAscii(): void
    {
        $uri = new Uri('http://domain.tld:80/path?aaa=ö +-:;%C3%B6');
        $this->assertEquals('aaa=%C3%B6%20+-:;%C3%B6', $uri->getQuery());
        $this->assertEquals(['aaa' => 'ö  -:;ö'], $uri->getQueryItems());
        $this->assertEquals('ö  -:;ö', $uri->getQueryItem('aaa'));

        $uri = $uri->withQueryItem('aaa', 'å -+:;%C3%A5');
        $this->assertEquals('aaa=%C3%A5%20-+:;%C3%A5', $uri->getQuery());
        // @todo: parse_str() function decode as RFC 1738 rather than 3986, need to build own parser
        $this->assertEquals(['aaa' => 'å - :;å'], $uri->getQueryItems());
        // $this->assertEquals(['aaa' => 'å -+:;å'], $uri->getQueryItems());
        $this->assertEquals('å - :;å', $uri->getQueryItem('aaa'));
        // $this->assertEquals('å -+:;å', $uri->getQueryItem('aaa'));
    }

    public function testQueryHelperArrays(): void
    {
        $uri = new Uri('http://domain.tld:80/path?arr%5B0%5D=arr1&arr%5B1%5D=arr2#fragment');
        $this->assertEquals([
            'arr' => ['arr1', 'arr2']
        ], $uri->getQueryItems());
        $this->assertEquals(['arr1', 'arr2'], $uri->getQueryItem('arr'));
        $uri = $uri->withQueryItems([
            'arr' => ['arr3'],
            'assarr' => ['ass1' => 'ass1', 'ass2' => 'ass2'],
            'str' => 'str1',
        ]);
        $this->assertEquals([
            'arr' => ['arr1', 'arr2', 'arr3'],
            'assarr' => ['ass1' => 'ass1', 'ass2' => 'ass2'],
            'str' => 'str1',
        ], $uri->getQueryItems());
        $uri = $uri->withQueryItem('assarr', ['ass1' => 'ass1-new', 'ass3' => 'ass3']);
        $this->assertEquals([
            'arr' => ['arr1', 'arr2', 'arr3'],
            'assarr' => ['ass1' => 'ass1-new', 'ass2' => 'ass2', 'ass3' => 'ass3'],
            'str' => 'str1',
        ], $uri->getQueryItems());
        $uri = $uri->withQueryItems([
            'assarr' => null,
            'str' => null,
        ]);
        $this->assertEquals([
            'arr' => ['arr1', 'arr2', 'arr3'],
        ], $uri->getQueryItems());
    }


    #[DataProvider('provideValidEqual')]
    public function testEquals(UriInterface|string $compareWith): void
    {
        $uri = new Uri('https://ηßöø必дあ.com/åäö/b?a=åäö#åäö');
        $this->assertTrue($uri->equals($compareWith));
    }

    /** @return array<array<UriInterface|string, bool>> */
    public static function provideValidEqual(): array
    {
        return [
            ['https://xn--zca0cg32z7rau82strvd.com/åäö//a/../b?a=åäö#åäö'],
            ['https://ηßöø必дあ.com/åäö/b?a=åäö#åäö'],
            ['https://ηßöø必дあ.com:443/%C3%A5%C3%A4%C3%B6/b?a=%C3%A5%C3%A4%C3%B6#%C3%A5%C3%A4%C3%B6'],
            [new Uri('https://ηßöø必дあ.com/åäö/b?a=åäö#åäö')],
        ];
    }

    #[DataProvider('provideValidNotEqual')]
    public function testNotEquals(UriInterface|string $compareWith): void
    {
        $uri = new Uri('https://ηßöø必дあ.com/åäö/b?a=åäö#åäö');
        $this->assertFalse($uri->equals($compareWith));
    }

    /** @return array<array<UriInterface|string, bool>> */
    public static function provideValidNotEqual(): array
    {
        return [
            ['http://ηßöø必дあ.com/åäö/b?a=åäö#åäö'],
            ['https://notthesame.com/åäö/b?a=åäö#åäö'],
            ['https://ηßöø必дあ.com/abc/b?a=åäö#åäö'],
            ['https://ηßöø必дあ.com/åäö/b?a=abc#åäö'],
            ['https://ηßöø必дあ.com/åäö/b?a=åäö#abc'],
            ['https://ηßöø必дあ.com:80/åäö/b?a=åäö#åäö'],
            ['/åäö/b?a=åäö#åäö'],
            [new Uri('http://ηßöø必дあ.com/åäö/b?a=åäö#åäö')],
        ];
    }

    #[DataProvider('provideInvalidEqual')]
    public function testInvalidEquals(mixed $compareWith, string $type): void
    {
        $uri = new Uri('https://ηßöø必дあ.com/åäö/b?a=åäö#åäö');
        $this->expectException(IncomparableException::class);
        $this->expectExceptionMessage("Can not compare with type '{$type}'");
        $this->assertFalse($uri->equals($compareWith));
    }

    /** @return array<array<UriInterface|string, bool>> */
    public static function provideInvalidEqual(): array
    {
        return [
            [1234, 'int'],
            [null, 'null'],
            [[], 'array'],
        ];
    }

    public function testDeprecation(): void
    {
        $handler = new ErrorHandler();
        $uri = new Uri('https://xn--zca0cg32z7rau82strvd.com');
        $handler->withAll(function () use ($uri) {
            $uri->getHost(Uri::IDNA);
        }, function ($errors) {
            $this->assertEquals('Flag IDNA is deprecated; use IDN_ENCODE instead', $errors[0]->getMessage());
        });
        $handler->withAll(function () use ($uri) {
            $uri->withHost('xn--zca0cg32z7rau82strvd.com', Uri::IDNA);
        }, function ($errors) {
            $this->assertEquals('Flag IDNA is deprecated; use IDN_ENCODE instead', $errors[0]->getMessage());
        });
    }
}
