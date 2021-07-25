<?php

namespace Symfony\Polyfill\Php74;

/**
 * @author Alexandru Punga <alexpunga148@gmail.com>
 *
 * WeakReference class implementation for PHP < 7.4
 */
class WeakReference
{
    protected $ref;

    protected function __construct()
    {
    }

    /**
     * @param $object
     * @return WeakReference
     */
    public static function create($object): WeakReference
    {
        $reference = new WeakReference();
        $reference->ref = $object;

        return $reference;
    }

    /**
     * @return object|null Returns reference on the stored object.
     *                     If reference counter returns 0, returns null and unsets object
     */
    public function &get()
    {
        if (!$this->refcount($this->ref)) {
            unset($this->ref);
        }

        return $this->ref;
    }

    /**
     * Uses debug_zval_dump function to count object references
     */
    protected function refcount($var)
    {
        ob_start();
        debug_zval_dump($var);
        $dump = ob_get_clean();

        $matches = [];
        preg_match('/refcount\(([0-9]+)/', $dump, $matches);

        $count = $matches[1];

        //3 references are added, including when calling debug_zval_dump(), so we need to subtract them
        return $count - 3;
    }
}
