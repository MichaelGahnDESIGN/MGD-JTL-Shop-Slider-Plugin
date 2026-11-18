<?php

// Rauchtest Install-Meldung (Free-Lizenz), ohne JTL und ohne Netz: php tests/install-smoke.php
declare(strict_types=1);

require __DIR__ . '/../mgd_slider/src/UpdateTab.php';

use Plugin\mgd_slider\Install\{InstallClient, JsonFileStore, Response, Settings, Transport};
use Plugin\mgd_slider\Install\Platform\Jtl;
use Plugin\mgd_slider\Update\Admin\InstallReporting;
use Plugin\mgd_slider\UpdateTab;

UpdateTab::registerAutoload();

$fails = 0;
$check = static function (string $name, bool $ok) use (&$fails): void {
    echo ($ok ? 'OK    ' : 'FEHLER') . ' ' . $name . "\n";
    $fails += $ok ? 0 : 1;
};
$tmp = sys_get_temp_dir() . '/mgd_slider_inst_' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

// Fake-Server statt Netz
$calls = [];
$http = new class ($calls) implements Transport {
    public function __construct(private array &$calls)
    {
    }

    public function send(string $method, string $url, array $headers): Response
    {
        $this->calls[] = [$method, $url, $headers];

        return new Response(200, ['x-mgd-install-key' => str_repeat('k', 40)], '{}');
    }
};
$now = 1_800_000_000;
$client = new InstallClient($http, new JsonFileStore($tmp . '/install.json'), UpdateTab::PRODUCT, 'jtl5', '1.5.0', 'shop.example.test', static function () use (&$now): int {
    return $now;
});

$check('ab Werk an', $client->isEnabled());
$check('erste Meldung geht raus', $client->ping(true) && count($calls) === 1);
$check('Adresse ist die Update-Adresse vom Slider', str_contains((string) ($calls[0][1] ?? ''), '/api/v1/updates/mgd-slider.json'));
$sent = strtolower(implode("\n", array_map(static fn ($k, $v) => $k . ': ' . $v, array_keys($calls[0][2] ?? []), $calls[0][2] ?? [])));
$check('nur Produkt, Version, System, Domain, ID gesendet', str_contains($sent, 'jtl5') && str_contains($sent, 'shop.example.test') && !str_contains($sent, '@'));
$check('am selben Tag keine zweite Meldung', $client->ping() === false && count($calls) === 1);
$now += 86400 + 1;
$check('nach einem Tag wieder', $client->ping() && count($calls) === 2);

// Schalter auf der Update-Seite
$tab = new UpdateTab(
    $tmp . '/upd',
    '1.5.0',
    'shop.example.test',
    '5.8.2',
    static fn (): bool => true,
    static fn (): bool => true,
    '<input type="hidden" name="jtl_token" value="TEST">',
    static function (string $f): void {
    },
    new InstallReporting(
        static fn () => $client->ping(),
        static fn (): bool => $client->isEnabled(),
        static fn (bool $on) => $client->setEnabled($on),
        static fn (): string => $client->status(),
        Settings::HINT,
    ),
);
$html = $tab->render('GET', []);
$check('Schalter auf der Update-Seite', str_contains($html, 'name="install_report"') && str_contains($html, ' checked'));
$check('Hinweistext steht dabei', str_contains($html, 'höchstens einmal am Tag'));
$tab->render('POST', ['mgd_update_action' => 'save_install']);
$check('ausschalten geht', $client->isEnabled() === false);
$n = count($calls);
$now += 86400 * 2;
$check('aus = nichts mehr senden', $client->ping(true) === false && count(array_filter(array_slice($calls, $n), static fn ($c) => $c[0] !== 'DELETE')) === 0);
$tab->render('POST', ['mgd_update_action' => 'save_install', 'install_report' => '1']);
$check('wieder anschalten geht', $client->isEnabled());

// falscher Produktname > null statt Absturz
$check('falsches Produkt gibt null', Jtl::client($tmp, 'Kaputt Name!', '1.5.0', 'shop.example.test') === null);

// aufräumen
foreach (array_merge(glob($tmp . '/upd/{,.}[!.]*', GLOB_BRACE) ?: [], glob($tmp . '/*.json') ?: []) as $f) {
    is_file($f) && unlink($f);
}
echo $fails === 0 ? "ALLES OK\n" : "$fails FEHLER\n";
exit($fails === 0 ? 0 : 1);
