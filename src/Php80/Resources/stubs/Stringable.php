<?php

if (!interface_exists('Stringable')) {
    interface Stringable
    {
        /**
         * @return string
         */
        public function __toString();
    }
}
