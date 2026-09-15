<?php

namespace Piwik\Plugins\Webmetic\ProfileSummary;

use Piwik\Piwik;
use Piwik\Plugins\Live\ProfileSummary\ProfileSummaryAbstract;
use Piwik\View;

class CompanySummary extends ProfileSummaryAbstract
{
    public function getName()
    {
        return Piwik::translate('Webmetic_ProfileSummaryName');
    }

    public function render()
    {
        if (empty($this->profile['webmetic']['companyName'])) {
            return '';
        }

        $view = new View('@Webmetic/_profileSummary.twig');
        $view->webmetic = $this->profile['webmetic'];

        return $view->render();
    }

    public function getOrder()
    {
        return 5;
    }
}
