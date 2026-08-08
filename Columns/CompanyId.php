<?php

namespace Piwik\Plugins\Webmetic\Columns;

use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Webmetic\Tracker\LookupClient;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class CompanyId extends VisitDimension
{
    protected $columnName   = 'webmetic_company_id';
    protected $columnType   = 'VARCHAR(64) DEFAULT NULL';
    protected $segmentName  = 'webmeticCompanyId';
    protected $nameSingular = 'Webmetic_ColumnCompanyId';
    protected $category     = 'Webmetic_SegmentCategory';
    protected $acceptValues = 'siemens-ag, acme-berlin-gmbh, etc.';
    protected $type = self::TYPE_TEXT;

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!empty($visitor->getVisitorColumn($this->columnName))) {
            return false;
        }
        $company = LookupClient::getCompanyForVisit($request, $visitor);
        if (empty($company['company_id'])) {
            return false;
        }
        return $company['company_id'];
    }

    public function getRequiredVisitFields()
    {
        return ['location_ip'];
    }
}
