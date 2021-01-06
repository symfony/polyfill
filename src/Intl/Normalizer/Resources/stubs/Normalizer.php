<?php

class Normalizer extends Symfony\Polyfill\Intl\Normalizer\Normalizer
{
    /**
     * @deprecated since ICU 56 and removed in PHP 8
     */
    public const NONE = 1;
    public const FORM_D = 2;
    public const FORM_KD = 3;
    public const FORM_C = 4;
    public const FORM_KC = 5;
    public const NFD = 2;
    public const NFKD = 3;
    public const NFC = 4;
    public const NFKC = 5;
}
