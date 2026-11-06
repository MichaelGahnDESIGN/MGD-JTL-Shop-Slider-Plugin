<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\Catalog\Product\Artikel;
use JTL\DB\DbInterface;
use JTL\Shop;
use Throwable;

/**
 * Prime Abfrage aus Artikeln - liefert Kandidaten für MGD\Slider\Prime::select
 *
 * Vertrag je Kandidat (Array): id, sku, name, url, image, imageSet, alt, price (fertiger Text), sales (int),
 * categories (list<string>), inStock (bool).
 * Auswahl der IDs per SQL (nur ein kleiner Pool, nie alle Artikel), Daten/Bilder/Preise kommen aus der
 * JTL-Artikelklasse, damit Sichtbarkeit, Kundengruppe, Währung und Bild-URLs stimmen.
 */
final class PrimeCandidates
{
    // Pool pro Slide, damit nach dem Aussortieren (ohne Bild, nicht lieferbar) genug übrig bleiben
    private const POOL_FACTOR = 4;
    private const POOL_MIN = 12;

    public function __construct(private readonly DbInterface $db)
    {
    }

    /**
     * @param array<string,mixed> $cfg normalisierter Slider (Config::normalize)
     * @return list<array<string,mixed>>
     */
    public function load(array $cfg): array
    {
        $prime = $cfg['prime'];
        $limit = max(self::POOL_MIN, (int) $prime['count'] * self::POOL_FACTOR);
        $source = (string) $prime['source'];

        $sales = [];
        $categories = [];
        if ($source === 'manual') {
            $ids = $this->idsManual($prime['products']);
        } elseif ($source === 'category') {
            $ids = $this->idsCategory($prime['categories'], $limit);
            $categories = array_map('strval', $prime['categories']);
        } elseif ($source === 'bestseller') {
            $sales = $this->salesMap($limit);
            // ohne Verkaufsdaten (neuer Shop) wie "zufällig"
            $ids = $sales !== [] ? array_keys($sales) : $this->idsRandom($limit);
        } else {
            $ids = $this->idsRandom($limit);
        }

        $out = [];
        foreach ($ids as $id) {
            $c = $this->candidate((int) $id, $sales[$id] ?? 0, $categories);
            if ($c !== null) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /** @return list<int> */
    private function idsRandom(int $limit): array
    {
        // Variationskinder raus, die hängen am Vaterartikl
        $rows = $this->db->getObjects('SELECT kArtikel FROM tartikel WHERE kVaterArtikel = 0 ORDER BY RAND() LIMIT ' . $limit);

        return array_map(static fn ($r): int => (int) $r->kArtikel, $rows);
    }

    /**
     * Verkäufe je Artikel: erst JTL-Bestsellertabelle, sonst aus echten Bestellpositionen
     *
     * @return array<int,int> kArtikel > Anzahl, absteigend
     */
    private function salesMap(int $limit): array
    {
        $rows = $this->db->getObjects(
            'SELECT b.kArtikel, b.fAnzahl AS n FROM tbestseller b JOIN tartikel a ON a.kArtikel = b.kArtikel
             WHERE a.kVaterArtikel = 0 ORDER BY b.fAnzahl DESC, b.kArtikel LIMIT ' . $limit
        );
        if ($rows === []) {
            $rows = $this->db->getObjects(
                'SELECT p.kArtikel, SUM(p.nAnzahl) AS n FROM twarenkorbpos p
                 JOIN tbestellung o ON o.kWarenkorb = p.kWarenkorb
                 JOIN tartikel a ON a.kArtikel = p.kArtikel
                 WHERE p.nPosTyp = 1 AND p.kArtikel > 0 AND a.kVaterArtikel = 0
                 GROUP BY p.kArtikel ORDER BY n DESC, p.kArtikel LIMIT ' . $limit
            );
        }
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->kArtikel] = (int) round((float) $r->n);
        }

        return $out;
    }

    /**
     * @param list<string> $categoryIds
     * @return list<int>
     */
    private function idsCategory(array $categoryIds, int $limit): array
    {
        $cats = array_values(array_filter(array_map('intval', $categoryIds), static fn (int $i): bool => $i > 0));
        if ($cats === []) {
            return [];
        }
        // Unterkategorien über lft/rght mitnehmen
        $in = implode(',', $cats);
        $rows = $this->db->getObjects(
            'SELECT DISTINCT ka.kArtikel FROM tkategorieartikel ka
             JOIN tkategorie c ON c.kKategorie = ka.kKategorie
             JOIN tkategorie p ON c.lft BETWEEN p.lft AND p.rght
             JOIN tartikel a ON a.kArtikel = ka.kArtikel
             WHERE p.kKategorie IN (' . $in . ') AND a.kVaterArtikel = 0
             ORDER BY RAND() LIMIT ' . $limit
        );

        return array_map(static fn ($r): int => (int) $r->kArtikel, $rows);
    }

    /**
     * Manuell: Artikel-ID oder Artikelnummer
     *
     * @param list<string> $products
     * @return list<int>
     */
    private function idsManual(array $products): array
    {
        if ($products === []) {
            return [];
        }
        $params = [];
        $marks = [];
        foreach (array_values($products) as $i => $p) {
            $params['p' . $i] = $p;
            $marks[] = ':p' . $i;
        }
        $in = implode(',', $marks);
        $rows = $this->db->getObjects(
            'SELECT kArtikel FROM tartikel WHERE CAST(kArtikel AS CHAR) IN (' . $in . ') OR cArtNr IN (' . $in . ')',
            $params
        );

        return array_map(static fn ($r): int => (int) $r->kArtikel, $rows);
    }

    /**
     * @param list<string> $categories
     * @return array<string,mixed>|null
     */
    private function candidate(int $id, int $sales, array $categories): ?array
    {
        try {
            $a = (new Artikel($this->db))->fuelleArtikel($id, Artikel::getDefaultOptions());
        } catch (Throwable $e) {
            Shop::Container()->getLogService()->warning('MGD Slider: Artikel ' . $id . ' nicht ladbar: ' . $e->getMessage());

            return null;
        }
        if ($a === null || empty($a->Bilder[0])) {
            return null;
        }
        $b = $a->Bilder[0];
        $name = html_entity_decode((string) $a->cName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return [
            'id' => (string) $a->kArtikel,
            'sku' => (string) $a->cArtNr,
            'name' => $name,
            'url' => (string) $a->cURLFull,
            'image' => (string) ($b->cURLGross ?: $b->cURLNormal),
            'imageSet' => $this->imageSet($b),
            'alt' => html_entity_decode((string) ($b->cAltAttribut ?: $name), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            // JTL liefert "&euro;" als HTML, hier muss Klartext hin (Renderer maskiert selbst)
            'price' => html_entity_decode(strip_tags((string) ($a->Preise->cVKLocalized[0] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'sales' => $sales,
            'categories' => $categories,
            'inStock' => ($a->inWarenkorbLegbar ?? 0) >= 1,
        ];
    }

    // srcset mit den zwei JTL-Bildgrößen, Breiten aus den Shop-Einstellungen (Bilder)
    private function imageSet(object $b): string
    {
        $md = (int) Shop::getSettingValue(\CONF_BILDER, 'bilder_artikel_normal_breite');
        $lg = (int) Shop::getSettingValue(\CONF_BILDER, 'bilder_artikel_gross_breite');
        $parts = [];
        if ($md > 0 && !empty($b->cURLNormal)) {
            $parts[] = $b->cURLNormal . ' ' . $md . 'w';
        }
        if ($lg > 0 && !empty($b->cURLGross) && $lg !== $md) {
            $parts[] = $b->cURLGross . ' ' . $lg . 'w';
        }

        return implode(', ', $parts);
    }
}
