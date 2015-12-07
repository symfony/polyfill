<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php54;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class Php54
{
    public static function hex2bin($data)
    {
        $len = strlen($data);

        if (null === $len) {
            return;
        }
        if ($len % 2) {
            trigger_error('hex2bin(): Hexadecimal input string must have an even length', E_USER_WARNING);

            return false;
        }

        $data = pack('H*', $data);

        if (false !== strpos($data, "\0")) {
            return false;
        }

        return $data;
    }

    public static function http_response_code($responseCode)
    {
        static $currentCode = 200;

        $messages = array(
          100 => 'Continue',
          101 => 'Switching Protocols',
          200 => 'OK',
          201 => 'Created',
          202 => 'Accepted',
          203 => 'Non-Authoritative Information',
          204 => 'No Content',
          205 => 'Reset Content',
          206 => 'Partial Content',
          300 => 'Multiple Choices',
          301 => 'Moved Permanently',
          302 => 'Moved Temporarily',
          303 => 'See Other',
          304 => 'Not Modified',
          305 => 'Use Proxy',
          307 => 'Temporary Redirect',
          308 => 'Permanent Redirect',
          400 => 'Bad Request',
          401 => 'Unauthorized',
          402 => 'Payment Required',
          403 => 'Forbidden',
          404 => 'Not Found',
          405 => 'Method Not Allowed',
          406 => 'Not Acceptable',
          407 => 'Proxy Authentication Required',
          408 => 'Request Time-out',
          409 => 'Conflict',
          410 => 'Gone',
          411 => 'Length Required',
          412 => 'Precondition Failed',
          413 => 'Request Entity Too Large',
          414 => 'Request-URI Too Large',
          415 => 'Unsupported Media Type',
          416 => 'Requested Range Not Satisfiable',
          417 => 'Expectation Failed',
          500 => 'Internal Server Error',
          501 => 'Not Implemented',
          502 => 'Bad Gateway',
          503 => 'Service Unavailable',
          504 => 'Gateway Time-out',
          505 => 'HTTP Version not supported',
        );

        $previousCode = $currentCode;

        if (is_numeric($responseCode)) {
            $currentCode = (int) $responseCode;
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
            $message = isset($messages[$responseCode]) ? $messages[$responseCode] : 'Unknown Status Code';
            header($protocol . ' ' . $responseCode . ' ' . $message);
        } elseif (null !== $responseCode) {
            $type = gettype($responseCode);
            trigger_error('http_response_code() expects parameter 1 to be long, ' . $type . ' given', E_USER_WARNING);
        }

        return $previousCode;
    }
}
