<?php

/**
 * File for Net\Uri class.
 * @package Phrity > Net
 * @see https://www.rfc-editor.org/rfc/rfc3986#section-3.1
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
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme(): string
    {
        return $this->getComponent('scheme') ?? '';
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
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
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo(): string
    {
        $user = $this->formatComponent($this->getComponent('user'));
        $pass = $this->formatComponent($this->getComponent('pass'), ':');
        return $this->isEmpty($user) ? '' : "{$user}{$pass}";
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost(): string
    {
        return $this->getComponent('host') ?? '';
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
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
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath(): string
    {
        return $this->getComponent('path') ?? '';
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery(): string
    {
        return $this->getComponent('query') ?? '';
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment(): string
    {
        return $this->getComponent('fragment') ?? '';
    }


    // ---------- PSR-7 setters ---------------------------------------------------------------------------------------

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid schemes.
     * @throws \InvalidArgumentException for unsupported schemes.
     */
    public function withScheme($scheme): static
    {
        $clone = clone $this;
        $clone->setCompontent('scheme', $scheme);
        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): static
    {
        $clone = clone $this;
        $clone->setCompontent('user', $user);
        $clone->setCompontent('pass', $password);
        return $clone;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
// @todo: What host is invalid?
    public function withHost($host): static
    {
        $clone = clone $this;
        $clone->setCompontent('host', $host);
        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port): static
    {
        $clone = clone $this;
        $clone->setCompontent('port', $port);
        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If an HTTP path is intended to be host-relative rather than path-relative
     * then it must begin with a slash ("/"). HTTP paths not starting with a slash
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
// @todo: What path is invalid?
    public function withPath($path): static
    {
        $clone = clone $this;
        $clone->setCompontent('path', $path);
        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
// @todo: What query is invalid?
    public function withQuery($query): static
    {
        $clone = clone $this;
        $clone->setCompontent('query', $query);
        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment): static
    {
        $clone = clone $this;
        $clone->setCompontent('fragment', $fragment);
        return $clone;
    }


    // ---------- PSR-7 string ----------------------------------------------------------------------------------------

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
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
/*
    getQueryItens
    getQueryIten
    withQueryItens
    withQueryIten

    withAbsolutePath
    getPathDirectory
    withPathDirectory
    getPathBasename
    withPathBasename

    getDefaultPort
    IDNA

    Guzzlestativ:
    isDefaultPort
    isAbsolute
    isNetworkPathReference
    isAbsolutePathReference
    isRelativePathReference
    isSameDocumentReference
    withQueryValue
    withQueryValues
    withoutQueryValue
*/



    public static function createFromString($uri = ''): self
    {
        return new self($uri);
    }


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
            default:
                return null;
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
