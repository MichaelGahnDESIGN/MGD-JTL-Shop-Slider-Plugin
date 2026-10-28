<?php

declare(strict_types=1);

namespace Plugin\mgd_slider;

use JTL\DB\DbInterface;
use MGD\Slider\Config;
use MGD\Slider\Limits;
use RuntimeException;

/**
 * Zugriff auf xplugin_mgd_slider. DbInterface-Methoden (getObjects, getSingleObject, insert, update, delete)
 * nach JTL5 - ungetestet, zu prüfen.
 */
final class SliderRepository
{
    public const TABLE = 'xplugin_mgd_slider';

    public function __construct(private readonly DbInterface $db)
    {
    }

    /** @return list<array{id:int,name:string,created_at:string,updated_at:string}> */
    public function all(): array
    {
        $rows = $this->db->getObjects('SELECT id, name, created_at, updated_at FROM ' . self::TABLE . ' ORDER BY id');
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int) $r->id, 'name' => (string) $r->name, 'created_at' => (string) $r->created_at, 'updated_at' => (string) $r->updated_at];
        }

        return $out;
    }

    public function count(): int
    {
        $row = $this->db->getSingleObject('SELECT COUNT(*) AS c FROM ' . self::TABLE);

        return $row === null ? 0 : (int) $row->c;
    }

    /** @return array{id:int,name:string,config:array<string,mixed>}|null */
    public function find(int $id): ?array
    {
        $row = $this->db->getSingleObject('SELECT id, name, config FROM ' . self::TABLE . ' WHERE id = :id', ['id' => $id]);
        if ($row === null) {
            return null;
        }
        $cfg = json_decode((string) $row->config, true);

        return ['id' => (int) $row->id, 'name' => (string) $row->name, 'config' => Config::normalize(is_array($cfg) ? $cfg : [])];
    }

    /**
     * Anlegen mit Limit-Prüfung auf dem Server (nicht nur im Formular).
     *
     * @param array<string,mixed> $rawConfig
     * @throws RuntimeException wenn das Limit erreicht ist
     */
    public function create(string $name, array $rawConfig = []): int
    {
        if (!Limits::canCreate($this->count())) {
            throw new RuntimeException(Limits::reachedMessage());
        }
        $now = date('Y-m-d H:i:s');
        $cfg = Config::normalize($rawConfig);
        $id = $this->db->insert(self::TABLE, (object) [
            'name' => Config::text($name, 120) ?: 'Slider',
            'config' => json_encode($cfg, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $id;
    }

    /** @param array<string,mixed> $rawConfig */
    public function update(int $id, string $name, array $rawConfig): void
    {
        $this->db->update(self::TABLE, 'id', $id, (object) [
            'name' => Config::text($name, 120) ?: 'Slider',
            'config' => json_encode(Config::normalize($rawConfig), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->delete(self::TABLE, 'id', $id);
    }
}
