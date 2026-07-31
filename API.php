<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Archive;
use Piwik\Piwik;
use Piwik\Plugins\Webmetic\RecordBuilders\Companies;

/**
 * @method static \Piwik\Plugins\Webmetic\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Companies identified by Webmetic for the given site and period.
     */
    public function getCompanies($idSite, $period, $date, $segment = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $archive   = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable(Companies::COMPANIES_RECORD_NAME);

        $dataTable->filter('AddSegmentValue');
        $dataTable->queueFilter('ReplaceColumnNames');
        $dataTable->queueFilter('ReplaceSummaryRowLabel');

        return $dataTable;
    }
}
