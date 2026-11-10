<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Tables\Tests\Support\Doubles;

use HTMLPurifier;
use RuntimeException;

/**
 * A purifier that counts its calls and can be told to fail, so a test reaches the
 * sanitizer's failure and fallback branches on purpose.
 */
final class RecordingPurifier extends HTMLPurifier
{
    /**
     * @var int How many times `purify()` was called.
     */
    public int $calls = 0;

    /**
     * @param  ?string  $failWith  The message to throw on every call, or null to succeed.
     */
    public function __construct(private readonly ?string $failWith = null) {}

    /**
     * Count the call, then throw the configured failure or return a marker.
     *
     * @param  mixed  $html  The HTML to purify, ignored.
     * @param  mixed  $config  Ignored.
     * @return string A fixed marker.
     */
    #[\Override]
    public function purify($html, $config = null)
    {
        $this->calls++;

        if ($this->failWith !== null) {
            throw new RuntimeException($this->failWith);
        }

        return 'purified';
    }
}
