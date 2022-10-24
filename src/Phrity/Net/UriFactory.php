<?php

/**
 * File for Socket\Factory class.
 * @package Phrity > Socket
 */

namespace Phrity\Socket;

use Psr\Http\Message\UriInterface;

/**
 * Socket\Factory class.
 */
class Factory
{
    public function getClient(UriInterface $uri)
    {
        $errno = $errstr = '';
        $client = stream_socket_client(
            "{$uri}",
            $errno,
            $errstr,
            null,
            STREAM_CLIENT_CONNECT, // STREAM_CLIENT_PERSISTENT
            null
        );
    }
}
