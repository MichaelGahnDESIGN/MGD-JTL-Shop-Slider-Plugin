<?php

// Rauchtest im echten JTL-Shop 5 per CLI. Aufruf: php smoke-jtl.php /pfad/zum/shop https://shop-url
// Legt Test-Slider an und räumt sie wieder weg (Namen "SMOKE ...").
declare(strict_types=1);

use JTL\OPC\PortletInstance;
use JTL\Shop;
use MGD\Slider\Config;
use MGD\Slider\Limits;
use MGD\Slider\Prime;
use MGD\Slider\Renderer;
use Plugin\mgd_slider\AdminPage;
use Plugin\mgd_slider\PrimeCandidates;
use Plugin\mgd_slider\SliderRepository;

$shopDir = $argv[1] ?? '';
$host = parse_url($argv[2] ?? '', PHP_URL_HOST) ?: 'localhost';
if (!is_file($shopDir . '/includes/globalinclude.php')) {
    fwrite(STDERR, "Shop-Ordner angeben\n");
    exit(2);
}
chdir($shopDir);
$_SERVER += ['HTTP_HOST' => $host, 'REQUEST_URI' => '/', 'HTTPS' => 'on', 'SERVER_NAME' => $host, 'REQUEST_METHOD' => 'GET'];
require 'includes/globalinclude.php';

$fails = 0;
$check = static function (string $name, bool $ok, string $info = '') use (&$fails): void {
    echo ($ok ? 'OK    ' : 'FEHLER') . ' ' . $name . ($info !== '' ? ' - ' . $info : '') . "\n";
    $fails += $ok ? 0 : 1;
};

$db = Shop::Container()->getDB();
$loader = JTL\Plugin\Helper::getLoaderByPluginID(
    (int) $db->getSingleObject("SELECT kPlugin FROM tplugin WHERE cPluginID = 'mgd_slider'")->kPlugin,
    $db,
    Shop::Container()->getCache()
);
$plugin = $loader->init((int) $db->getSingleObject("SELECT kPlugin FROM tplugin WHERE cPluginID = 'mgd_slider'")->kPlugin);
JTL\Plugin\Helper::bootstrap($plugin->getID(), $loader);
$repo = new SliderRepository($db);
$made = [];

try {
    // Limit Free: 5 gehen, der 6. nicht
    $before = $repo->count();
    $room = max(0, Limits::FREE_SLIDERS - $before);
    for ($i = 0; $i < $room; $i++) {
        $made[] = $repo->create('SMOKE ' . $i);
    }
    $check('Limit: auf 5 aufgefüllt', $repo->count() === Limits::FREE_SLIDERS, 'vorher ' . $before);
    try {
        $repo->create('SMOKE sechs');
        $check('Limit: 6. Slider abgelehnt', false);
    } catch (RuntimeException $e) {
        $check('Limit: 6. Slider abgelehnt', true, $e->getMessage());
    }
    $check('Pro: unbegrenzt-Schalter umgeht das Limit', ($made[] = $repo->create('SMOKE pro', [], true)) > 0);
    foreach ($made as $m) {
        $repo->delete($m);
    }
    $made = [];

    // Klassisch
    $classic = $repo->create('SMOKE klassisch', [
        'mode' => 'classic', 'autoplay' => true, 'label' => 'Aktionen',
        'slides' => [
            ['image' => '/mediafiles/a.jpg', 'alt' => 'A', 'title' => 'Eins', 'text' => 'Text', 'buttonLabel' => 'Los', 'buttonUrl' => '/x'],
            ['image' => '/mediafiles/b.jpg', 'alt' => 'B', 'title' => 'Zwei'],
        ],
    ], true);
    $made[] = $classic;
    $s = $repo->find($classic);
    $html = Renderer::render($s['config'], $s['config']['slides'], (string) $classic);
    $check('Klassisch: 2 Slides gerendert', substr_count($html, 'class="mgd-slide"') === 2);

    // Prime je Quelle
    $cat = $db->getSingleObject('SELECT kKategorie FROM tkategorie WHERE kOberKategorie = 0 ORDER BY kKategorie LIMIT 1');
    $first = $db->getSingleObject('SELECT kArtikel, cArtNr FROM tartikel WHERE kVaterArtikel = 0 ORDER BY kArtikel LIMIT 1');
    foreach ([
        'random' => [], 'bestseller' => [],
        'category' => ['categories' => [(string) $cat->kKategorie]],
        'manual' => ['products' => [(string) $first->cArtNr]],
    ] as $source => $extra) {
        $id = $repo->create('SMOKE prime ' . $source, ['mode' => 'prime', 'prime' => ['source' => $source, 'count' => 4] + $extra], true);
        $made[] = $id;
        $cfg = $repo->find($id)['config'];
        $slides = Prime::select((new PrimeCandidates($db))->load($cfg), $cfg);
        $out = Renderer::render($cfg, $slides, (string) $id);
        $n = substr_count($out, 'class="mgd-slide"');
        $check('Prime ' . $source . ': Slides aus Artikeln', $n >= 1 && $n <= 4, $n . ' Slides');
        if ($source === 'random' && $slides !== []) {
            echo '      Beispiel: ' . $slides[0]['title'] . ' | ' . $slides[0]['text'] . ' | ' . $slides[0]['image'] . ' | ' . $slides[0]['buttonUrl'] . "\n";
            $check('Prime: Preis ohne HTML-Entity', !str_contains($slides[0]['text'], '&'));
            file_put_contents('/tmp/mgd-prime-random.html', $out);
        }
    }

    // Portlet wie im OPC
    $portlet = Shop::Container()->getOPCDB()->getPortlet('MGDSlider');
    $check('Portlet: Klasse aus Plugin geladen', $portlet instanceof Plugin\mgd_slider\Portlets\MGDSlider\MGDSlider, get_class($portlet));
    $inst = new PortletInstance($portlet);
    $inst->setProperty('sliderId', (string) $classic);
    $ph = $portlet->getFinalHtml($inst);
    $check('Portlet: Frontend-HTML mit Slider', str_contains($ph, 'data-mgd-slider') && substr_count($ph, 'class="mgd-slide"') === 2);
    file_put_contents('/tmp/mgd-portlet.html', $ph);
    $check('Portlet: Vorschau im Editor', str_contains($portlet->getPreviewHtml($inst), 'MGD Slider'));
    $check('Portlet: Auswahlliste kennt Slider', isset($portlet->getPropertyDesc()['sliderId']['options'][(string) $classic]));

    // Prime-Slider weg, sonst greift das Limit im Admin-Test
    foreach (array_slice($made, 1) as $m) {
        $repo->delete($m);
    }
    $made = [$classic];

    // Admin Tab
    $page = new AdminPage($db, $repo);
    $list = $page->render([], [], '<input type="hidden" name="jtl_token" value="t">', true);
    $check('Admin: Liste zeigt Slider', str_contains($list, 'SMOKE klassisch') && str_contains($list, 'Neuen Slider anlegen'));
    $form = $page->render([], ['mgd_new' => '1'], '', true);
    $check('Admin: Formular neu', str_contains($form, 'name="mgd_prime_source"') && str_contains($form, 'name="mgd_slide_image[]"'));
    $save = $page->render([
        'mgd_action' => 'save', 'mgd_name' => 'SMOKE admin', 'mgd_mode' => 'classic', 'mgd_dots' => '1',
        'mgd_slide_image' => ['/mediafiles/c.jpg', ''], 'mgd_slide_alt' => ['C', ''], 'mgd_slide_title' => ['Drei', ''],
        'mgd_slide_text' => ['', ''], 'mgd_slide_btn' => ['', ''], 'mgd_slide_url' => ['', ''],
    ], [], '', true);
    $check('Admin: speichern', str_contains($save, 'Slider angelegt'), substr(strip_tags($save), 0, 160));
    $row = $db->getSingleObject("SELECT id, config FROM xplugin_mgd_slider WHERE name = 'SMOKE admin'");
    $made[] = (int) $row->id;
    $saved = json_decode((string) $row->config, true);
    $check('Admin: leere Slide-Zeile ignoriert, Häkchen übernommen', count($saved['slides']) === 1 && $saved['dots'] === true && $saved['arrows'] === false);
    $bad = $page->render(['mgd_action' => 'save', 'mgd_name' => 'x'], [], '', false);
    $check('Admin: ohne gültiges Token wird nichts gespeichert', str_contains($bad, 'abgelaufen') && $db->getSingleObject("SELECT id FROM xplugin_mgd_slider WHERE name = 'x'") === null);
    $edit = $page->render([], ['mgd_edit' => (string) $row->id], '', true);
    $check('Admin: bearbeiten zeigt Werte', str_contains($edit, 'value="SMOKE admin"') && str_contains($edit, '/mediafiles/c.jpg'));
} finally {
    foreach ($made as $m) {
        $repo->delete($m);
    }
    echo 'Aufgeräumt, Slider in der Tabelle: ' . $repo->count() . "\n";
}
echo $fails === 0 ? "ALLES OK\n" : $fails . " FEHLER\n";
exit($fails === 0 ? 0 : 1);
