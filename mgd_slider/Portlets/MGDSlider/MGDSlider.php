<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Portlets\MGDSlider;

use JTL\OPC\InputType;
use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use JTL\Shop;
use MGD\Slider\Config;
use MGD\Slider\Prime;
use MGD\Slider\Renderer;
use Plugin\mgd_slider\PrimeCandidates;
use Plugin\mgd_slider\SliderRepository;

/**
 * OPC-Portlet "MGDSlider" - Slider auswählen, Ausgabe kommt komplett aus MGD\Slider\Renderer
 */
class MGDSlider extends Portlet
{
    /** @return array<string,mixed> */
    public function getPropertyDesc(): array
    {
        $options = ['' => '- bitte wählen -'];
        foreach ($this->repository()->all() as $s) {
            $options[(string) $s['id']] = $s['name'] . ' (#' . $s['id'] . ')';
        }

        return [
            'sliderId' => [
                'type' => InputType::SELECT,
                'label' => 'Slider',
                'options' => $options,
                'default' => '',
            ],
        ];
    }

    public function getFinalHtml(PortletInstance $instance, bool $inContainer = true): string
    {
        $id = (int) $instance->getProperty('sliderId');
        $slider = $id > 0 ? $this->repository()->find($id) : null;
        if ($slider === null) {
            return '';
        }
        $cfg = $slider['config'];
        $slides = $cfg['slides'];
        if ($cfg['mode'] === 'prime') {
            $slides = Prime::select((new PrimeCandidates(Shop::Container()->getDB()))->load($cfg), $cfg);
        }
        // uid mit Instanz, damit derselbe Slider mehrfach auf einer Seite geht
        $uid = $id . '-' . substr(md5(spl_object_hash($instance)), 0, 6);

        return Renderer::render($cfg, $slides, $uid);
    }

    private function repository(): SliderRepository
    {
        return new SliderRepository(Shop::Container()->getDB());
    }
}
