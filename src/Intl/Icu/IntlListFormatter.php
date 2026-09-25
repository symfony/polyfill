<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu;

/**
 * @author Ayesh Karunaratne <ayesh@aye.sh>
 *
 * @internal
 */
class IntlListFormatter
{
    public const TYPE_AND = 0;
    public const TYPE_OR = 1;
    public const TYPE_UNITS = 2;

    public const WIDTH_WIDE = 0;
    public const WIDTH_SHORT = 1;
    public const WIDTH_NARROW = 2;

    private $type;
    private $width;
    private $region;

    private const TYPE_MAP = [
        self::TYPE_AND => 'standard',
        self::TYPE_OR => 'or',
        self::TYPE_UNITS => 'unit',
    ];

    private const WIDTH_MAP = [
        self::WIDTH_WIDE => '',
        self::WIDTH_SHORT => '-short',
        self::WIDTH_NARROW => '-narrow',
    ];

    private const EN_LIST_PATTERNS = [
        'listPattern-type-standard' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, and {1}',
            2 => '{0} and {1}',
        ],
        'listPattern-type-or' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, or {1}',
            2 => '{0} or {1}',
        ],
        'listPattern-type-or-narrow' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, or {1}',
            2 => '{0} or {1}',
        ],
        'listPattern-type-or-short' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, or {1}',
            2 => '{0} or {1}',
        ],
        'listPattern-type-standard-narrow' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, {1}',
            2 => '{0}, {1}',
        ],
        'listPattern-type-standard-short' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, & {1}',
            2 => '{0} & {1}',
        ],
        'listPattern-type-unit' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, {1}',
            2 => '{0}, {1}',
        ],
        'listPattern-type-unit-narrow' => [
            'start' => '{0} {1}',
            'middle' => '{0} {1}',
            'end' => '{0} {1}',
            2 => '{0} {1}',
        ],
        'listPattern-type-unit-short' => [
            'start' => '{0}, {1}',
            'middle' => '{0}, {1}',
            'end' => '{0}, {1}',
            2 => '{0}, {1}',
        ],
    ];

    // CLDR's en_001 drops the serial comma, and en_IN inherits from it
    private const EN_001_LIST_PATTERNS = [
        'listPattern-type-standard' => ['end' => '{0} and {1}'],
        'listPattern-type-standard-short' => ['end' => '{0} and {1}', 2 => '{0} and {1}'],
        'listPattern-type-or' => ['end' => '{0} or {1}'],
        'listPattern-type-or-narrow' => ['end' => '{0} or {1}'],
        'listPattern-type-or-short' => ['end' => '{0} or {1}'],
    ];

    private const EN_IN_LIST_PATTERNS = [
        'listPattern-type-standard-narrow' => ['end' => '{0}, and {1}'],
    ];

    // Regions of en_001 and of its descendants in CLDR 48, plus en_CA, which overrides the same patterns
    private const EN_001_REGIONS = [
        '001', '150', 'AG', 'AI', 'AT', 'AU', 'BB', 'BE', 'BM', 'BS', 'BW', 'BZ', 'CA', 'CC', 'CH', 'CK', 'CM', 'CX', 'CY', 'CZ',
        'DE', 'DG', 'DK', 'DM', 'EE', 'ER', 'ES', 'FI', 'FJ', 'FK', 'FM', 'FR', 'GB', 'GD', 'GE', 'GG', 'GH', 'GI', 'GM', 'GS',
        'GY', 'HK', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IT', 'JE', 'JM', 'KE', 'KI', 'KN', 'KY', 'LC', 'LR', 'LS', 'LT',
        'LV', 'MG', 'MO', 'MS', 'MT', 'MU', 'MV', 'MW', 'MY', 'NA', 'NF', 'NG', 'NL', 'NO', 'NR', 'NU', 'NZ', 'PG', 'PK', 'PL',
        'PN', 'PT', 'PW', 'RO', 'RW', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SK', 'SL', 'SS', 'SX', 'SZ', 'TC', 'TK', 'TO',
        'TT', 'TV', 'TZ', 'UA', 'UG', 'VC', 'VG', 'VU', 'WS', 'ZA', 'ZM', 'ZW',
    ];

    public function __construct(string $locale, int $type = self::TYPE_AND, int $width = self::WIDTH_WIDE)
    {
        if ('en' !== $locale && 0 !== strpos($locale, 'en')) {
            if (80000 > \PHP_VERSION_ID) {
                throw new \InvalidArgumentException('Invalid locale, only "en" and "en-*" locales are supported.');
            }

            throw new \ValueError('Invalid locale, only "en" and "en-*" locales are supported.');
        }

        if (!isset(self::TYPE_MAP[$type])) {
            if (80000 > \PHP_VERSION_ID) {
                throw new \InvalidArgumentException('Argument #2 ($type) must be one of IntlListFormatter::TYPE_AND, IntlListFormatter::TYPE_OR, or IntlListFormatter::TYPE_UNITS.');
            }

            throw new \ValueError('Argument #2 ($type) must be one of IntlListFormatter::TYPE_AND, IntlListFormatter::TYPE_OR, or IntlListFormatter::TYPE_UNITS.');
        }

        if (!isset(self::WIDTH_MAP[$width])) {
            if (80000 > \PHP_VERSION_ID) {
                throw new \InvalidArgumentException('Argument #3 ($width) must be one of IntlListFormatter::WIDTH_WIDE, IntlListFormatter::WIDTH_SHORT, or IntlListFormatter::WIDTH_NARROW.');
            }

            throw new \ValueError('Argument #3 ($width) must be one of IntlListFormatter::WIDTH_WIDE, IntlListFormatter::WIDTH_SHORT, or IntlListFormatter::WIDTH_NARROW.');
        }

        $this->type = $type;
        $this->width = $width;

        if (preg_match('/^en(?:[-_][a-z]{4})?[-_]([a-z]{2}|\d{3})(?=$|[-_@])/i', $locale, $m) && \in_array($region = strtoupper($m[1]), self::EN_001_REGIONS, true)) {
            $this->region = $region;
        }
    }

    public function format(array $strings): string
    {
        $count = \count($strings);

        if (0 === $count) {
            return '';
        }

        $strings = array_values($strings);

        if (1 === $count) {
            return (string) $strings[0];
        }

        $key = 'listPattern-type-'.self::TYPE_MAP[$this->type].self::WIDTH_MAP[$this->width];
        $pattern = self::EN_LIST_PATTERNS[$key];

        if (null !== $this->region) {
            $pattern = (self::EN_001_LIST_PATTERNS[$key] ?? []) + $pattern;

            if ('IN' === $this->region) {
                $pattern = (self::EN_IN_LIST_PATTERNS[$key] ?? []) + $pattern;
            }
        }

        if (2 === $count) {
            return strtr($pattern[2], ['{0}' => (string) $strings[0], '{1}' => (string) $strings[1]]);
        }

        $result = strtr($pattern['start'], ['{0}' => (string) $strings[0], '{1}' => (string) $strings[1]]);

        for ($i = 2; $i < $count - 1; ++$i) {
            $result = strtr($pattern['middle'], ['{0}' => $result, '{1}' => (string) $strings[$i]]);
        }

        return strtr($pattern['end'], ['{0}' => $result, '{1}' => (string) $strings[$count - 1]]);
    }

    public function getErrorCode(): int
    {
        return 0;
    }

    public function getErrorMessage(): string
    {
        return '';
    }
}
