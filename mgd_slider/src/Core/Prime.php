<?php

declare(strict_types=1);

namespace MGD\Slider;

/*
 Prime Auswahl - Plattform liefert Kandidaten, hier wird nur gewählt + zur Slide gemacht
 Kandidat: id, sku, name, url, image, imageSet, alt, price (Text), sales (int), categories (list), inStock (bool)
*/
final class Prime
{
    /**
     * @param list<array<string,mixed>> $candidates
     * @param array<string,mixed> $cfg normalisierter Slider (Config::normalize)
     * @param (callable(int):int)|null $rand 0..n-1 (für Tests)
     * @return list<array<string,string>> fertige Slides
     */
    public static function select(array $candidates, array $cfg, ?callable $rand = null): array
    {
        $p = $cfg['prime'];
        $rand ??= static fn (int $n): int => random_int(0, $n - 1);

        $pool = [];
        foreach ($candidates as $c) {
            $id = (string) ($c['id'] ?? '');
            // ohne Bild/Link oder ausverkauft > raus
            if ($id === '' || isset($pool[$id]) || empty($c['image']) || empty($c['url']) || ($c['inStock'] ?? true) === false) {
                continue;
            }
            $pool[$id] = $c;
        }
        $pool = array_values($pool);

        if ($p['source'] === 'manual') {
            // nur gewählte (ID oder Artikelnr), dann gemischt
            $pool = array_values(array_filter($pool, static fn (array $c): bool => in_array((string) $c['id'], $p['products'], true) || in_array((string) ($c['sku'] ?? ''), $p['products'], true)));
        } elseif ($p['source'] === 'category') {
            $pool = array_values(array_filter($pool, static fn (array $c): bool => array_intersect($p['categories'], array_map('strval', (array) ($c['categories'] ?? []))) !== []));
        }

        if ($p['source'] === 'bestseller') {
            // nach Verkäufen absteigend, stabil
            $i = 0;
            $keyed = array_map(static function (array $c) use (&$i): array {
                return [$c, $i++];
            }, $pool);
            usort($keyed, static fn (array $a, array $b): int => ((int) ($b[0]['sales'] ?? 0) <=> (int) ($a[0]['sales'] ?? 0)) ?: $a[1] <=> $b[1]);
            $pool = array_map(static fn (array $k): array => $k[0], $keyed);
        } else {
            $pool = self::shuffle($pool, $rand);
        }

        $out = [];
        foreach (array_slice($pool, 0, (int) $p['count']) as $c) {
            $out[] = self::toSlide($c, $p['buttonLabel']);
        }

        return $out;
    }

    /** @param array<string,mixed> $c @return array<string,string> */
    public static function toSlide(array $c, string $buttonLabel): array
    {
        return [
            'image' => Config::url($c['image'] ?? ''),
            'imageSet' => Config::srcset($c['imageSet'] ?? ''),
            'alt' => Config::text($c['alt'] ?? '', 200) ?: Config::text($c['name'] ?? '', 200),
            'title' => Config::text($c['name'] ?? '', 120),
            'text' => Config::text($c['price'] ?? '', 300),
            'buttonLabel' => $buttonLabel,
            'buttonUrl' => Config::url($c['url'] ?? ''),
            'overlayColor' => '',
            'textColor' => '',
        ];
    }

    // Backend-Suche Name oder Artikelnr
    public static function matchesSearch(string $name, string $sku, string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        return str_contains(mb_strtolower($name), $q) || str_contains(mb_strtolower($sku), $q);
    }

    /**
     * Fisher-Yates, neue Liste
     *
     * @param list<array<string,mixed>> $list @param callable(int):int $rand @return list<array<string,mixed>>
     */
    private static function shuffle(array $list, callable $rand): array
    {
        for ($i = count($list) - 1; $i > 0; $i--) {
            $j = $rand($i + 1);
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        }

        return $list;
    }
}
