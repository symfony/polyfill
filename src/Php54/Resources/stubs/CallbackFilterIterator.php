<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! class_exists('CallbackFilterIterator')) :
    class CallbackFilterIterator extends FilterIterator
    {
        private $iterator;
        private $callback;

        public function __construct(Iterator $iterator, $callback)
        {
            $this->iterator = $iterator;
            $this->callback = $callback;
            parent::__construct($iterator);
        }

        public function accept()
        {
            return call_user_func($this->callback, $this->current(), $this->key(), $this->iterator);
        }
    }
endif;
