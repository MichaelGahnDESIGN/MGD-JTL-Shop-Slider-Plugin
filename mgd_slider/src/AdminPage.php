<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\DB\DbInterface;
use MGD\Slider\Config;
use MGD\Slider\Limits;
use RuntimeException;

/**
 * Admin Tab "Slider" - Liste, anlegen, bearbeiten, löschen
 * CSRF-Prüfung macht die Bootstrap (Parameter $tokenOk), hier nur Logik + HTML
 */
final class AdminPage
{
    private const SLIDE_ROWS_EXTRA = 2;

    public function __construct(private readonly DbInterface $db, private readonly SliderRepository $repo)
    {
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $get
     */
    public function render(array $post, array $get, string $tokenInput, bool $tokenOk): string
    {
        $msg = '';
        $ok = '';
        $editId = isset($get['mgd_edit']) ? (int) $get['mgd_edit'] : 0;
        $isNew = isset($get['mgd_new']);
        $action = (string) ($post['mgd_action'] ?? '');

        if ($action !== '') {
            if (!$tokenOk) {
                $msg = 'Das Formular ist abgelaufen. Bitte Seite neu laden und noch einmal speichern.';
            } else {
                try {
                    [$ok, $editId] = $this->handle($action, $post);
                    $isNew = false;
                } catch (RuntimeException $e) {
                    // Limit-Meldung aus dem Kern
                    $msg = $e->getMessage();
                    $editId = (int) ($post['mgd_id'] ?? 0);
                    $isNew = $editId === 0;
                }
            }
        }

        $out = ($msg !== '' ? '<div class="alert alert-danger" role="alert">' . $this->e($msg) . '</div>' : '')
            . ($ok !== '' ? '<div class="alert alert-success" role="status">' . $this->e($ok) . '</div>' : '');

        if ($isNew || $editId > 0) {
            $slider = $editId > 0 ? $this->repo->find($editId) : null;
            if ($editId > 0 && $slider === null) {
                return $out . '<div class="alert alert-warning">Diesen Slider gibt es nicht (mehr).</div>' . $this->listHtml($tokenInput);
            }
            // bei Fehler die eingegebenen Werte behalten
            if ($msg !== '' && $action === 'save') {
                $slider = ['id' => $editId, 'name' => (string) ($post['mgd_name'] ?? ''), 'config' => Config::normalize($this->configFromPost($post))];
            }

            return $out . $this->formHtml($slider, $tokenInput);
        }

        return $out . $this->listHtml($tokenInput);
    }

    /**
     * @param array<string,mixed> $post
     * @return array{0:string,1:int} Meldung, ID die danach offen bleibt
     */
    private function handle(string $action, array $post): array
    {
        $id = (int) ($post['mgd_id'] ?? 0);
        if ($action === 'delete' && $id > 0) {
            $this->repo->delete($id);

            return ['Slider gelöscht.', 0];
        }
        if ($action === 'save') {
            $name = (string) ($post['mgd_name'] ?? '');
            $cfg = $this->configFromPost($post);
            if ($id > 0) {
                $this->repo->update($id, $name, $cfg);

                return ['Slider gespeichert.', $id];
            }
            // Limit wird in create() auf dem Server geprüft
            $new = $this->repo->create($name, $cfg);

            return ['Slider angelegt. Du findest ihn im OPC-Editor unter dem Portlet "MGD Slider".', $new];
        }

        return ['', $id];
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function configFromPost(array $post): array
    {
        $flag = static fn (string $k): string => isset($post['mgd_' . $k]) ? '1' : '0';
        $slides = [];
        $n = is_array($post['mgd_slide_image'] ?? null) ? count($post['mgd_slide_image']) : 0;
        for ($i = 0; $i < $n; $i++) {
            $slides[] = [
                'image' => $post['mgd_slide_image'][$i] ?? '',
                'alt' => $post['mgd_slide_alt'][$i] ?? '',
                'title' => $post['mgd_slide_title'][$i] ?? '',
                'text' => $post['mgd_slide_text'][$i] ?? '',
                'buttonLabel' => $post['mgd_slide_btn'][$i] ?? '',
                'buttonUrl' => $post['mgd_slide_url'][$i] ?? '',
            ];
        }

        return [
            'mode' => $post['mgd_mode'] ?? '',
            'label' => $post['mgd_label'] ?? '',
            'autoplay' => $flag('autoplay'),
            'interval' => $post['mgd_interval'] ?? '',
            'speed' => $post['mgd_speed'] ?? '',
            'pauseOnHover' => $flag('pauseOnHover'),
            'arrows' => $flag('arrows'),
            'dots' => $flag('dots'),
            'swipe' => $flag('swipe'),
            'loop' => $flag('loop'),
            'aspect' => $post['mgd_aspect'] ?? '',
            'heightPx' => $post['mgd_heightPx'] ?? '',
            'fit' => $post['mgd_fit'] ?? '',
            'align' => $post['mgd_align'] ?? '',
            'accent' => $post['mgd_accent'] ?? '',
            'prime' => [
                'source' => $post['mgd_prime_source'] ?? '',
                'count' => $post['mgd_prime_count'] ?? '',
                'categories' => $post['mgd_prime_categories'] ?? [],
                'products' => $post['mgd_prime_products'] ?? '',
                'buttonLabel' => $post['mgd_prime_btn'] ?? '',
            ],
            'slides' => $slides,
        ];
    }

    private function listHtml(string $tokenInput): string
    {
        $all = $this->repo->all();
        $max = Limits::maxSliders();
        $rows = '';
        foreach ($all as $s) {
            $rows .= '<tr><td>' . $s['id'] . '</td><td>' . $this->e($s['name']) . '</td><td>' . $this->e($s['updated_at']) . '</td><td>'
                . '<a class="btn btn-sm btn-outline-primary" href="?mgd_edit=' . $s['id'] . '">Bearbeiten</a> '
                . '<form method="post" style="display:inline" onsubmit="return confirm(\'Slider wirklich löschen?\');">' . $tokenInput
                . '<input type="hidden" name="mgd_id" value="' . $s['id'] . '">'
                . '<button type="submit" name="mgd_action" value="delete" class="btn btn-sm btn-outline-danger">Löschen</button></form></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4">Noch kein Slider angelegt.</td></tr>';
        }

        return '<p>Free-Version: ' . count($all) . ' von ' . $max . ' Slidern angelegt. Einbinden: im OPC-Editor das Portlet "MGD Slider" auf die Seite ziehen und den Slider auswählen.</p>'
            . '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Geändert</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<a class="btn btn-primary" href="?mgd_new=1">Neuen Slider anlegen</a>';
    }

    /** @param array{id:int,name:string,config:array<string,mixed>}|null $slider */
    private function formHtml(?array $slider, string $tokenInput): string
    {
        $c = $slider['config'] ?? Config::normalize([]);
        $id = $slider['id'] ?? 0;
        $p = $c['prime'];
        $h = '<form method="post" class="mgd-admin">' . $tokenInput . '<input type="hidden" name="mgd_id" value="' . $id . '">'
            . '<h3>' . ($id > 0 ? 'Slider #' . $id . ' bearbeiten' : 'Neuer Slider') . '</h3>'
            . $this->field('Name (nur für dich)', '<input class="form-control" type="text" name="mgd_name" maxlength="120" required value="' . $this->e($slider['name'] ?? '') . '">')
            . $this->field('Art', $this->select('mgd_mode', ['classic' => 'Klassisch (Slides selbst anlegen)', 'prime' => 'Prime (Slides automatisch aus Artikeln)'], $c['mode']))
            . $this->field('Beschriftung für Screenreader', '<input class="form-control" type="text" name="mgd_label" maxlength="120" placeholder="z. B. Aktionen" value="' . $this->e($c['label']) . '">');

        $h .= '<fieldset><legend>Verhalten</legend>'
            . $this->check('autoplay', 'Automatisch wechseln (dann gibt es einen Pausenknopf)', $c['autoplay'])
            . $this->check('pauseOnHover', 'Pause bei Maus darüber', $c['pauseOnHover'])
            . $this->check('arrows', 'Pfeile anzeigen', $c['arrows'])
            . $this->check('dots', 'Punkte anzeigen', $c['dots'])
            . $this->check('swipe', 'Wischen auf Touch-Geräten', $c['swipe'])
            . $this->check('loop', 'Endlos (nach der letzten Slide wieder die erste)', $c['loop'])
            . $this->field('Wechselzeit in Millisekunden (2000 bis 20000)', '<input class="form-control" type="number" name="mgd_interval" min="2000" max="20000" step="100" value="' . (int) $c['interval'] . '">')
            . $this->field('Dauer der Bewegung in Millisekunden (0 bis 2000)', '<input class="form-control" type="number" name="mgd_speed" min="0" max="2000" step="50" value="' . (int) $c['speed'] . '">')
            . '</fieldset>';

        $h .= '<fieldset><legend>Aussehen</legend>'
            . $this->field('Seitenverhältnis', $this->select('mgd_aspect', array_combine(['21/9', '16/9', '3/2', '4/3', '1/1', '3/1'], ['21:9', '16:9', '3:2', '4:3', '1:1', '3:1']), $c['aspect']))
            . $this->field('Feste Höhe in Pixel (0 = Seitenverhältnis nutzen)', '<input class="form-control" type="number" name="mgd_heightPx" min="0" max="1200" value="' . (int) $c['heightPx'] . '">')
            . $this->field('Bild einpassen', $this->select('mgd_fit', ['cover' => 'Füllen (Rand wird beschnitten)', 'contain' => 'Ganz zeigen (evtl. mit Rand)'], $c['fit']))
            . $this->field('Textausrichtung', $this->select('mgd_align', ['left' => 'Links', 'center' => 'Mitte', 'right' => 'Rechts'], $c['align']))
            . $this->field('Akzentfarbe (leer = Farbe vom Theme, Format #aabbcc)', '<input class="form-control" type="text" name="mgd_accent" maxlength="7" pattern="#[0-9a-fA-F]{6}" value="' . $this->e($c['accent']) . '">')
            . '</fieldset>';

        // Klassisch
        $h .= '<fieldset><legend>Klassisch: Slides</legend><p>Leere Zeilen werden beim Speichern ignoriert. Bild: Adresse (URL) oder Pfad, z. B. /mediafiles/Bilder/banner.jpg. Der Alt-Text beschreibt das Bild für Screenreader.</p>'
            . '<div id="mgd-slide-rows">';
        $slides = $c['slides'];
        for ($i = 0; $i < self::SLIDE_ROWS_EXTRA; $i++) {
            $slides[] = ['image' => '', 'alt' => '', 'title' => '', 'text' => '', 'buttonLabel' => '', 'buttonUrl' => ''];
        }
        foreach ($slides as $i => $s) {
            $h .= $this->slideRow($s, $i + 1);
        }
        $h .= '</div><button type="button" class="btn btn-outline-secondary" id="mgd-add-slide">Weitere Slide-Zeile</button></fieldset>';

        // Prime
        $h .= '<fieldset><legend>Prime: Slides aus Artikeln</legend>'
            . $this->field('Quelle', $this->select('mgd_prime_source', ['random' => 'Zufällig', 'bestseller' => 'Meistverkauft', 'category' => 'Aus Kategorien', 'manual' => 'Manuell gewählte Artikel (daraus zufällig)'], $p['source']))
            . $this->field('Anzahl Slides (1 bis 12)', '<input class="form-control" type="number" name="mgd_prime_count" min="1" max="12" value="' . (int) $p['count'] . '">')
            . $this->field('Kategorien (nur bei "Aus Kategorien", mehrere mit Strg/Cmd)', $this->categorySelect($p['categories']))
            . $this->field('Artikel (nur bei "Manuell": Artikel-ID oder Artikelnummer, getrennt durch Komma)', '<input class="form-control" type="text" name="mgd_prime_products" value="' . $this->e(implode(', ', $p['products'])) . '">')
            . $this->field('Text auf dem Button', '<input class="form-control" type="text" name="mgd_prime_btn" maxlength="40" value="' . $this->e($p['buttonLabel']) . '">')
            . '</fieldset>';

        return $h . '<p><button type="submit" name="mgd_action" value="save" class="btn btn-primary">Speichern</button> '
            . '<a class="btn btn-link" href="?">Zurück zur Liste</a></p></form>' . $this->script();
    }

    /** @param array<string,string> $s */
    private function slideRow(array $s, int $n): string
    {
        $in = fn (string $name, string $label, string $val, string $ph = ''): string => '<div class="form-group col-md-6"><label>' . $this->e($label)
            . '<input class="form-control" type="text" name="' . $name . '[]" value="' . $this->e($val) . '"' . ($ph !== '' ? ' placeholder="' . $this->e($ph) . '"' : '') . '></label></div>';

        return '<div class="mgd-slide-row card card-body mb-2"><strong>Slide ' . $n . '</strong><div class="form-row">'
            . $in('mgd_slide_image', 'Bild (URL oder Pfad)', $s['image'])
            . $in('mgd_slide_alt', 'Alt-Text', $s['alt'])
            . $in('mgd_slide_title', 'Titel', $s['title'])
            . $in('mgd_slide_text', 'Text', $s['text'])
            . $in('mgd_slide_btn', 'Button-Text', $s['buttonLabel'])
            . $in('mgd_slide_url', 'Button-Link', $s['buttonUrl'])
            . '</div></div>';
    }

    /** @param list<string> $selected */
    private function categorySelect(array $selected): string
    {
        $o = '';
        foreach ($this->db->getObjects('SELECT kKategorie, cName, nLevel FROM tkategorie ORDER BY lft') as $k) {
            $sel = in_array((string) $k->kKategorie, $selected, true) ? ' selected' : '';
            $o .= '<option value="' . (int) $k->kKategorie . '"' . $sel . '>' . str_repeat('– ', max(0, (int) $k->nLevel - 1)) . $this->e((string) $k->cName) . '</option>';
        }

        return '<select class="form-control" name="mgd_prime_categories[]" multiple size="8">' . $o . '</select>';
    }

    private function script(): string
    {
        // Zeile klnoen, ohne Bibliothek
        return '<script>document.getElementById("mgd-add-slide").addEventListener("click",function(){'
            . 'var box=document.getElementById("mgd-slide-rows"),rows=box.querySelectorAll(".mgd-slide-row"),n=rows[rows.length-1].cloneNode(true);'
            . 'n.querySelectorAll("input").forEach(function(i){i.value="";});n.querySelector("strong").textContent="Slide "+(rows.length+1);box.appendChild(n);});</script>';
    }

    private function field(string $label, string $control): string
    {
        return '<div class="form-group"><label>' . $this->e($label) . $control . '</label></div>';
    }

    private function check(string $name, string $label, bool $on): string
    {
        return '<div class="form-check"><label><input type="checkbox" name="mgd_' . $name . '" value="1"' . ($on ? ' checked' : '') . '> ' . $this->e($label) . '</label></div>';
    }

    /** @param array<string,string> $options */
    private function select(string $name, array $options, string $current): string
    {
        $o = '';
        foreach ($options as $v => $l) {
            $o .= '<option value="' . $this->e((string) $v) . '"' . ((string) $v === $current ? ' selected' : '') . '>' . $this->e($l) . '</option>';
        }

        return '<select class="form-control" name="' . $name . '">' . $o . '</select>';
    }

    private function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
