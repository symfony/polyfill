<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Intl\Transliterator as p;

if (!function_exists('transliterator_create')) {
    function transliterator_create($id, $direction = null) { return p\Transliterator::create($id, $direction); }
    function transliterator_create_from_rules($rules, $direction = null) { return p\Transliterator::createFromRules($rules, $direction); }
    function transliterator_list_ids() { return p\Transliterator::listIDs(); }
    function transliterator_create_inverse() { return p\Transliterator::createInverse(); }
    function transliterator_transliterate(p\Transliterator $trans, $subject, $start = null, $end = null) { return $trans->transliterate($subject, $start, $end); }
    function transliterator_get_error_code(p\Transliterator $trans) { return $trans->getErrorCode(); }
    function transliterator_get_error_message(p\Transliterator $trans) { return $trans->getErrorMessage(); }
}
