<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\Webmetic\Dao\LookupCache;

class Webmetic extends \Piwik\Plugin
{
    public function isTrackerPlugin()
    {
        return true;
    }

    public function registerEvents()
    {
        return [
            'Tracker.setTrackerCacheGeneral'    => 'setTrackerCacheGeneral',
            'PrivacyManager.exportDataSubjects' => 'exportDataSubjects',
            'PrivacyManager.deleteDataSubjects' => 'deleteDataSubjects',
            'PrivacyManager.deleteLogsOlderThan' => 'deleteLogsOlderThan',
        ];
    }

    public function install()
    {
        LookupCache::install();
    }

    public function uninstall()
    {
        LookupCache::uninstall();
    }

    public function activate()
    {
        \Piwik\Tracker\Cache::clearCacheGeneral();
    }

    public function deactivate()
    {
        \Piwik\Tracker\Cache::clearCacheGeneral();
    }

    /**
     * Expose plugin configuration to the tracker file cache so the tracker
     * hot path never queries the settings tables directly.
     */
    public function setTrackerCacheGeneral(&$cacheContent)
    {
        $settings = new SystemSettings();
        $cacheContent['Webmetic.enabled'] = (bool) $settings->enabled->getValue();
        $cacheContent['Webmetic.apiKey']  = (string) $settings->apiKey->getValue();
    }

    /**
     * GDPR right of access: export cache rows belonging to the given visits.
     * Cache rows are keyed by sha256(ip), so we resolve each visit's stored IP first.
     */
    public function exportDataSubjects(&$export, $visitsToExport)
    {
        $export['Webmetic'] = [];
        foreach ($visitsToExport as $visit) {
            $ipHash = $this->getIpHashForVisit($visit['idvisit']);
            if ($ipHash === null) {
                continue;
            }
            $row = Db::fetchRow(
                'SELECT ip_hash, company_data, created_at, expires_at FROM '
                    . Common::prefixTable(LookupCache::TABLE) . ' WHERE ip_hash = ?',
                [$ipHash]
            );
            if (!empty($row)) {
                $export['Webmetic'][] = $row;
            }
        }
    }

    /**
     * GDPR right to erasure: delete cache rows belonging to the given visits.
     * The webmetic_* columns in log_visit are removed by Matomo core.
     */
    public function deleteDataSubjects(&$result, $visitsToDelete)
    {
        $result['Webmetic'] = 0;
        foreach ($visitsToDelete as $visit) {
            $ipHash = $this->getIpHashForVisit($visit['idvisit']);
            if ($ipHash === null) {
                continue;
            }
            $result['Webmetic'] += Db::query(
                'DELETE FROM ' . Common::prefixTable(LookupCache::TABLE) . ' WHERE ip_hash = ?',
                [$ipHash]
            )->rowCount();
        }
    }

    /**
     * Retention purge ("delete logs older than N days").
     */
    public function deleteLogsOlderThan($dateUpperLimit, $deleteLogsOlderThan)
    {
        Db::query(
            'DELETE FROM ' . Common::prefixTable(LookupCache::TABLE) . ' WHERE created_at < ?',
            [$dateUpperLimit->getDatetime()]
        );
    }

    private function getIpHashForVisit($idVisit)
    {
        $binaryIp = Db::fetchOne(
            'SELECT location_ip FROM ' . Common::prefixTable('log_visit') . ' WHERE idvisit = ?',
            [$idVisit]
        );
        if (empty($binaryIp)) {
            return null;
        }
        $ip = \Matomo\Network\IPUtils::binaryToStringIP($binaryIp);
        if (empty($ip)) {
            return null;
        }
        return hash('sha256', $ip);
    }
}
