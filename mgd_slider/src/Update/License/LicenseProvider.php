<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Update\License;

// Lizenz-Adapter. Liefert das Lizenz-Token nur für den Authorization-Header. Nie loggen, nie in URLs
interface LicenseProvider
{
    public function token(): ?string;
}
