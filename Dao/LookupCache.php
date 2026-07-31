<?php

namespace Piwik\Plugins\Webmetic\Dao;

use Piwik\Common;
use Piwik\Db;

/**
 * Local IP-hash -> company cache table.
 *
 * A row with company_data = NULL is a negative entry (no company match / lookup
 * error backoff) and suppresses remote lookups until it expires.
 */
class LookupCache
{
    public const TABLE = 'webmetic_lookup_cache';

    public static function install()
    {
        try {
            $sql = 'CREATE TABLE ' . Common::prefixTable(self::TABLE) . ' (
                        ip_hash CHAR(64) NOT NULL,
                        company_data MEDIUMTEXT NULL,
                        created_at DATETIME NOT NULL,
                        expires_at DATETIME NOT NULL,
                        PRIMARY KEY (ip_hash),
                        INDEX idx_webmetic_expires (expires_at)
                    ) DEFAULT CHARSET=utf8mb4';
            Db::exec($sql);
        } catch (\Exception $e) {
            if (!Db::get()->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }

    public static function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::TABLE));
    }

    /**
     * @return array|null|false  array = cached company payload,
     *                           null  = valid negative entry,
     *                           false = cache miss (expired or absent)
     */
    public function get($ipHash)
    {
        $row = Db::fetchRow(
            'SELECT company_data FROM ' . Common::prefixTable(self::TABLE)
                . ' WHERE ip_hash = ? AND expires_at > NOW()',
            [$ipHash]
        );
        if (empty($row)) {
            return false;
        }
        if ($row['company_data'] === null || $row['company_data'] === '') {
            return null;
        }
        $payload = json_decode($row['company_data'], true);
        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array|null $payload null stores a negative entry
     */
    public function store($ipHash, $payload, $ttlSeconds)
    {
        $data = ($payload === null) ? null : json_encode($payload);
        Db::query(
            'INSERT INTO ' . Common::prefixTable(self::TABLE)
                . ' (ip_hash, company_data, created_at, expires_at)'
                . ' VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))'
                . ' ON DUPLICATE KEY UPDATE company_data = VALUES(company_data),'
                . ' created_at = VALUES(created_at), expires_at = VALUES(expires_at)',
            [$ipHash, $data, (int) $ttlSeconds]
        );
    }

    public function pruneExpired()
    {
        Db::query('DELETE FROM ' . Common::prefixTable(self::TABLE) . ' WHERE expires_at < NOW()');
    }
}
