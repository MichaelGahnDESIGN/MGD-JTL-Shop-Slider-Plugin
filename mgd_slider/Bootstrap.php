<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\Events\Dispatcher;
use JTL\Helpers\Form;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\mgd_slider\Install\InstallClient;
use Plugin\mgd_slider\Install\Platform\Jtl as JtlInstall;
use Plugin\mgd_slider\Install\Settings as InstallSettings;
use Plugin\mgd_slider\Update\Admin\InstallReporting;

/**
 * MGD Slider für JTL-Shop 5 - Release 1.5.0 (getestet nur mit JTL-Shop 5.8.2)
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
        // Update + Install Client Autoload
        require_once __DIR__ . '/src/UpdateTab.php';
        UpdateTab::registerAutoload();
        // Aktiv-Meldung max 1x am Tag, nur im Admin nie im Frontend
        if (!Shop::isFrontend()) {
            $this->pingInstall(false);
        }
    }

    // Install-Meldung bei installieren / aktivieren / update
    public function installed()
    {
        parent::installed();
        $this->pingInstall(true);
    }

    public function enabled()
    {
        parent::enabled();
        $this->pingInstall(true);
    }

    public function updated($oldVersion, $newVersion)
    {
        parent::updated($oldVersion, $newVersion);
        $this->pingInstall(true);
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
        $ic = $this->installClient();
        // Schalter "Installation melden" auf der Update-Seite
        $install = $ic === null ? null : new InstallReporting(
            static fn () => $ic->ping(),
            static fn (): bool => $ic->isEnabled(),
            static fn (bool $on) => $ic->setEnabled($on),
            static fn (): string => $ic->status(),
            InstallSettings::HINT,
        );
        $tab = new UpdateTab(
            self::dataDir(),
            // Version kommt aus info.xml via Plugin-Objekt
            $this->getPlugin()->getMeta()->getVersion(),
            (string) parse_url(Shop::getURL(), PHP_URL_HOST),
            defined('APPLICATION_VERSION') ? (string) APPLICATION_VERSION : '',
            // CSRF Check
            static fn (): bool => (bool) Form::validateToken(),
            // Rechte
            static fn (): bool => (bool) Shop::Container()->getAdminAccount()->permission('PLUGIN_ADMIN_VIEW'),
            Form::getTokenInput(),
            null,
            $install,
        );

        return $tab->render((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
    }

    // Datenordner ausserhalb Web (templates_c, mit Deny) - Update + Install teilen sich den
    private static function dataDir(): string
    {
        return rtrim((defined('PFAD_ROOT') ? PFAD_ROOT : '') . (defined('PFAD_COMPILEDIR') ? PFAD_COMPILEDIR : 'templates_c/'), '/') . '/mgd_slider_update';
    }

    // null = Produkt/System ungültig > dann gar keine Meldung
    private function installClient(): ?InstallClient
    {
        try {
            // bei installed() lief boot() evtl. noch nicht > Autoload sicherstellen
            require_once __DIR__ . '/src/UpdateTab.php';
            UpdateTab::registerAutoload();

            return JtlInstall::client(self::dataDir(), UpdateTab::PRODUCT, $this->getPlugin()->getMeta()->getVersion());
        } catch (\Throwable) {
            return null;
        }
    }

    // darf nie die Installation oder den Shop kaputt machen
    private function pingInstall(bool $force): void
    {
        try {
            $this->installClient()?->ping($force);
        } catch (\Throwable) {
        }
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
