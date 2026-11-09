<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Update;

use Plugin\mgd_slider\Update\Admin\UpdatesPage;
use Plugin\mgd_slider\Update\Crypto\KeyRing;
use Plugin\mgd_slider\Update\Http\CurlHttpClient;
use Plugin\mgd_slider\Update\License\{LicenseProvider, NullLicenseProvider};
use Plugin\mgd_slider\Update\Store\JsonFileStateStore;

// Baut Client und Admin-Seite aus den Plugin-Angaben. rootKeysFile = gepinnte root-keys.json im Plugin
final class Factory
{
    public static function page(
        string $rootKeysFile,
        string $dataDir,
        string $product,
        string $edition,
        string $pluginName,
        string $installedVersion,
        string $domain,
        string $shopVersion,
        callable $csrfValid,
        callable $canManage,
        string $csrfField,
        ?LicenseProvider $license = null,
        array $requiredFiles = ['info.xml'],
    ): UpdatesPage {
        $raw = is_file($rootKeysFile) ? file_get_contents($rootKeysFile) : false;
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $roots = KeyRing::fromRootFile(is_array($data) ? $data : []);
        $cfg = new ClientConfig($product, $edition, $installedVersion, $domain, $roots, $dataDir, $shopVersion, $requiredFiles);
        $store = new JsonFileStateStore(rtrim($dataDir, '/') . '/state.json');
        $client = new UpdateClient($cfg, new CurlHttpClient($cfg->allowedHosts(), $cfg->userAgent()), $license ?? new NullLicenseProvider(), $store);

        return new UpdatesPage($client, $store, $csrfValid, $canManage, $csrfField, $pluginName, $installedVersion);
    }
}
