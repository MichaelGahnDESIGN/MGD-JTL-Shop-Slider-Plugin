<?php

declare(strict_types=1);

namespace MGD\Slider;

// Datenmodell Slider - Defaults aus options.json, kein Framework-Code (Woo/Shopware/JTL gleiche Datei)
final class Config
{
    /** @var array<string,mixed>|null */
    private static ?array $options = null;

    /** @return array<string,mixed> */
    public static function options(): array
    {
        if (self::$options === null) {
            // im Plugin liegt options.json neben der Klasse, im Pro-Repo eine Ebene höher > core/options.json
            $file = is_file(__DIR__ . '/options.json') ? __DIR__ . '/options.json' : __DIR__ . '/../options.json';
            $json = json_decode((string) file_get_contents($file), true);
            self::$options = is_array($json) ? $json : [];
        }

        return self::$options;
    }

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return self::options()['defaults'];
    }

    /** @param array<string,mixed> $raw @return array<string,mixed> bereinigter Slider inkl. Slides */
    public static function normalize(array $raw): array
    {
        $d = self::defaults();
        $o = self::options();
        $r = $o['ranges'];
        $c = $o['choices'];
        $prime = is_array($raw['prime'] ?? null) ? $raw['prime'] : [];
        $slides = [];
        foreach (is_array($raw['slides'] ?? null) ? $raw['slides'] : [] as $s) {
            $n = is_array($s) ? self::slide($s) : null;
            if ($n !== null) {
                $slides[] = $n;
            }
            if (count($slides) >= $r['slides'][1]) {
                break;
            }
        }
        $autoplay = self::bool($raw['autoplay'] ?? null, $d['autoplay']);

        return [
            'mode' => self::pick($raw['mode'] ?? null, $c['mode'], $d['mode']),
            'label' => self::text($raw['label'] ?? '', 120),
            'autoplay' => $autoplay,
            'interval' => self::clamp($raw['interval'] ?? null, $r['interval'], $d['interval']),
            'speed' => self::clamp($raw['speed'] ?? null, $r['speed'], $d['speed']),
            'pauseOnHover' => self::bool($raw['pauseOnHover'] ?? null, $d['pauseOnHover']),
            'arrows' => self::bool($raw['arrows'] ?? null, $d['arrows']),
            'dots' => self::bool($raw['dots'] ?? null, $d['dots']),
            'swipe' => self::bool($raw['swipe'] ?? null, $d['swipe']),
            'loop' => self::bool($raw['loop'] ?? null, $d['loop']),
            'aspect' => self::pick($raw['aspect'] ?? null, $c['aspect'], $d['aspect']),
            'heightPx' => self::clamp($raw['heightPx'] ?? null, $r['heightPx'], $d['heightPx']),
            'fit' => self::pick($raw['fit'] ?? null, $c['fit'], $d['fit']),
            'align' => self::pick($raw['align'] ?? null, $c['align'], $d['align']),
            'accent' => self::hex($raw['accent'] ?? ''),
            'prime' => [
                'source' => self::pick($prime['source'] ?? null, $c['source'], $d['prime']['source']),
                'count' => self::clamp($prime['count'] ?? null, $r['primeCount'], $d['prime']['count']),
                'categories' => self::idList($prime['categories'] ?? []),
                'products' => self::idList($prime['products'] ?? []),
                'buttonLabel' => self::text($prime['buttonLabel'] ?? $d['prime']['buttonLabel'], 40) ?: $d['prime']['buttonLabel'],
            ],
            'slides' => $slides,
        ];
    }

    /**
     * Eine Slide (Bild oder Text+Bild), ohne beides fliegt sie raus
     *
     * @param array<string,mixed> $s
     * @return array<string,string>|null
     */
    public static function slide(array $s): ?array
    {
        $n = [
            'image' => self::url($s['image'] ?? ''),
            'imageSet' => self::srcset($s['imageSet'] ?? ''),
            'alt' => self::text($s['alt'] ?? '', 200),
            'title' => self::text($s['title'] ?? '', 120),
            'text' => self::text($s['text'] ?? '', 300),
            'buttonLabel' => self::text($s['buttonLabel'] ?? '', 40),
            'buttonUrl' => self::url($s['buttonUrl'] ?? ''),
            'overlayColor' => self::hex($s['overlayColor'] ?? ''),
            'textColor' => self::hex($s['textColor'] ?? ''),
        ];
        if ($n['image'] === '' && $n['title'] === '' && $n['text'] === '') {
            return null;
        }
        if ($n['buttonUrl'] === '') {
            $n['buttonLabel'] = '';
        }

        return $n;
    }

    // nur http(s) oder relativ, javascript:/data: raus
    public static function url(mixed $v): string
    {
        $v = trim((string) $v);
        if ($v === '' || preg_match('~[\x00-\x20"\'<>`]~', $v)) {
            return '';
        }
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $v)) {
            return preg_match('~^https?://~i', $v) ? $v : '';
        }

        return $v;
    }

    // srcset "url 480w, url 960w"
    public static function srcset(mixed $v): string
    {
        $out = [];
        foreach (explode(',', (string) $v) as $part) {
            if (!preg_match('~^\s*(\S+)\s+(\d{2,5})w\s*$~', $part, $m)) {
                continue;
            }
            $u = self::url($m[1]);
            if ($u !== '') {
                $out[] = $u . ' ' . $m[2] . 'w';
            }
        }

        return implode(', ', $out);
    }

    public static function hex(mixed $v): string
    {
        $v = trim((string) $v);

        return preg_match('/^#[0-9a-fA-F]{6}$/D', $v) === 1 ? strtolower($v) : '';
    }

    public static function text(mixed $v, int $max): string
    {
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', strip_tags((string) $v)) ?? '');

        return mb_substr($v, 0, $max);
    }

    /** @param list<string> $allowed */
    private static function pick(mixed $v, array $allowed, string $fallback): string
    {
        return is_string($v) && in_array($v, $allowed, true) ? $v : $fallback;
    }

    private static function bool(mixed $v, bool $fallback): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if ($v === '1' || $v === 1 || $v === 'true') {
            return true;
        }
        if ($v === '0' || $v === 0 || $v === 'false') {
            return false;
        }

        return $fallback;
    }

    /** @param array{0:int,1:int} $range */
    private static function clamp(mixed $v, array $range, int $fallback): int
    {
        if (!is_numeric($v)) {
            return $fallback;
        }

        return max($range[0], min($range[1], (int) round((float) $v)));
    }

    /** IDs als eindeutige Strings (Zahl oder UUID). @return list<string> */
    private static function idList(mixed $v): array
    {
        if (is_string($v)) {
            $v = preg_split('/[\s,;]+/', $v) ?: [];
        }
        $out = [];
        foreach (is_array($v) ? $v : [] as $id) {
            $id = trim((string) $id);
            if ($id !== '' && preg_match('/^[\w.:-]{1,64}$/D', $id)) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }
}
