<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\Events\Dispatcher;
use JTL\Helpers\Form;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;
use RuntimeException;

/**
 * MGD Slider für JTL-Shop 5 - Pre-Alpha 0.1.0, UNGETESTET in einem echten Shop.
 *
 * Zu prüfen (nichts davon ist hier belegt):
 *  - info.xml: Blöcke CSS/JS (name, priority, position) und Portlets nach JTL5-Doku, Reihenfolge Model vor Slider
 *  - Customlink ohne Filename > Bootstrap::renderAdminMenuTab (wie im Notification-Center-Plugin, dort ohne Inhalt)
 *  - Migrations-Ordner und Namensmuster, Tabellenpräfix xplugin_
 *  - Portlet-Klasse (siehe Portlets/MGDSlider/MGDSlider.php)
 *  - Form::getTokenInput / Form::validateToken im Admin
 *  - PrimeCandidates ist nur ein Stub
 */
final class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        // Kern lokal eingebettet > src/Core/*.php (kommt vom Generator)
        $core = __DIR__ . '/src/Core/';
        foreach (['Config', 'Limits', 'Prime', 'Renderer'] as $file) {
            require_once $core . $file . '.php';
        }
        require_once __DIR__ . '/src/SliderRepository.php';
        require_once __DIR__ . '/src/PrimeCandidates.php';
    }

    public function uninstalled(bool $deleteData = true): void
    {
        parent::uninstalled($deleteData);
        if ($deleteData) {
            $this->getDB()->query('DROP TABLE IF EXISTS ' . SliderRepository::TABLE);
        }
    }

    /** Admin-Tabs "Slider" und "Lizenzen" */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        $notice = '<p style="padding:8px 12px;background:#fff3cd;border:1px solid #e0c36a;">'
            . 'Pre-Alpha: nur Platzhalter, noch nicht für Live-Shops.</p>';

        return match ($tabName) {
            'Slider' => $notice . $this->sliderTab(),
            'Lizenzen' => $notice . $this->licenseTab(),
            default => '',
        };
    }

    private function sliderTab(): string
    {
        $repo = new SliderRepository($this->getDB());
        $msg = '';
        if (($_POST['mgd_slider_create'] ?? '') !== '') {
            if (!Form::validateToken()) {
                $msg = 'Ungültiges Formular-Token.';
            } else {
                try {
                    $repo->create((string) ($_POST['mgd_slider_name'] ?? ''));
                } catch (RuntimeException $e) {
                    // Limit-Meldung aus dem Kern
                    $msg = $e->getMessage();
                }
            }
        }
        $rows = '';
        foreach ($repo->all() as $s) {
            $rows .= '<tr><td>' . $s['id'] . '</td><td>' . $this->e($s['name']) . '</td><td>' . $this->e($s['updated_at']) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="3">Noch kein Slider angelegt.</td></tr>';
        }

        return ($msg !== '' ? '<p style="color:#b00020;">' . $this->e($msg) . '</p>' : '')
            . '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Geändert</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<form method="post">' . Form::getTokenInput()
            . '<input type="text" name="mgd_slider_name" placeholder="Name" maxlength="120"> '
            . '<button type="submit" name="mgd_slider_create" value="1" class="btn btn-primary">Slider anlegen</button></form>'
            . '<p>Der Editor für Slides folgt. Einbinden: OPC-Portlet "MGD Slider".</p>';
    }

    private function licenseTab(): string
    {
        return '<p>Lizenzcode (noch nicht aktiv):</p>'
            . '<input type="text" disabled placeholder="Lizenzcode" style="width:320px;"> '
            . '<p>Infos und Lizenzen: <a href="https://themes.michael-gahn.de" target="_blank" rel="noopener">themes.michael-gahn.de</a></p>';
    }

    private function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
