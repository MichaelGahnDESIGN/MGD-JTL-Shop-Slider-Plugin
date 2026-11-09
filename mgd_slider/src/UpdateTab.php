<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use InvalidArgumentException;
use Plugin\mgd_slider\Update\Factory;

/**
 * Admin Tab "Updates" - dünner Adapter um den MGD Update Client (src/Update, kommt vom Generator)
 * Alles JTL-spezifische (CSRF, Rechte, Pfade) kommt als Parameter rein, dadurch ohne JTL testbar
 */
final class UpdateTab
{
    // Produktname für die Update-API
    public const PRODUCT = 'mgd-slider';
    public const PLUGIN_NAME = 'MGD Slider';
    // Free, Pro-Limit-Lizenz gibt es für JTL noch nicht > NullLicenseProvider (Standard in Factory)
    public const EDITION = 'free';

    /** @var callable():bool */
    private $csrfValid;
    /** @var callable():bool */
    private $canManage;
    /** @var callable(string):void */
    private $sendFile;

    /**
     * @param callable():bool $csrfValid z. B. Form::validateToken
     * @param callable():bool $canManage Rechte im Admin
     * @param callable(string):void|null $sendFile Download ausliefern (Test: Fake)
     */
    public function __construct(
        private readonly string $dataDir,
        private readonly string $installedVersion,
        private readonly string $domain,
        private readonly string $shopVersion,
        callable $csrfValid,
        callable $canManage,
        private readonly string $csrfField,
        ?callable $sendFile = null,
    ) {
        $this->csrfValid = $csrfValid;
        $this->canManage = $canManage;
        $this->sendFile = $sendFile ?? [self::class, 'streamZip'];
    }

    // Autoload für src/Update (Plugin hat keinen eigenen)
    public static function registerAutoload(): void
    {
        $prefix = __NAMESPACE__ . '\\Update\\';
        $base = __DIR__ . '/Update/';
        spl_autoload_register(static function (string $class) use ($prefix, $base): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    public function render(string $method, array $post): string
    {
        try {
            $this->prepareDataDir();
            $page = Factory::page(
                __DIR__ . '/../update/root-keys.json',
                $this->dataDir,
                self::PRODUCT,
                self::EDITION,
                self::PLUGIN_NAME,
                $this->installedVersion,
                $this->domain,
                $this->shopVersion,
                $this->csrfValid,
                $this->canManage,
                $this->csrfField,
            );
        } catch (InvalidArgumentException | \RuntimeException $e) {
            // Version oder Datenordner kaputt
            return '<div class="alert alert-danger" role="alert">Der Update-Bereich ist gerade nicht verfügbar: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
        }
        $res = $page->handle($method, $post);
        if ($res['deliver'] !== null) {
            ($this->sendFile)($res['deliver']);
        }

        return $res['html'];
    }

    // Datenordner ausserhalb Web + Deny Datei
    private function prepareDataDir(): void
    {
        if (!is_dir($this->dataDir) && !@mkdir($this->dataDir, 0700, true) && !is_dir($this->dataDir)) {
            throw new \RuntimeException('Datenordner nicht anlegbar.');
        }
        $guards = [
            '.htaccess' => "# Zugriff von aussen sperren\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n",
            'index.html' => '',
        ];
        foreach ($guards as $name => $content) {
            $f = $this->dataDir . '/' . $name;
            if (!is_file($f)) {
                @file_put_contents($f, $content);
            }
        }
    }

    // ZIP zum Download, danach Ende
    public static function streamZip(string $file): void
    {
        if (!is_file($file) || headers_sent()) {
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file)) ?: 'update.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($file));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }
}
