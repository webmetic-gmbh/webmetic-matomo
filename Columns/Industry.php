<?php

namespace Piwik\Plugins\Webmetic\Columns;

use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Webmetic\Tracker\LookupClient;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class Industry extends VisitDimension
{
    protected $columnName   = 'webmetic_industry';
    protected $columnType   = 'VARCHAR(100) DEFAULT NULL';
    protected $segmentName  = 'webmeticIndustry';
    protected $nameSingular = 'Webmetic_ColumnIndustry';
    protected $category     = 'General_Visitors';
    protected $acceptValues = 'IT, Software & Telekommunikation, Industrie & Fertigung, etc.';
    protected $type = self::TYPE_TEXT;

    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!empty($visitor->getVisitorColumn($this->columnName))) {
            return false;
        }
        $company = LookupClient::getCompanyForVisit($request, $visitor);
        if (empty($company['industry'])) {
            return false;
        }
        return substr((string) $company['industry'], 0, 100);
    }

    public function getRequiredVisitFields()
    {
        return ['location_ip'];
    }
}
