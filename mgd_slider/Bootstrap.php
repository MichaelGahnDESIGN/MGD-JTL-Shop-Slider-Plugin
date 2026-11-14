<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\Events\Dispatcher;
use JTL\Helpers\Form;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;

/**
 * MGD Slider für JTL-Shop 5 - Release 1.4.0 (getestet nur mit JTL-Shop 5.8.2)
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
        require_once __DIR__ . '/src/AdminPage.php';
        // Update Client Autoload
        require_once __DIR__ . '/src/UpdateTab.php';
        UpdateTab::registerAutoload();
    }

    public function uninstalled(bool $deleteData = true): void
    {
        parent::uninstalled($deleteData);
        if ($deleteData) {
            $this->getDB()->query('DROP TABLE IF EXISTS ' . SliderRepository::TABLE);
        }
    }

    /** Admin-Tabs "Slider", "Lizenzen" und "Updates" */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        return match ($tabName) {
            'Slider' => $this->sliderTab(),
            'Lizenzen' => $this->licenseTab(),
            'Updates' => $this->updatesTab(),
            default => '',
        };
    }

    private function sliderTab(): string
    {
        $db = $this->getDB();
        $page = new AdminPage($db, new SliderRepository($db));
        $isPost = ($_POST['mgd_action'] ?? '') !== '';

        // Token nur bei Speichern/Löschen prüfne
        return $page->render($_POST, $_GET, Form::getTokenInput(), $isPost ? Form::validateToken() : true);
    }

    private function updatesTab(): string
    {
        // Updates Menue
        $tab = new UpdateTab(
            // Datenordner ausserhalb Web (templates_c, mit Deny)
            rtrim((defined('PFAD_ROOT') ? PFAD_ROOT : '') . (defined('PFAD_COMPILEDIR') ? PFAD_COMPILEDIR : 'templates_c/'), '/') . '/mgd_slider_update',
            // Version kommt aus info.xml via Plugin-Objekt
            $this->getPlugin()->getMeta()->getVersion(),
            (string) parse_url(Shop::getURL(), PHP_URL_HOST),
            defined('APPLICATION_VERSION') ? (string) APPLICATION_VERSION : '',
            // CSRF Check
            static fn (): bool => (bool) Form::validateToken(),
            // Rechte
            static fn (): bool => (bool) Shop::Container()->getAdminAccount()->permission('PLUGIN_ADMIN_VIEW'),
            Form::getTokenInput(),
        );

        return $tab->render((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
    }

    private function licenseTab(): string
    {
        return '<p>Pro-Lizenz (unbegrenzt viele Slider): Die Prüfung per Lizenzcode ist in dieser Version noch nicht eingebaut. Die Free-Version erlaubt 5 Slider.</p>'
            . '<input type="text" disabled placeholder="Lizenzcode" style="width:320px;"> '
            . '<p>Infos und Lizenzen: <a href="https://themes.michael-gahn.de" target="_blank" rel="noopener">themes.michael-gahn.de</a></p>';
    }

    private function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
