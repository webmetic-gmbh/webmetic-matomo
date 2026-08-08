<?php

namespace Piwik\Plugins\Webmetic\Columns;

use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Webmetic\Tracker\LookupClient;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class CompanySize extends VisitDimension
{
    protected $columnName   = 'webmetic_company_size';
    protected $columnType   = 'VARCHAR(20) DEFAULT NULL';
    protected $segmentName  = 'webmeticCompanySize';
    protected $nameSingular = 'Webmetic_ColumnCompanySize';
    protected $category     = 'Webmetic_SegmentCategory';
    protected $acceptValues = '1-10, 11-50, 51-200, 201-500, etc.';
    protected $type = self::TYPE_TEXT;

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!empty($visitor->getVisitorColumn($this->columnName))) {
            return false;
        }
        $company = LookupClient::getCompanyForVisit($request, $visitor);
        if (empty($company['employee_range'])) {
            return false;
        }
        return substr((string) $company['employee_range'], 0, 20);
    }

    public function getRequiredVisitFields()
    {
        return ['location_ip'];
    }
}
