<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

/**
 * Kandidaten für MGD\Slider\Prime::select - STUB, ungetestet.
 *
 * TODO / zu prüfen: Tabellen und Klassen in JTL-Shop 5 sind hier NICHT belegt.
 * Mögliche Quellen (alles zu prüfen, nichts davon ist hier verwendet):
 *  - Artikeldaten: tartikel (Name, Artikelnummer, Lagerbestand), Bilder über JTL-Artikelklasse
 *  - Kategorien: Zuordnung Artikel > Kategorie
 *  - Bestseller: Verkaufszahlen (Tabelle zu prüfen)
 *  - Preis: fertiger Text über die Shop-Preisfunktion (Währung, Netto/Brutto)
 *
 * Vertrag je Kandidat (Array): id, sku, name, url, image, alt, price (fertiger Text), sales (int),
 * categories (list<string>), inStock (bool). Optional imageSet.
 */
final class PrimeCandidates
{
    /** @return list<array<string,mixed>> */
    public function load(): array
    {
        // TODO: echte Abfrage - bis dahin leer, dann rendert der Prime-Slider nichts
        return [];
    }
}
