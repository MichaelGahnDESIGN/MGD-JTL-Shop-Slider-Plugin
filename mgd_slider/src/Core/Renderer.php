<?php

declare(strict_types=1);

namespace MGD\Slider;

/*
 Slider HTML - Markup Vertrag (alle Plattformen gleich, siehe docs/SLIDER-PLUGINS.md)
 section.mgd-slider > .mgd-slider__viewport > .mgd-slider__track > .mgd-slide
 Pfeile/Punkte/Pause baut mgd-slider.js, ohne JS bleibt Slide 1 stehen
*/
final class Renderer
{
    /**
     * @param array<string,mixed> $cfg normalisierter Slider
     * @param list<array<string,string>> $slides fertige Slides (klassisch oder aus Prime)
     * @param string $uid Kennung auf der Seite ("12" oder "12-2")
     */
    public static function render(array $cfg, array $slides, string $uid): string
    {
        if ($slides === []) {
            return '';
        }
        $total = count($slides);
        $label = $cfg['label'] !== '' ? $cfg['label'] : 'Slider';
        $style = self::sliderStyle($cfg);
        $data = [
            'autoplay' => $cfg['autoplay'], 'interval' => $cfg['interval'], 'speed' => $cfg['speed'],
            'pauseOnHover' => $cfg['pauseOnHover'], 'arrows' => $cfg['arrows'], 'dots' => $cfg['dots'],
            'swipe' => $cfg['swipe'], 'loop' => $cfg['loop'],
        ];
        $json = json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
        $id = 'mgd-slider-' . preg_replace('/[^\w-]/', '', $uid);

        $html = '<section id="' . self::e($id) . '" class="mgd-slider mgd-slider--' . self::e($cfg['mode']) . ' mgd-align-' . self::e($cfg['align']) . ' mgd-fit-' . self::e($cfg['fit']) . '"'
            . ' data-mgd-slider=\'' . $json . '\' aria-roledescription="carousel" aria-label="' . self::e($label) . '"' . ($style !== '' ? ' style="' . self::e($style) . '"' : '') . '>'
            . '<div class="mgd-slider__viewport"><div class="mgd-slider__track">';
        foreach ($slides as $i => $s) {
            $html .= self::slide($s, $i, $total);
        }

        return $html . '</div></div></section>';
    }

    /** @param array<string,string> $s */
    private static function slide(array $s, int $i, int $total): string
    {
        $first = $i === 0;
        $style = '';
        if ($s['overlayColor'] !== '') {
            $style .= '--mgd-overlay:' . $s['overlayColor'] . ';';
        }
        if ($s['textColor'] !== '') {
            $style .= '--mgd-slide-text:' . $s['textColor'] . ';';
        }
        $html = '<div class="mgd-slide" role="group" aria-roledescription="slide" aria-label="' . ($i + 1) . ' von ' . $total . '"' . ($style !== '' ? ' style="' . self::e($style) . '"' : '') . '>';
        if ($s['image'] !== '') {
            // erstes Bild sofort laden (LCP), alle anderen erst bei Bedarf
            $html .= '<img class="mgd-slide__img" src="' . self::e($s['image']) . '"'
                . ($s['imageSet'] !== '' ? ' srcset="' . self::e($s['imageSet']) . '" sizes="100vw"' : '')
                . ' alt="' . self::e($s['alt']) . '" decoding="async" ' . ($first ? 'fetchpriority="high"' : 'loading="lazy"') . '>';
        }
        if ($s['title'] !== '' || $s['text'] !== '' || $s['buttonLabel'] !== '') {
            $html .= '<div class="mgd-slide__overlay">';
            if ($s['title'] !== '') {
                $html .= '<p class="mgd-slide__title">' . self::e($s['title']) . '</p>';
            }
            if ($s['text'] !== '') {
                $html .= '<p class="mgd-slide__text">' . self::e($s['text']) . '</p>';
            }
            if ($s['buttonLabel'] !== '' && $s['buttonUrl'] !== '') {
                $html .= '<a class="mgd-slide__btn" href="' . self::e($s['buttonUrl']) . '">' . self::e($s['buttonLabel']) . '</a>';
            }
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    // CSS-Variablen: Seitenverhältnis, Höhe, Akzent
    private static function sliderStyle(array $cfg): string
    {
        $s = '--mgd-aspect:' . $cfg['aspect'] . ';';
        if ($cfg['heightPx'] > 0) {
            $s .= '--mgd-height:' . (int) $cfg['heightPx'] . 'px;';
        }
        if ($cfg['accent'] !== '') {
            $s .= '--mgd-accent:' . $cfg['accent'] . ';';
        }

        return $s;
    }

    private static function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
