<?php

namespace Piwik\Plugins\Webmetic\Tracker;

use Piwik\Common;
use Piwik\Plugins\Webmetic\Dao\LookupCache;
use Piwik\Tracker\Cache;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

/**
 * Resolves the visitor's IP to a company via the Webmetic lookup API.
 *
 * Runs inside the tracker hot path (VisitDimension::onNewVisit), therefore:
 * - config comes from the tracker file cache, never from the settings tables
 * - local cache table first; at most one HTTP call per unknown IP
 * - short timeout, and every failure path returns null (fail-open)
 * - only the sha256 of the IP is ever sent to the API
 */
class LookupClient
{
    private const DEFAULT_LOOKUP_URL = 'https://hub.webmetic.de/matomo/lookup';

    private const POSITIVE_TTL_MIN = 3600;        // 1 hour
    private const POSITIVE_TTL_MAX = 15552000;    // 180 days
    private const NEGATIVE_TTL     = 86400;       // no-match: retry after 1 day
    private const ERROR_TTL        = 300;         // transport/API error: back off 5 minutes
    private const HTTP_TIMEOUT     = 2;           // seconds

    /** @var array<string, array|null> one lookup per IP per tracker request */
    private static $requestMemo = [];

    /**
     * @return array|null company payload (company_id, company_name, ...) or null
     */
    public static function getCompanyForVisit(Request $request, Visitor $visitor)
    {
        try {
            return self::doGetCompany($request, $visitor);
        } catch (\Exception $e) {
            Common::printDebug('Webmetic lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function doGetCompany(Request $request, Visitor $visitor)
    {
        if (defined('PIWIK_TEST_MODE') && !defined('WEBMETIC_ALLOW_IN_TESTS')) {
            return null;
        }

        $config = Cache::getCacheGeneral();
        if (empty($config['Webmetic.enabled']) || empty($config['Webmetic.apiKey'])) {
            return null;
        }

        $ip = self::getVisitIpString($visitor, $request);
        if ($ip === null) {
            return null;
        }

        $ipHash = hash('sha256', $ip);

        if (array_key_exists($ipHash, self::$requestMemo)) {
            return self::$requestMemo[$ipHash];
        }

        $cache  = new LookupCache();
        $cached = $cache->get($ipHash);
        if ($cached !== false) {
            return self::$requestMemo[$ipHash] = $cached;   // array or null (negative)
        }

        $result = self::remoteLookup($config, $ipHash, $cache);
        return self::$requestMemo[$ipHash] = $result;
    }

    /**
     * Same IP-source rule as core UserCountry/GeoIP enrichment: the raw request IP,
     * unless the admin enabled PrivacyManager's "use anonymized IP for visit
     * enrichment" — then the anonymized IP, which the guard below will skip.
     */
    private static function getVisitIpString(Visitor $visitor, Request $request)
    {
        $useAnonymized = false;
        try {
            $privacyConfig = new \Piwik\Plugins\PrivacyManager\Config();
            $useAnonymized = (bool) $privacyConfig->useAnonymizedIpForVisitEnrichment;
        } catch (\Exception $e) {
            // PrivacyManager unavailable -> keep the conservative default
        }
        $binaryIp = $useAnonymized ? $visitor->getVisitorColumn('location_ip') : $request->getIp();
        if (empty($binaryIp)) {
            $binaryIp = $visitor->getVisitorColumn('location_ip');
        }
        if (empty($binaryIp)) {
            return null;
        }
        $ip = \Matomo\Network\IPUtils::binaryToStringIP($binaryIp);
        if (empty($ip) || $ip === '0.0.0.0') {
            return null;
        }
        if (substr($ip, -2) === '.0' || substr($ip, -2) === '::') {
            Common::printDebug('Webmetic: IP is anonymized, skipping company lookup');
            return null;
        }
        return $ip;
    }

    /**
     * Live-check an API key against the lookup endpoint (used by the settings
     * validate closure — admin UI context only, never in the tracker path).
     *
     * @throws \Exception when the key is rejected or the API is unreachable
     */
    public static function validateApiKey($apiKey)
    {
        $lookupUrl = self::getLookupUrl();
        $url = $lookupUrl
            . (strpos($lookupUrl, '?') === false ? '?' : '&')
            . 'ip_sha=' . hash('sha256', '127.0.0.1');

        try {
            $response = \Piwik\Http::sendHttpRequestBy(
                \Piwik\Http::getTransportMethod(),
                $url,
                $timeout = 5,
                $userAgent = 'Matomo-Webmetic-Plugin',
                $destinationPath = null,
                $file = null,
                $followDepth = 0,
                $acceptLanguage = false,
                $acceptInvalidSslCertificate = false,
                $byteRange = false,
                $getExtendedInfo = true,
                $httpMethod = 'GET',
                $httpUsername = null,
                $httpPassword = null,
                $requestBody = null,
                $additionalHeaders = ['Authorization: ' . $apiKey]
            );
        } catch (\Exception $e) {
            throw new \Exception(\Piwik\Piwik::translate('Webmetic_ApiKeyCheckFailed'));
        }

        $status = isset($response['status']) ? (int) $response['status'] : 0;
        if ($status === 401 || $status === 403) {
            throw new \Exception(\Piwik\Piwik::translate('Webmetic_ApiKeyRejected'));
        }
        if ($status !== 200 && $status !== 204) {
            throw new \Exception(\Piwik\Piwik::translate('Webmetic_ApiKeyCheckFailed'));
        }
    }

    /**
     * The endpoint is fixed; config.ini.php [Webmetic] lookup_url overrides it
     * for staging/tests only (no UI setting on purpose).
     */
    private static function getLookupUrl()
    {
        try {
            $section = \Piwik\Config::getInstance()->Webmetic;
            if (!empty($section['lookup_url'])) {
                return (string) $section['lookup_url'];
            }
        } catch (\Exception $e) {
            // fall through to default
        }
        return self::DEFAULT_LOOKUP_URL;
    }

    private static function remoteLookup(array $config, $ipHash, LookupCache $cache)
    {
        $lookupUrl = self::getLookupUrl();
        $url = $lookupUrl
            . (strpos($lookupUrl, '?') === false ? '?' : '&')
            . 'ip_sha=' . urlencode($ipHash);

        try {
            $response = \Piwik\Http::sendHttpRequestBy(
                \Piwik\Http::getTransportMethod(),
                $url,
                self::HTTP_TIMEOUT,
                $userAgent = 'Matomo-Webmetic-Plugin',
                $destinationPath = null,
                $file = null,
                $followDepth = 0,
                $acceptLanguage = false,
                $acceptInvalidSslCertificate = false,
                $byteRange = false,
                $getExtendedInfo = true,
                $httpMethod = 'GET',
                $httpUsername = null,
                $httpPassword = null,
                $requestBody = null,
                $additionalHeaders = ['Authorization: ' . $config['Webmetic.apiKey']]
            );
        } catch (\Exception $e) {
            Common::printDebug('Webmetic: lookup HTTP error: ' . $e->getMessage());
            $cache->store($ipHash, null, self::ERROR_TTL);
            return null;
        }

        $status = isset($response['status']) ? (int) $response['status'] : 0;

        if ($status === 204 || $status === 404) {
            $cache->store($ipHash, null, self::NEGATIVE_TTL);
            return null;
        }

        if ($status !== 200 || empty($response['data'])) {
            Common::printDebug('Webmetic: lookup returned status ' . $status);
            $cache->store($ipHash, null, self::ERROR_TTL);
            return null;
        }

        $payload = json_decode($response['data'], true);
        if (!is_array($payload) || empty($payload['company_id']) || empty($payload['company_name'])) {
            // API answered 200 without a usable company (e.g. JSON null) -> treat as no match
            $cache->store($ipHash, null, self::NEGATIVE_TTL);
            return null;
        }

        $payload['company_id']   = substr((string) $payload['company_id'], 0, 64);
        $payload['company_name'] = substr(trim((string) $payload['company_name']), 0, 200);

        $ttl = isset($payload['cache_ttl'])
            ? min(max((int) $payload['cache_ttl'], self::POSITIVE_TTL_MIN), self::POSITIVE_TTL_MAX)
            : self::NEGATIVE_TTL;

        $cache->store($ipHash, $payload, $ttl);
        return $payload;
    }
}
