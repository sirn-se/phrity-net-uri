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

    /**
     * @dataProvider provideValidUris
     */
    #[DataProvider('provideValidUris')]
    public function testValidUri($uri_string): void
    {
        $uri = new Uri($uri_string);
        $this->assertSame($uri_string, (string) $uri);
    }

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

    /**
     * @dataProvider provideInvalidUris
     */
    #[DataProvider('provideInvalidUris')]
    public function testInvalidUri($uri_string): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri($uri_string);
    }

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

    /**
     * @dataProvider provideValidPorts
     */
    #[DataProvider('provideValidPorts')]
    public function testValidPort($port, $expected): void
    {
        $uri = (new Uri())->withPort($port);
        $this->assertSame($expected, $uri->getPort());
    }

    public static function provideValidPorts(): array
    {
        return [
            [null, null],
            [0, 0],
            [65535, 65535],
        ];
    }

    /**
     * @dataProvider provideInvalidPorts
     */
    #[DataProvider('provideInvalidPorts')]
    public function testInvalidPort($port): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = (new Uri())->withPort($port);
    }

    public static function provideInvalidPorts(): array
    {
        return [
            [100000],
            [-23],
        ];
    }

    /**
     * @dataProvider provideInvalidPortTypes
     */
    #[DataProvider('provideInvalidPortTypes')]
    public function testInvalidPortType($port): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withPort($port);
    }

    public static function provideInvalidPortTypes(): array
    {
        return [
            ['0'],
            [[]],
        ];
    }

    /**
     * @dataProvider provideDefaultPorts
     */
    #[DataProvider('provideDefaultPorts')]
    public function testDefaultPort($scheme, $port): void
    {
        $uri = new Uri("{$scheme}://domain.tld:{$port}");
        $this->assertSame(null, $uri->getPort());
        $this->assertSame("{$scheme}://domain.tld", (string)$uri);
    }

    /**
     * @dataProvider provideDefaultPorts
     */
    #[DataProvider('provideDefaultPorts')]
    public function testNotDefaultPort($scheme, $port): void
    {
        $port += 100;
        $uri = new Uri("{$scheme}://domain.tld:{$port}");
        $this->assertSame($port, $uri->getPort());
        $this->assertSame("{$scheme}://domain.tld:{$port}", (string)$uri);
    }

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

    /**
     * @dataProvider provideValidSchemes
     */
    #[DataProvider('provideValidSchemes')]
    public function testValidScheme($scheme, $expected): void
    {
        $uri = (new Uri())->withScheme($scheme);
        $this->assertSame($expected, $uri->getScheme());
    }

    public static function provideValidSchemes(): array
    {
        return [
            ['', ''],
            ['http', 'http'],
            ['h-t.+s', 'h-t.+s'],
            ['HtTpS', 'https'],
        ];
    }

    /**
     * @dataProvider provideInvalidSchemes
     */
    #[DataProvider('provideInvalidSchemes')]
    public function testInvalidScheme($scheme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = (new Uri())->withScheme($scheme);
    }

    public static function provideInvalidSchemes(): array
    {
        return [
            ['with space'],
            ['3http'],
            ['ηßöø必Дあ']
        ];
    }

    /**
     * @dataProvider provideInvalidSchemeTypes
     */
    #[DataProvider('provideInvalidSchemeTypes')]
    public function testInvalidSchemeType($scheme): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withScheme($scheme);
    }

    public static function provideInvalidSchemeTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Host tests --------------------------------------------------------------------------------------- //

    /**
     * @dataProvider provideValidHosts
     */
    #[DataProvider('provideValidHosts')]
    public function testValidHost($host, $expected): void
    {
        $uri = (new Uri())->withHost($host);
        $this->assertSame($expected, $uri->getHost());
    }

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

    /**
     * @dataProvider provideInvalidHostTypes
     */
    #[DataProvider('provideInvalidHostTypes')]
    public function testInvalidHosTypet($host): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withHost($host);
    }

    public static function provideInvalidHostTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Path tests --------------------------------------------------------------------------------------- //

    /**
     * @dataProvider provideValidPaths
     */
    #[DataProvider('provideValidPaths')]
    public function testValidPath($path, $expected): void
    {
        $uri = (new Uri())->withPath($path);
        $this->assertSame($expected, $uri->getPath());
    }

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

    /**
     * @dataProvider provideInvalidPathTypes
     */
    #[DataProvider('provideInvalidPathTypes')]
    public function testInvalidPathsType($path): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withPath($path);
    }

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

    /**
     * @dataProvider provideValidQueries
     */
    #[DataProvider('provideValidQueries')]
    public function testValidQuery($query, $expected): void
    {
        $uri = (new Uri())->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

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

    /**
     * @dataProvider provideInvalidQueryTypes
     */
    #[DataProvider('provideInvalidQueryTypes')]
    public function testInvalidQueryType($query): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withQuery($query);
    }

    public static function provideInvalidQueryTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Fragment tests ----------------------------------------------------------------------------------- //

    /**
     * @dataProvider provideValidFragments
     */
    #[DataProvider('provideValidFragments')]
    public function testValidFragment($fragment, $expected): void
    {
        $uri = (new Uri())->withFragment($fragment);
        $this->assertSame($expected, $uri->getFragment());
    }

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

    /**
     * @dataProvider provideInvalidFragmentTypes
     */
    #[DataProvider('provideInvalidFragmentTypes')]
    public function testInvalidFragmentType($fragment): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri())->withFragment($fragment);
    }

    public static function provideInvalidFragmentTypes(): array
    {
        return [
            [null],
            [[]],
        ];
    }


    // ---------- Authority tests ---------------------------------------------------------------------------------- //

    /**
     * @dataProvider provideValidUserInfos
     */
    #[DataProvider('provideValidUserInfos')]
    public function testValidUserInfo($user, $pass, $expected, $include): void
    {
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
        $this->assertSame($expected, $uri->getUserInfo());
        $this->assertSame("{$expected}{$include}domain.tld", $uri->getAuthority());
        $this->assertSame("http://{$uri->getAuthority()}", (string)$uri);
    }

    public static function provideValidUserInfos(): array
    {
        return [
            ['', '', '', ''],
            ['user', '', 'user', '@'],
            ['user', 'pass', 'user:pass', '@'],
            ['', 'pass', '', ''],
            ['with space', 'with%20space', 'with%20space:with%20space', '@'],
            ['.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@', '.-_~!$&\'()*+,;=:@:.-_~!$&\'()*+,;=:@', '@'],
            ['ηßöø', '必Дあ', '%CE%B7%C3%9F%C3%B6%C3%B8:%E5%BF%85%D0%94%E3%81%82', '@'],
        ];
    }

    /**
     * @dataProvider provideInvalidUserInfoTypes
     */
    #[DataProvider('provideInvalidUserInfoTypes')]
    public function testInvalidUserInfoType($user, $pass, $expected, $include): void
    {
        $this->expectException(TypeError::class);
        $uri = (new Uri('http://domain.tld'))->withUserInfo($user, $pass);
    }

    public static function provideInvalidUserInfoTypes(): array
    {
        return [
            [null, null, '', ''],
        ];
    }
}
