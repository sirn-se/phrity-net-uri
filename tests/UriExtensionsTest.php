<?php

/**
 * Tests for Net\Uri class.
 * @package Phrity > Net > Uri
 */

declare(strict_types=1);

namespace Phrity\Net;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

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
}
