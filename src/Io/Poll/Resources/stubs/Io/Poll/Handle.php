<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Io\Poll;

if (\PHP_VERSION_ID < 80600) {
    /**
     * @internal only classes provided by PHP core or extensions may implement this interface
     */
    interface Handle
    {
    }
}
