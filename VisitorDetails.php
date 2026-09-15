<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Plugins\Live\VisitorDetailsAbstract;
use Piwik\View;

class VisitorDetails extends VisitorDetailsAbstract
{
    /**
     * Company of the most recent visit that had one, collected while the profile is built.
     *
     * @var array|null
     */
    private $profileCompany = null;

    /**
     * @var int
     */
    private $profileCompanyTimestamp = 0;

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

    public function initProfile($visits, &$profile)
    {
        $this->profileCompany          = null;
        $this->profileCompanyTimestamp = 0;
    }

    public function handleProfileVisit($visit, &$profile)
    {
        $companyName = $visit->getColumn('webmeticCompanyName');
        if (empty($companyName)) {
            return;
        }

        $timestamp = (int) ($visit->getColumn('lastActionTimestamp') ?: $visit->getColumn('serverTimestamp'));
        if ($this->profileCompany !== null && $timestamp <= $this->profileCompanyTimestamp) {
            return;
        }

        $this->profileCompany = [
            'companyId'   => $visit->getColumn('webmeticCompanyId'),
            'companyName' => $companyName,
            'industry'    => $visit->getColumn('webmeticIndustry'),
            'companySize' => $visit->getColumn('webmeticCompanySize'),
            'revenue'     => $visit->getColumn('webmeticRevenue'),
        ];
        $this->profileCompanyTimestamp = $timestamp;
    }

    public function finalizeProfile($visits, &$profile)
    {
        if ($this->profileCompany !== null) {
            $profile['webmetic'] = $this->profileCompany;
        }
    }
}
