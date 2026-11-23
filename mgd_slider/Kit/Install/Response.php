<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Kit\Install;

final class Response
{
    /** @param array<string,string> $headers Namen klein geschrieben */
    public function __construct(public readonly int $status, public readonly array $headers = [], public readonly string $body = '')
    {
    }
}
