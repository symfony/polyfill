<?php

/*
 * Copyright © 2008 by Yii Software LLC (http://www.yiisoft.com)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *  * Neither the name of Yii Software LLC nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Originally forked from
 * https://github.com/yiisoft/yii2/blob/2.0.15/framework/i18n/MessageFormatter.php
 */

namespace Symfony\Polyfill\Intl\MessageFormatter;

/**
 * A polyfill implementation of the MessageFormatter class provided by the intl extension.
 *
 * It only supports the following message formats:
 *  * plural formatting for english ('one' and 'other' selectors)
 *  * select format
 *  * simple parameters
 *  * integer number parameters
 *
 * It does NOT support the ['apostrophe-friendly' syntax](https://php.net/MessageFormatter.formatMessage).
 * Also messages that are working with the fallback implementation are not necessarily compatible with the
 * PHP intl MessageFormatter so do not rely on the fallback if you are able to install intl extension somehow.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Carsten Brandt <mail@cebe.cc>
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class MessageFormatter
{
    private $locale;
    private $pattern;
    private $tokens;
    private $errorCode = 0;
    private $errorMessage = '';

    public function __construct(string $locale, string $pattern)
    {
        $this->locale = $locale;

        if (!$this->setPattern($pattern)) {
            throw new \IntlException('Message pattern is invalid.');
        }
    }

    public static function create(string $locale, string $pattern)
    {
        $formatter = new static($locale, '-');

        return $formatter->setPattern($pattern) ? $formatter : null;
    }

    public static function formatMessage(string $locale, string $pattern, array $values)
    {
        if (null === $formatter = self::create($locale, $pattern)) {
            return false;
        }

        return $formatter->format($values);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function setPattern(string $pattern)
    {
        try {
            $this->tokens = self::tokenizePattern($pattern);
            $this->pattern = $pattern;
        } catch (\DomainException $e) {
            return false;
        }

        return true;
    }

    public function format(array $values)
    {
        $this->errorCode = 0;
        $this->errorMessage = '';

        if (!$values) {
            return $this->pattern;
        }

        try {
            return self::parseTokens($this->tokens, $values, $this->locale);
        } catch (\DomainException $e) {
            $this->errorCode = -1;
            $this->errorMessage = $e->getMessage();

            return false;
        }
    }

    public function parse(string $string)
    {
        $this->errorCode = -1;
        $this->errorMessage = sprintf('The PHP intl extension is required to use "MessageFormatter::%s()".', __FUNCTION__);

        return false;
    }

    private static function parseTokens(array $tokens, array $values, $locale)
    {
        foreach ($tokens as $i => $token) {
            if (\is_array($token)) {
                $tokens[$i] = self::parseToken($token, $values, $locale);
            }
        }

        return implode('', $tokens);
    }

    private static function tokenizePattern($pattern)
    {
        if (false === $start = $pos = strpos($pattern, '{')) {
            return [$pattern];
        }

        $depth = 1;
        $tokens = [substr($pattern, 0, $pos)];

        while (true) {
            $open = strpos($pattern, '{', 1 + $pos);
            $close = strpos($pattern, '}', 1 + $pos);

            if (false === $open) {
                if (false === $close) {
                    break;
                }
                $open = \strlen($pattern);
            }

            if ($close > $open) {
                ++$depth;
                $pos = $open;
            } else {
                --$depth;
                $pos = $close;
            }

            if (0 === $depth) {
                $tokens[] = explode(',', substr($pattern, 1 + $start, $pos - $start - 1), 3);
                $start = 1 + $pos;
                $tokens[] = substr($pattern, $start, $open - $start);
                $start = $open;
            }

            if (0 !== $depth && (false === $open || false === $close)) {
                break;
            }
        }

        if ($depth) {
            throw new \DomainException('Message pattern is invalid.');
        }

        return $tokens;
    }

    /**
     * Parses pattern based on ICU grammar.
     *
     * @see http://icu-project.org/apiref/icu4c/classMessageFormat.html#details
     */
    private static function parseToken(array $token, array $values, $locale)
    {
        if (!isset($values[$param = trim($token[0])])) {
            return '{'.$param.'}';
        }

        $arg = $values[$param];
        $type = isset($token[1]) ? trim($token[1]) : 'none';
        switch ($type) {
            case 'date': //XXX use DateFormatter?
            case 'time':
            case 'spellout':
            case 'ordinal':
            case 'duration':
            case 'choice':
            case 'selectordinal':
                throw new \DomainException(sprintf('The PHP intl extension is required to use the "%s" message format.', $type));
            case 'number':
                $format = isset($token[2]) ? trim($token[2]) : null;
                if (!is_numeric($arg) || (null !== $format && 'integer' !== $format)) {
                    throw new \DomainException('The PHP intl extension is required to use the "number" message format with non-integer values.');
                }

                $number = number_format($arg); //XXX use NumberFormatter?
                if (null === $format && false !== $pos = strpos($arg, '.')) {
                    // add decimals with unknown length
                    $number .= '.'.substr($arg, $pos + 1);
                }

                return $number;

            case 'none':
                return $arg;

            case 'select':
                /* http://icu-project.org/apiref/icu4c/classicu_1_1SelectFormat.html
                selectStyle = (selector '{' message '}')+
                */
                if (!isset($token[2])) {
                    throw new \DomainException('Message pattern is invalid.');
                }
                $select = self::tokenizePattern($token[2]);
                $c = \count($select);
                $message = false;
                for ($i = 0; 1 + $i < $c; ++$i) {
                    if (\is_array($select[$i]) || !\is_array($select[1 + $i])) {
                        throw new \DomainException('Message pattern is invalid.');
                    }
                    $selector = trim($select[$i++]);
                    if (false === $message && 'other' === $selector || $selector == $arg) {
                        $message = implode(',', $select[$i]);
                    }
                }
                if (false !== $message) {
                    return self::parseTokens(self::tokenizePattern($message), $values, $locale);
                }
                break;

            case 'plural': //TODO make it locale-dependent based on symfony/translation rules
                /* http://icu-project.org/apiref/icu4c/classicu_1_1PluralFormat.html
                pluralStyle = [offsetValue] (selector '{' message '}')+
                offsetValue = "offset:" number
                selector = explicitValue | keyword
                explicitValue = '=' number  // adjacent, no white space in between
                keyword = [^[[:Pattern_Syntax:][:Pattern_White_Space:]]]+
                message: see MessageFormat
                */
                if (!isset($token[2])) {
                    throw new \DomainException('Message pattern is invalid.');
                }
                $plural = self::tokenizePattern($token[2]);
                $c = \count($plural);
                $message = false;
                $offset = 0;
                for ($i = 0; 1 + $i < $c; ++$i) {
                    if (\is_array($plural[$i]) || !\is_array($plural[1 + $i])) {
                        throw new \DomainException('Message pattern is invalid.');
                    }
                    $selector = trim($plural[$i++]);

                    if (1 === $i && 0 === strncmp($selector, 'offset:', 7)) {
                        $pos = strpos(str_replace(["\n", "\r", "\t"], ' ', $selector), ' ', 7);
                        $offset = (int) trim(substr($selector, 7, $pos - 7));
                        $selector = trim(substr($selector, 1 + $pos, \strlen($selector)));
                    }
                    if (false === $message && 'other' === $selector ||
                        '=' === $selector[0] && (int) substr($selector, 1, \strlen($selector)) === $arg ||
                        'one' === $selector && 1 == $arg - $offset
                    ) {
                        $message = implode(',', str_replace('#', $arg - $offset, $plural[$i]));
                    }
                }
                if (false !== $message) {
                    return self::parseTokens(self::tokenizePattern($message), $values, $locale);
                }
                break;
        }

        throw new \DomainException('Message pattern is invalid.');
    }
}
