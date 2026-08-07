<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Plugins\Live\VisitorDetailsAbstract;
use Piwik\View;

class VisitorDetails extends VisitorDetailsAbstract
{
    public function extendVisitorDetails(&$visitor)
    {
        $visitor['webmeticCompanyId']   = $this->details['webmetic_company_id'] ?? null;
        $visitor['webmeticCompanyName'] = $this->details['webmetic_company_name'] ?? null;
        $visitor['webmeticIndustry']    = $this->details['webmetic_industry'] ?? null;
        $visitor['webmeticCompanySize'] = $this->details['webmetic_company_size'] ?? null;
        $visitor['webmeticRevenue']     = $this->details['webmetic_revenue'] ?? null;
    }

    public function renderVisitorDetails($visitorDetails)
    {
        if (empty($visitorDetails['webmeticCompanyName'])) {
            return [];
        }
        $view = new View('@Webmetic/_visitorDetails.twig');
        $view->visitInfo = $visitorDetails;
        return [[21, $view->render()]];
    }
}
