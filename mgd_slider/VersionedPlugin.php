<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

/**
 * Plugin-Objekt mit fester Version (nur fuer updated()), alles andere geht ans echte Plugin durch
 */
final class VersionedPlugin
{
    public function __construct(private readonly object $plugin, private readonly string $version)
    {
    }

    // Kit prueft per method_exists, darum nicht nur ueber __call
    public function getPaths(): mixed
    {
        return $this->plugin->getPaths();
    }

    public function getMeta(): object
    {
        $meta = $this->plugin->getMeta();
        $version = $this->version;

        return new class ($meta, $version) {
            public function __construct(private readonly object $meta, private readonly string $version)
            {
            }

            public function getVersion(): string
            {
                return $this->version;
            }

            public function __call(string $name, array $args): mixed
            {
                return $this->meta->$name(...$args);
            }
        };
    }

    public function __call(string $name, array $args): mixed
    {
        return $this->plugin->$name(...$args);
    }
}
