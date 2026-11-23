<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Kit\Platform\Jtl;

use Plugin\mgd_slider\Kit\Support\Text;
use Plugin\mgd_slider\Kit\Update\UpdateInfo;

// Hinweis "Bitte aktualisieren" aus dem notice-Feld im Plugin-Backend, nur für Admins, alles escaped
final class NoticeView
{
    public static function render(NoticeReader $reader, Guard $guard, string $installed, string $today, string $pluginName): string
    {
        try {
            if (!$guard->canManage()) {
                return '';
            }

            return self::html($reader->info(), $installed, $today, $pluginName);
        } catch (\Throwable) {
            return '';
        }
    }

    /** Regeln aus Plugin\mgd_slider\Kit\Update\Notice: installiert < below und heute >= from */
    public static function html(?UpdateInfo $u, string $installed, string $today, string $pluginName): string
    {
        $n = $u?->notice;
        if ($n === null || !$n->shouldShow($installed, $today)) {
            return '';
        }

        return '<div class="alert alert-warning mgd-kit-notice" role="status"><p><strong>' . Text::e($pluginName) . ':</strong> '
            . Text::e($n->text) . ' <a href="' . Text::e($n->url) . '" target="_blank" rel="noopener noreferrer">Zur aktuellen Version</a></p></div>';
    }
}
