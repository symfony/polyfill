<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!function_exists('transliterator_create')) {
    function transliterator_create($id, $direction = null) { return \Transliterator::create($id, $direction); }
    function transliterator_create_from_rules($rules, $direction = null) { return \Transliterator::createFromRules($rules, $direction); }
    function transliterator_list_ids() { return \Transliterator::listIDs(); }
    function transliterator_create_inverse() {return \Transliterator::createInverse(); }
    function transliterator_transliterate(\Transliterator $trans, $subject, $start = null, $end = null) { return $trans->transliterate($subject, $start, $end); }
    function transliterator_get_error_code(\Transliterator $trans) { return $trans->getErrorCode(); }
    function transliterator_get_error_message(\Transliterator $trans) { return $trans->getErrorMessage(); }
}
