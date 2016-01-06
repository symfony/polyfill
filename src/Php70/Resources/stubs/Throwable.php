<?php

/**
 * Throwable interface for PHP 5.x
 *
 * Throwable is the base interface for any object that can be thrown via a throw statement in PHP 7,
 * including {@see Error} and {@see Exception}.
 *
 * @see http://php.net/manual/en/class.throwable.php
 */
interface Throwable
{
    /**
     * Gets the message
     *
     * Returns the message associated with the thrown object.
     *
     * @see http://php.net/manual/en/throwable.getmessage.php
     *
     * @return string
     */
    public function getMessage();

    /**
     * Gets the exception code
     *
     * Returns the error code associated with the thrown object.
     *
     * @see http://php.net/manual/en/throwable.getcode.php
     *
     * @return int
     */
    public function getCode();

    /**
     * Gets the file in which the exception occurred
     *
     * Returns the name of the file from which the object was thrown.
     *
     * @link http://php.net/manual/en/throwable.getfile.php
     *
     * @return string
     */
    public function getFile();

    /**
     * Gets the line on which the object was instantiated
     *
     * Returns the line number where the thrown object was instantiated.
     *
     * @see http://php.net/manual/en/throwable.getline.php
     *
     * @return int
     */
    public function getLine();

    /**
     * Gets the stack trace
     *
     * Returns the stack trace as an array.
     *
     * @see http://php.net/manual/en/throwable.gettrace.php
     *
     * @return array
     */
    public function getTrace();

    /**
     * Gets the stack trace as a string
     *
     * Returns the stack trace as a string.
     *
     * @see http://php.net/manual/en/throwable.gettraceasstring.php
     *
     * @return string
     */
    public function getTraceAsString();

    /**
     * Returns the previous Throwable
     *
     * Returns any previous Throwable (for example, one provided as the third parameter
     * to {@see Exception::__construct())}.
     *
     * @see http://php.net/manual/en/throwable.getprevious.php
     *
     * @return Throwable|null
     */
    public function getPrevious();

    /**
     * Gets a string representation of the thrown object
     *
     * @see http://php.net/manual/en/throwable.tostring.php
     *
     * @return string
     */
    public function __toString();
}
