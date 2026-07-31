<?php

namespace Piwik\Plugins\Webmetic\Reports;

use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Webmetic\Columns\CompanyName;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

class GetCompanies extends Report
{
    protected function init()
    {
        parent::init();
        $this->categoryId    = 'General_Visitors';
        $this->subcategoryId = 'Webmetic_Companies';
        $this->dimension     = new CompanyName();
        $this->name          = Piwik::translate('Webmetic_Companies');
        $this->documentation = Piwik::translate('Webmetic_CompaniesReportDocumentation');
        $this->order         = 55;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig(
            $factory->createWidget()->setName('Webmetic_WidgetCompanies')
        );
    }

    public function configureView(ViewDataTable $view)
    {
        $view->requestConfig->filter_limit = 25;
        $view->config->addTranslation('label', $this->dimension->getName());
        $view->config->show_search = true;
    }
}
