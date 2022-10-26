<?php

/**
 * File for Net\Uri class.
 * @package Phrity > Net > Uri
 * @see https://www.rfc-editor.org/rfc/rfc3986
 * @see https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface
 */

namespace Phrity\Net;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Net\Uri class.
 */
class Uri implements UriInterface
{
    private static $port_defaults = [
        'acap' => 674,
        'afp' => 548,
        'dict' => 2628,
        'dns' => 53,
        'ftp' => 21,
        'git' => 9418,
        'gopher' => 70,
        'http' => 80,
        'https' => 443,
        'imap' => 143,
        'ipp' => 631,
        'ipps' => 631,
        'irc' => 194,
        'ircs' => 6697,
        'ldap' => 389,
        'ldaps' => 636,
        'mms' => 1755,
        'msrp' => 2855,
        'mtqp' => 1038,
        'nfs' => 111,
        'nntp' => 119,
        'nntps' => 563,
        'pop' => 110,
        'prospero' => 1525,
        'redis' => 6379,
        'rsync' => 873,
        'rtsp' => 554,
        'rtsps' => 322,
        'rtspu' => 5005,
        'sftp' => 22,
        'smb' => 445,
        'snmp' => 161,
        'ssh' => 22,
        'svn' => 3690,
        'telnet' => 23,
        'ventrilo' => 3784,
        'vnc' => 5900,
        'wais' => 210,
        'ws' => 80,
        'wss' => 443,
    ];

    private $scheme;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query;
    private $fragment;

    /**
     * Create new URI instance ysing a string
     * @param string $uri_string URI as string
     * @throws \InvalidArgumentException If the given URI cannot be parsed
     */
    public function __construct(string $uri_string = '')
    {
        $uri_parsed = parse_url($uri_string);
        if (!is_array($uri_parsed)) {
            throw new InvalidArgumentException('Invalid URL provided');
        }
        foreach ($uri_parsed as $compontent => $value) {
            $this->setCompontent($compontent, $value);
        }
    }


    // ---------- PSR-7 getters ---------------------------------------------------------------------------------------

    /**
     * Retrieve the scheme component of the URI.
     * @return string The URI scheme
     */
    public function getScheme(): string
    {
        return $this->getComponent('scheme') ?? '';
    }

    /**
     * Retrieve the authority component of the URI.
     * @return string The URI authority, in "[user-info@]host[:port]" format
     */
    public function getAuthority(): string
    {
        $host = $this->formatComponent($this->getComponent('host'));
        if ($this->isEmpty($host)) {
            return '';
        }
        $userinfo = $this->formatComponent($this->getUserInfo(), '', '@');
        $port = $this->formatComponent($this->getPort(), ':');
        return "{$userinfo}{$host}{$port}";
    }

    /**
     * Retrieve the user information component of the URI.
     * @return string The URI user information, in "username[:password]" format
     */
    public function getUserInfo(): string
    {
        $user = $this->formatComponent($this->getComponent('user'));
        $pass = $this->formatComponent($this->getComponent('pass'), ':');
        return $this->isEmpty($user) ? '' : "{$user}{$pass}";
    }

    /**
     * Retrieve the host component of the URI.
     * @return string The URI host
     */
    public function getHost(): string
    {
        return $this->getComponent('host') ?? '';
    }

    /**
     * Retrieve the port component of the URI.
     * @return null|int The URI port
     */
    public function getPort(): ?int
    {
        $port = $this->getComponent('port');
        $scheme = $this->getComponent('scheme');
        $default = isset(self::$port_defaults[$scheme]) ? self::$port_defaults[$scheme] : null;
        return $this->isEmpty($port) || $port === $default ? null : $port;
    }

    /**
     * Retrieve the path component of the URI.
     * @return string The URI path
     */
    public function getPath(): string
    {
        return $this->getComponent('path') ?? '';
    }

    /**
     * Retrieve the query string of the URI.
     * @return string The URI query string
     */
    public function getQuery(): string
    {
        return $this->getComponent('query') ?? '';
    }

    /**
     * Retrieve the fragment component of the URI.
     * @return string The URI fragment
     */
    public function getFragment(): string
    {
        return $this->getComponent('fragment') ?? '';
    }


    // ---------- PSR-7 setters ---------------------------------------------------------------------------------------

    /**
     * Return an instance with the specified scheme.
     * @param string $scheme The scheme to use with the new instance
     * @return static A new instance with the specified scheme
     * @throws \InvalidArgumentException for invalid schemes
     * @throws \InvalidArgumentException for unsupported schemes
     */
    public function withScheme($scheme): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('scheme', $scheme);
        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     * @param string $user The user name to use for authority
     * @param null|string $password The password associated with $user
     * @return static A new instance with the specified user information
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('user', $user);
        $clone->setCompontent('pass', $password);
        return $clone;
    }

    /**
     * Return an instance with the specified host.
     * @param string $host The hostname to use with the new instance
     * @return static A new instance with the specified host
     * @throws \InvalidArgumentException for invalid hostnames
     */
    public function withHost($host): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('host', $host);
        return $clone;
    }

    /**
     * Return an instance with the specified port.
     * @param null|int $port The port to use with the new instance
     * @return static A new instance with the specified port
     * @throws \InvalidArgumentException for invalid ports
     */
    public function withPort($port): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('port', $port);
        return $clone;
    }

    /**
     * Return an instance with the specified path.
     * @param string $path The path to use with the new instance
     * @return static A new instance with the specified path
     * @throws \InvalidArgumentException for invalid paths
     */
    public function withPath($path): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('path', $path);
        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     * @param string $query The query string to use with the new instance
     * @return static A new instance with the specified query string
     * @throws \InvalidArgumentException for invalid query strings
     */
    public function withQuery($query): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('query', $query);
        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     * @param string $fragment The fragment to use with the new instance
     * @return static A new instance with the specified fragment
     */
    public function withFragment($fragment): UriInterface
    {
        $clone = clone $this;
        $clone->setCompontent('fragment', $fragment);
        return $clone;
    }


    // ---------- PSR-7 string ----------------------------------------------------------------------------------------

    /**
     * Return the string representation as a URI reference.
     * @return string
     */
    public function __toString(): string
    {
        $scheme = $this->formatComponent($this->getComponent('scheme'), '', ':');
        $authority = $this->formatComponent($this->getAuthority(), '//');
        $path = $this->formatComponent($this->getComponent('path'));
        if ($path && $authority && $path[0] !== '/') {
            $path = "/{$path}";
        }
        if (substr($path, 0, 2) === '//') {
            $path = substr($path, 1);
        }
        $query = $this->formatComponent($this->getComponent('query'), '?');
        $fragment = $this->formatComponent($this->getComponent('fragment'), '#');
        return "{$scheme}{$authority}{$path}{$query}{$fragment}";
    }


    // ---------- Private helper methods ------------------------------------------------------------------------------

    private function encode(string $source, string $keep = ''): string
    {
        $exclude = "[^%\/:=&!\$'()*+,;@{$keep}]+";
        $exp = "/(%{$exclude})|({$exclude})/";
        return preg_replace_callback($exp, function ($matches) {
            if ($e = preg_match('/^(%[0-9a-fA-F]{2})/', $matches[0], $m)) {
                return substr($matches[0], 0, 3) . rawurlencode(substr($matches[0], 3));
            } else {
                return rawurlencode($matches[0]);
            }
        }, $source);
    }

    private function setCompontent(string $component, $value): void
    {
        $value = $this->parseCompontent($component, $value);
        $this->$component = $value;
    }

    private function parseCompontent(string $component, $value)
    {
        if ($this->isEmpty($value)) {
            return null;
        }
        switch ($component) {
            case 'scheme':
                $this->assertString($component, $value);
                $this->assertpattern($component, $value, '/^[a-z][a-z0-9-+.]*$/i');
                return strtolower($value);
            case 'host': // IP-literal / IPv4address / reg-name
                $this->assertString($component, $value);
                return strtolower($value);
            case 'port':
                $this->assertInteger($component, $value);
                if ($value < 0 || $value > 65535) {
                    throw new InvalidArgumentException("Invalid port number");
                }
                return (int)$value;
            case 'path':
                $this->assertString($component, $value);
                $value = $this->encode($value);
                return $value;
            case 'user':
            case 'pass':
            case 'query':
            case 'fragment':
                $this->assertString($component, $value);
                $value = $this->encode($value, '?');
                return $value;
        }
    }

    private function getComponent(string $component)
    {
        return isset($this->$component) ? $this->$component : null;
    }

    private function formatComponent($value, string $before = '', string $after = ''): string
    {
        return $this->isEmpty($value) ? '' : "{$before}{$value}{$after}";
    }

    private function isEmpty($value): bool
    {
        return is_null($value) || $value === '';
    }

    private function assertString(string $component, $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("Invalid '{$component}': Should be a string");
        }
    }

    private function assertInteger(string $component, $value): void
    {
        if (!is_numeric($value) || intval($value) != $value) {
            throw new InvalidArgumentException("Invalid '{$component}': Should be an integer");
        }
    }

    private function assertPattern(string $component, string $value, string $pattern): void
    {
        if (preg_match($pattern, $value) == 0) {
            throw new InvalidArgumentException("Invalid '{$component}': Should match {$pattern}");
        }
    }
}
