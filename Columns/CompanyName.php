<?php

namespace Piwik\Plugins\Webmetic\Columns;

use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Webmetic\Tracker\LookupClient;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class CompanyName extends VisitDimension
{
    protected $columnName   = 'webmetic_company_name';
    protected $columnType   = 'VARCHAR(200) DEFAULT NULL';
    protected $segmentName  = 'webmeticCompany';
    protected $nameSingular = 'Webmetic_ColumnCompanyName';
    protected $namePlural   = 'Webmetic_ColumnCompanyNamePlural';
    protected $category     = 'Webmetic_SegmentCategory';
    protected $acceptValues = 'Siemens AG, SAP SE, etc.';
    protected $type = self::TYPE_TEXT;

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!empty($visitor->getVisitorColumn($this->columnName))) {
            return false;
        }
        $company = LookupClient::getCompanyForVisit($request, $visitor);
        if (empty($company['company_name'])) {
            return false;
        }
        return $company['company_name'];
    }

    public function getRequiredVisitFields()
    {
        return ['location_ip'];
    }
}
