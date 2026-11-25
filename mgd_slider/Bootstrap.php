<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\Events\Dispatcher;
use JTL\Helpers\Form;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;
use Plugin\mgd_slider\Kit\Platform\Jtl\Setup;

/**
 * MGD Slider für JTL-Shop 5 - Release 1.6.0 (getestet nur mit JTL-Shop 5.8.2)
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
        // Baukasten (Updates, Weitere Plugins, Installation melden) laedt JTL von selbst > Kit/
        // Aktiv-Meldung max 1x am Tag, nur im Backend nie im Frontend
        if (Setup::isBackend()) {
            Setup::forShop($this->getPlugin())?->ping();
        }
    }

    // Anmeldung bei installieren / aktivieren / update (immer mit ?-> damit nix scheitert)
    public function installed()
    {
        parent::installed();
        Setup::forShop($this->getPlugin())?->ping(true);
    }

    public function enabled()
    {
        parent::enabled();
        Setup::forShop($this->getPlugin())?->ping(true);
    }

    public function updated($oldVersion, $newVersion)
    {
        parent::updated($oldVersion, $newVersion);
        // beim Update kennt das Plugin-Objekt noch die alte Version > neue Version mitgeben, sonst meldet der Shop die alte
        Setup::forShop(new VersionedPlugin($this->getPlugin(), (string) $newVersion))?->ping(true);
    }

    public function uninstalled(bool $deleteData = true): void
    {
        // Abmelden bei theme.michael-gahn.de
        Setup::forShop($this->getPlugin())?->uninstall($deleteData);
        parent::uninstalled($deleteData);
        if ($deleteData) {
            $this->getDB()->query('DROP TABLE IF EXISTS ' . SliderRepository::TABLE);
        }
    }

    /** Admin-Tabs "Slider", "Lizenzen" + Baukasten "Übersicht" und "Weitere Plugins" */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        return match ($tabName) {
            'Slider' => $this->sliderTab(),
            'Lizenzen' => $this->licenseTab(),
            default => Setup::renderTab(Setup::forShop($this->getPlugin()), $tabName) ?? '',
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
