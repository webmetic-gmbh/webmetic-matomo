<?php

namespace Piwik\Plugins\Webmetic\Columns;

use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Webmetic\Tracker\LookupClient;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class Revenue extends VisitDimension
{
    protected $columnName   = 'webmetic_revenue';
    protected $columnType   = 'VARCHAR(20) DEFAULT NULL';
    protected $segmentName  = 'webmeticRevenue';
    protected $nameSingular = 'Webmetic_ColumnRevenue';
    protected $category     = 'General_Visitors';
    protected $acceptValues = '1M-5M €, 10M-50M €, etc.';
    protected $type = self::TYPE_TEXT;

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!empty($visitor->getVisitorColumn($this->columnName))) {
            return false;
        }
        $company = LookupClient::getCompanyForVisit($request, $visitor);
        if (empty($company['revenue_range'])) {
            return false;
        }
        return substr((string) $company['revenue_range'], 0, 20);
    }

    public function getRequiredVisitFields()
    {
        return ['location_ip'];
    }
}
