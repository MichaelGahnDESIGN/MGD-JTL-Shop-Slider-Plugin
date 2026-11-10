<?php

// Rauchtest Updates-Tab, laeuft ohne JTL und ohne Netz: php tests/update-smoke.php
declare(strict_types=1);

require __DIR__ . '/../mgd_slider/src/UpdateTab.php';

use Plugin\mgd_slider\UpdateTab;

UpdateTab::registerAutoload();

$fails = 0;
$check = static function (string $name, bool $ok) use (&$fails): void {
    echo ($ok ? 'OK    ' : 'FEHLER') . ' ' . $name . "\n";
    $fails += $ok ? 0 : 1;
};
$tmp = sys_get_temp_dir() . '/mgd_slider_upd_' . bin2hex(random_bytes(4));
$delivered = [];
$make = static function (string $dir, bool $csrf, bool $rights, string $version = '0.1.0') use (&$delivered): UpdateTab {
    return new UpdateTab(
        $dir,
        $version,
        'shop.example.test',
        '5.8.2',
        static fn (): bool => $csrf,
        static fn (): bool => $rights,
        '<input type="hidden" name="jtl_token" value="TEST">',
        static function (string $f) use (&$delivered): void {
            $delivered[] = $f;
        }
    );
};
$stateFile = $tmp . '/state.json';

// Ohne Recht
$html = $make($tmp, true, false)->render('POST', ['mgd_update_action' => 'check_now']);
$check('ohne Recht: Meldung Berechtigung', str_contains($html, 'Berechtigung'));
$check('ohne Recht: keine Aktion, kein Zustand', !is_file($stateFile));
$check('ohne Recht: kein Formular', !str_contains($html, '<form'));

// Ohne CSRF
$html = $make($tmp, false, true)->render('POST', ['mgd_update_action' => 'save_settings', 'checks_enabled' => '1']);
$check('ohne CSRF: Token-Meldung', str_contains($html, 'Sicherheits-Token'));
$check('ohne CSRF: nichts gespeichert', !is_file($stateFile));
$html = $make($tmp, false, true)->render('POST', ['mgd_update_action' => 'deliver', 'confirm' => '1']);
$check('ohne CSRF: kein Download', $delivered === []);

// GET ohne Einschalten: kein Abruf
$html = $make($tmp, true, true)->render('GET', []);
$check('GET: Seite kommt', str_contains($html, 'Update-Prüfung einschalten'));
$check('GET: kein Abruf (kein last_check, kein Ergebnis)', !is_file($stateFile) || !str_contains((string) file_get_contents($stateFile), 'last_check'));

// Datenordner abgesichert
$check('Datenordner: .htaccess Deny', is_file($tmp . '/.htaccess') && str_contains((string) file_get_contents($tmp . '/.htaccess'), 'denied'));
$check('Datenordner: index.html', is_file($tmp . '/index.html'));

// Einschalten mit Token, dann leere Root-Keys = Kanal nicht eingerichtet (B1)
$make($tmp, true, true)->render('POST', ['mgd_update_action' => 'save_settings', 'checks_enabled' => '1']);
$check('Einstellung mit CSRF gespeichert', str_contains((string) file_get_contents($stateFile), '"enabled":true'));
// direkt über die Factory ohne Prüfsummen-Adresse, sonst ginge der Test ins Netz
$bare = \Plugin\mgd_slider\Update\Factory::page(__DIR__ . '/../mgd_slider/update/root-keys.json', $tmp, UpdateTab::PRODUCT, UpdateTab::EDITION, UpdateTab::PLUGIN_NAME, '0.1.0', 'shop.example.test', '5.8.2', static fn (): bool => true, static fn (): bool => true, '');
$html = $bare->handle('POST', ['mgd_update_action' => 'check_now'])['html'];
$check('B1: ohne Root-Keys kein Update (nicht eingerichtet)', stripos($html, 'nicht eingerichtet') !== false);
// einfacher Modus verdrahtet, nur statisch geprüft
$check('einfacher Modus: Adresse der Prüfsummen-Datei', UpdateTab::SIMPLE_URL === 'https://theme.michael-gahn.de/plugin-updates/mgd-slider.json');
$check('einfacher Modus: UpdateTab gibt die Adresse weiter', str_contains((string) file_get_contents(__DIR__ . '/../mgd_slider/src/UpdateTab.php'), "['info.xml'],\n                self::SIMPLE_URL"));

// Anzeige escaped
file_put_contents($stateFile, json_encode(['enabled' => true, 'last_check' => time(), 'last_result' => ['status' => 'up_to_date', 'message' => '<script>alert(1)</script>']]));
$html = $make($tmp, true, true)->render('GET', []);
$check('escaped: kein rohes <script>', !str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'));

// Kaputte Version: Meldung, kein Absturz, escaped
$html = $make($tmp . '_x', true, true, '<b>x')->render('GET', []);
$check('Version ungueltig: Hinweis statt Absturz', str_contains($html, 'nicht verfügbar') && !str_contains($html, '<b>x'));

// aufräumen
foreach ([$tmp, $tmp . '_x'] as $d) {
    foreach (glob($d . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
        is_file($f) && unlink($f);
    }
    is_dir($d) && rmdir($d);
}
echo $fails === 0 ? "ALLES OK\n" : "$fails FEHLER\n";
exit($fails === 0 ? 0 : 1);
