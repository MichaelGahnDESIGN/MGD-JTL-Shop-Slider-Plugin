<?php

declare(strict_types=1);

namespace MGD\Slider;

// Free Limit: 5 Slider, wird beim Anlegen auf dem Server geprüft
final class Limits
{
    public const FREE_SLIDERS = 5;

    public static function maxSliders(): int
    {
        return self::FREE_SLIDERS;
    }

    public static function canCreate(int $existing): bool
    {
        return $existing < self::maxSliders();
    }

    // Meldung im Backend
    public static function reachedMessage(): string
    {
        return 'Das Limit von ' . self::FREE_SLIDERS . ' Slidern ist erreicht. Mit einer Pro-Lizenz sind es unbegrenzt viele.';
    }
}
