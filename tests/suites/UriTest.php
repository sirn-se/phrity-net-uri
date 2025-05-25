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
use Psr\Http\Message\UriInterface;
use TypeError;

class UriTest extends TestCase
{
    // ---------- General tests ------------------------------------------------------------------------------------ //

    public function testConstruct(): void
    {
        $uri = new Uri('http://user:pass@domain.tld:123/path/page.html?q=query#fragment');
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('user:pass@domain.tld:123', $uri->getAuthority());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('domain.tld', $uri->getHost());
        $this->assertSame(123, $uri->getPort());
        $this->assertSame('/path/page.html', $uri->getPath());
        $this->assertSame('q=query', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
        $this->assertSame('http://user:pass@domain.tld:123/path/page.html?q=query#fragment', (string) $uri);
    }

    public function testWith(): void
    {
        $uri = (new Uri())
            ->withScheme('http')
            ->withUserInfo('user', 'pass')
            ->withHost('domain.tld')
            ->withPort(123)
            ->withPath('/path/page.html')
            ->withQuery('q=query')
            ->withFragment('fragment');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('user:pass@domain.tld:123', $uri->getAuthority());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('domain.tld', $uri->getHost());
        $this->assertSame(123, $uri->getPort());
        $this->assertSame('/path/page.html', $uri->getPath());
        $this->assertSame('q=query', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
        $this->assertSame('http://user:pass@domain.tld:123/path/page.html?q=query#fragment', (string) $uri);
    }

    public function testImmutability(): void
    {
        $uri = new Uri();
        $this->assertNotSame($uri, $uri = $uri->withScheme('http'));
        $this->assertNotSame($uri, $uri = $uri->withUserInfo('user', 'pass'));
        $this->assertNotSame($uri, $uri = $uri->withHost('domain.tld'));
        $this->assertNotSame($uri, $uri = $uri->withPort(123));
        $this->assertNotSame($uri, $uri = $uri->withPath('/path/page.html'));
        $this->assertNotSame($uri, $uri = $uri->withQuery('q=query'));
        $this->assertNotSame($uri, $uri = $uri->withFragment('fragment'));
    }

    public function testInterface(): void
    {
        $this->assertInstanceOf(UriInterface::class, new Uri());
    }


    // ---------- URI string tests --------------------------------------------------------------------------------- //

    #[DataProvider('provideValidUris')]
    public function testValidUri(string $uriString): void
    {
        $uri = new Uri($uriString);
        $this->assertSame($uriString, (string)$uri);
    }

    /** @return array<array<string>> */
    public static function provideValidUris(): array
    {
        return [
            ['urn:path-rootless'],
            ['urn:path:with:colon'],
            ['urn:/path-absolute'],
            ['urn:/'],
            ['urn:'],
            ['/'],
            ['relative/'],
            ['0'],
            [''],
            ['//domain.tld'],
            ['//domain.tld:1234'],
            ['//domain.tld/'],
            ['//domain.tld?query#fragment'],
            ['?query'],
            ['?query!=query1&query2=query2'],
            ['#fragment'],
            ['./path1/../path2'],
            ['a://0:0@0/0?0#0'],
            ['http://ηßöø必дあ.com/'],
            ['http://localhost'],
            ['localhost',],
            ['http://localhost'],
            ['/a-zA-Z0-9.-_~!$&\'()*+,;=:@?a-zA-Z0-9.-_~!$&\'()*+,;=:@#a-zA-Z0-9.-_~!$&\'()*+,;=:@'],
            ['mailto:foo'],
            ['http://[2a00:f48:1008::212:183:10]#frag'],
            ['http://[2a00:f48:1008::212:183:10]:56?foo=bar'],
            ['tel:+1-816-555-1212'],
            ['unix:///tmp/test.sock'],
            ['file:///tmp/filename.ext'],
            ['http://'], // uncertain, currently valid
        ];
    }

    #[DataProvider('provideInvalidUris')]
    public function testInvalidUri(string $uriString): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri($uriString);
    }

    /** @return array<array<string>> */
    public static function provideInvalidUris(): array
    {
        return [
            ['urn://host:with:colon'], // only colons within [] for ipv6
            ['0://0:0@0/0?0#0'], // scheme must begin with a letter
            ['//user:pass@:8080'], // userinfo and port require host
            ['//:pass@test.se'], // no pass without user
        ];
    }


    // ---------- Port tests --------------------------------------------------------------------------------------- //

    #[DataProvider('provideValidPorts')]
    public function testValidPort(int|null $port, int|null $expected): void
    {
        $uri = (new Uri())->withPort($port);
        $this->assertSame($expected, $uri->getPort());
    }

    /** @return array<array<int|null>> */
    public static function provideValidPorts(): array
    {
        return [
            [null, null],
            [0, 0],
            [65535, 65535],
        ];
    }

    #[DataProvider('provideInvalidPorts')]
    public function testInvalidPort(int $port): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = (new Uri())->withPort($port);
    }

    /** @return array<array<int>> */
    public static function provideInvalidPorts(): array
    {
        return [
            [100000],
            [-23],
        ];
    }

    #[DataProvider('provideInvalidPortTypes')]
    public function testInvalidPortType(mixed $port): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withPort($port);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidPortTypes(): array
    {
        return [
            ['0'],
            [[]],
        ];
    }

    #[DataProvider('provideDefaultPorts')]
    public function testDefaultPort(string $scheme, int $port): void
    {
        $uri = new Uri("{$scheme}://domain.tld:{$port}");
        $this->assertSame(null, $uri->getPort());
        $this->assertSame("{$scheme}://domain.tld", (string)$uri);
    }

    #[DataProvider('provideDefaultPorts')]
    public function testNotDefaultPort(string $scheme, int $port): void
    {
        $port += 100;
        $uri = new Uri("{$scheme}://domain.tld:{$port}");
        $this->assertSame($port, $uri->getPort());
        $this->assertSame("{$scheme}://domain.tld:{$port}", (string)$uri);
    }

    /** @return array<array<string|int>> */
    public static function provideDefaultPorts(): array
    {
        return [
            ['acap', 674],
            ['afp', 548],
            ['dict', 2628],
            ['dns', 53],
            ['ftp', 21],
            ['git', 9418],
            ['gopher', 70],
            ['http', 80],
            ['https', 443],
            ['imap', 143],
            ['ipp', 631],
            ['ipps', 631],
            ['irc', 194],
            ['ircs', 6697],
            ['ldap', 389],
            ['ldaps', 636],
            ['mms', 1755],
            ['msrp', 2855],
            ['mtqp', 1038],
            ['nfs', 111],
            ['nntp', 119],
            ['nntps', 563],
            ['pop', 110],
            ['prospero', 1525],
            ['redis', 6379],
            ['rsync', 873],
            ['rtsp', 554],
            ['rtsps', 322],
            ['rtspu', 5005],
            ['sftp', 22],
            ['smb', 445],
            ['snmp', 161],
            ['ssh', 22],
            ['svn', 3690],
            ['telnet', 23],
            ['ventrilo', 3784],
            ['vnc', 5900],
            ['wais', 210],
            ['ws', 80],
            ['wss', 443],
        ];
    }

    public function testPortOnSchemeChanges(): void
    {
        $uri = new Uri("http://domain.tld:80");
        $this->assertSame(null, $uri->getPort());
        $this->assertSame("http://domain.tld", (string)$uri);
        $uri = $uri->withPort(443);
        $this->assertSame(443, $uri->getPort());
        $this->assertSame("http://domain.tld:443", (string)$uri);
        $uri = $uri->withScheme('https');
        $this->assertSame(null, $uri->getPort());
        $this->assertSame("https://domain.tld", (string)$uri);
        $uri = $uri->withScheme('ftp');
        $this->assertSame(443, $uri->getPort());
        $this->assertSame("ftp://domain.tld:443", (string)$uri);
    }


    // ---------- Scheme tests ------------------------------------------------------------------------------------- //

    #[DataProvider('provideValidSchemes')]
    public function testValidScheme(string $scheme, string $expected): void
    {
        $uri = (new Uri())->withScheme($scheme);
        $this->assertSame($expected, $uri->getScheme());
    }

    /** @return array<array<string>> */
    public static function provideValidSchemes(): array
    {
        return [
            ['', ''],
            ['http', 'http'],
            ['h-t.+s', 'h-t.+s'],
            ['HtTpS', 'https'],
        ];
    }

    #[DataProvider('provideInvalidSchemes')]
    public function testInvalidScheme(string $scheme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = (new Uri())->withScheme($scheme);
    }

    /** @return array<array<string>> */
    public static function provideInvalidSchemes(): array
    {
        return [
            ['with space'],
            ['3http'],
            ['ηßöø必Дあ']
        ];
    }

    #[DataProvider('provideInvalidSchemeTypes')]
    public function testInvalidSchemeType(mixed $scheme): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withScheme($scheme);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidSchemeTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Host tests --------------------------------------------------------------------------------------- //

    #[DataProvider('provideValidHosts')]
    public function testValidHost(string $host, string $expected): void
    {
        $uri = (new Uri())->withHost($host);
        $this->assertSame($expected, $uri->getHost());
    }

    /** @return array<array<string>> */
    public static function provideValidHosts(): array
    {
        return [
            ['', ''],
            ['MyDomain.COM', 'mydomain.com'],
            ['ηßöø必Дあ.com', 'ηßöø必дあ.com'],
            ['[2a00:f48:1008::212:183:10]', '[2a00:f48:1008::212:183:10]'],
            ['127.0.0.1', '127.0.0.1'],
        ];
    }

    #[DataProvider('provideInvalidHostTypes')]
    public function testInvalidHosTypet(mixed $host): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withHost($host);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidHostTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Path tests --------------------------------------------------------------------------------------- //

    #[DataProvider('provideValidPaths')]
    public function testValidPath(string $path, string $expected): void
    {
        $uri = (new Uri())->withPath($path);
        $this->assertSame($expected, $uri->getPath());
    }

    /** @return array<array<string>> */
    public static function provideValidPaths(): array
    {
        return [
            ['', ''],
            ['relative', 'relative'],
            ['/path/to//some///thing', '/path/to//some///thing'],
            ['/../relative/./path/..', '/../relative/./path/..'],
            ['/with space', '/with%20space'],
            ['/€', '/%E2%82%AC'],
            ['/encoded%20space', '/encoded%20space'],
            ['/invalid%k9', '/invalid%25k9'],
            ['/.-_~!$&\'()*+,;=:@', '/.-_~!$&\'()*+,;=:@'],
            ['/🇺🇦/🛃', '/%F0%9F%87%BA%F0%9F%87%A6/%F0%9F%9B%83'],
            ['ηßöø必Дあ', '%CE%B7%C3%9F%C3%B6%C3%B8%E5%BF%85%D0%94%E3%81%82'],
        ];
    }

    #[DataProvider('provideInvalidPathTypes')]
    public function testInvalidPathsType(mixed $path): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withPath($path);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidPathTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    public function testPathWithHost(): void
    {
        $uri = (new Uri())->withHost('domain.tld')->withPath('my/path');
        $this->assertSame('//domain.tld/my/path', (string)$uri);
        $uri = (new Uri())->withHost('domain.tld')->withPath('//my/path');
        $this->assertSame('//domain.tld//my/path', (string)$uri);
    }


    // ---------- Query tests -------------------------------------------------------------------------------------- //

    #[DataProvider('provideValidQueries')]
    public function testValidQuery(string $query, string $expected): void
    {
        $uri = (new Uri())->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

    /** @return array<array<string>> */
    public static function provideValidQueries(): array
    {
        return [
            ['', ''],
            ['with space', 'with%20space'],
            ['€', '%E2%82%AC'],
            ['encoded%20space', 'encoded%20space'],
            ['invalid%k9', 'invalid%25k9'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@'],
            ['a=1&b&c=&d=4', 'a=1&b&c=&d=4'],
            ['?=🇺🇦/🛃', '?=%F0%9F%87%BA%F0%9F%87%A6/%F0%9F%9B%83'],
            ['η=ß&ö=ø必Дあ', '%CE%B7=%C3%9F&%C3%B6=%C3%B8%E5%BF%85%D0%94%E3%81%82'],
        ];
    }

    #[DataProvider('provideInvalidQueryTypes')]
    public function testInvalidQueryType(mixed $query): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withQuery($query);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidQueryTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Fragment tests ----------------------------------------------------------------------------------- //

    #[DataProvider('provideValidFragments')]
    public function testValidFragment(string $fragment, string $expected): void
    {
        $uri = (new Uri())->withFragment($fragment);
        $this->assertSame($expected, $uri->getFragment());
    }

    /** @return array<array<string>> */
    public static function provideValidFragments(): array
    {
        return [
            ['', ''],
            ['with space', 'with%20space'],
            ['€', '%E2%82%AC'],
            ['encoded%20space', 'encoded%20space'],
            ['invalid%k9', 'invalid%25k9'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@'],
            ['#🇺🇦/🛃', '%23%F0%9F%87%BA%F0%9F%87%A6/%F0%9F%9B%83'],
            ['ηßöø必Дあ', '%CE%B7%C3%9F%C3%B6%C3%B8%E5%BF%85%D0%94%E3%81%82'],
        ];
    }

    #[DataProvider('provideInvalidFragmentTypes')]
    public function testInvalidFragmentType(mixed $fragment): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withFragment($fragment);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidFragmentTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Authority tests ---------------------------------------------------------------------------------- //

    #[DataProvider('provideValidUserInfos')]
    public function testValidUserInfo(string $user, string $pass, string $expected, string $include): void
    {
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
        $this->assertSame($expected, $uri->getUserInfo());
        $this->assertSame("{$expected}{$include}domain.tld", $uri->getAuthority());
        $this->assertSame("http://{$uri->getAuthority()}", (string)$uri);
    }

    /** @return array<array<string>> */
    public static function provideValidUserInfos(): array
    {
        return [
            ['', '', '', ''],
            ['user', '', 'user', '@'],
            ['user', 'pass', 'user:pass', '@'],
            ['', 'pass', '', ''],
            ['with space', 'with%20space', 'with%20space:with%20space', '@'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=%3A%40:.-_~!$&\'()*+,;=%3A%40', '@'],
            ['ηßöø', '必Дあ', '%CE%B7%C3%9F%C3%B6%C3%B8:%E5%BF%85%D0%94%E3%81%82', '@'],
        ];
    }

    #[DataProvider('provideValidUserInfosDecoded')]
    public function testValidUserInfoDecoded(string $user, string $pass, string $expected, string $include): void
    {
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
        $this->assertSame($expected, $uri->getUserInfo(Uri::URI_DECODE));
        $this->assertSame("{$expected}{$include}domain.tld", $uri->getAuthority(Uri::URI_DECODE));
        $this->assertSame("http://{$uri->getAuthority()}", (string)$uri);
    }

    /** @return array<array<string>> */
    public static function provideValidUserInfosDecoded(): array
    {
        return [
            ['', '', '', ''],
            ['user', '', 'user', '@'],
            ['user', 'pass', 'user:pass', '@'],
            ['', 'pass', '', ''],
            ['with space', 'with%20space', 'with space:with space', '@'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@:.-_~!$&\'()*+,;=:@', '@'],
            ['ηßöø', '必Дあ', 'ηßöø:必Дあ', '@'],
        ];
    }

    #[DataProvider('provideValidUserInfosEncoded')]
    public function testValidUserInfoEncoded(string $user, string $pass, string $expected, string $include): void
    {
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
        $this->assertSame($expected, $uri->getUserInfo(Uri::URI_ENCODE));
        $this->assertSame("{$expected}{$include}domain.tld", $uri->getAuthority(Uri::URI_ENCODE));
        $this->assertSame("http://{$uri->getAuthority()}", (string)$uri);
    }

    /** @return array<array<string>> */
    public static function provideValidUserInfosEncoded(): array
    {
        return [
            ['', '', '', ''],
            ['user', '', 'user', '@'],
            ['user', 'pass', 'user:pass', '@'],
            ['', 'pass', '', ''],
            ['with space', 'with%20space', 'with%20space:with%20space', '@'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=%3A%40:.-_~!$&\'()*+,;=%3A%40', '@'],
            ['ηßöø', '必Дあ', 'ηßöø:必Дあ', '@'],
        ];
    }

    #[DataProvider('provideInvalidUserInfoTypes')]
    public function testInvalidUserInfoType(mixed $user, mixed $pass, mixed $expected, mixed $include): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
    }

    /** @return array<array<mixed>> */
    public static function provideInvalidUserInfoTypes(): array
    {
        return [
            [null, null, '', ''],
        ];
    }
}
