<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Normalizer;

/**
 * Normalizer is a PHP fallback implementation of the Normalizer class provided by the intl extension.
 *
 * Since PHP 7.3 Normalizer implementation depends on the ICU version.
 * See https://github.com/php/php-src/blob/3fa88e0ce0ffd9f63672afe114158a07a0204e21/ext/intl/normalizer/normalizer.h#L22) for details.
 * This class auto-adapts to the PHP and ICU versions.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Valentin Udaltsov <udaltsov.valentin@gmail.com>
 *
 * @internal
 */
if (version_compare(PHP_VERSION, '7.3', '>=') && defined('INTL_ICU_VERSION') && version_compare(INTL_ICU_VERSION, '56', '>=')) {
    class Normalizer extends BaseNormalizer
    {
        const NONE = 2;
        const FORM_D = 4;
        const FORM_KD = 8;
        const FORM_C = 16;
        const FORM_KC = 32;
        const FORM_KC_CF = 48;
        const NFD = 4;
        const NFKD = 8;
        const NFC = 16;
        const NFKC = 32;
        const NFKC_CF = 48;

        /**
         * Override method to use new $form default value.
         */
        public static function isNormalized($s, $form = self::NFC)
        {
            return parent::isNormalized($s, $form);
        }

        /**
         * Override method to use new $form default value.
         */
        public static function normalize($s, $form = self::NFC)
        {
            return parent::normalize($s, $form);
        }

        /**
         * {@inheritdoc}
         */
        protected static function isFormNormalized($form)
        {
            if ($form <= static::NONE || $form > static::NFKC_CF) {
                return false;
            }

            // check $form is a power of two
            return 0 === ($form & ($form - 1));
        }
    }
} else {
    class Normalizer extends BaseNormalizer
    {
    }
}
