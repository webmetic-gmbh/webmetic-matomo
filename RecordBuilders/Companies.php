<?php

namespace Piwik\Plugins\Webmetic\RecordBuilders;

use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Record;
use Piwik\ArchiveProcessor\RecordBuilder;
use Piwik\Config as PiwikConfig;
use Piwik\DataTable;
use Piwik\Metrics;

class Companies extends RecordBuilder
{
    public const COMPANIES_RECORD_NAME = 'Webmetic_Companies';
    public const COMPANY_NAME_FIELD    = 'webmetic_company_name';

    public function __construct()
    {
        parent::__construct();
        $this->maxRowsInTable = PiwikConfig::getInstance()->General['datatable_archiving_maximum_rows_standard'];
        $this->columnToSortByBeforeTruncation = Metrics::INDEX_NB_VISITS;
    }

    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            Record::make(Record::TYPE_BLOB, self::COMPANIES_RECORD_NAME),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $record = new DataTable();

        $query = $archiveProcessor->getLogAggregator()
            ->queryVisitsByDimension(['label' => self::COMPANY_NAME_FIELD]);
        while ($row = $query->fetch()) {
            if (!isset($row['label']) || $row['label'] === '' || $row['label'] === null) {
                continue; // only identified companies appear in the report
            }
            $columns = [
                Metrics::INDEX_NB_UNIQ_VISITORS    => $row[Metrics::INDEX_NB_UNIQ_VISITORS],
                Metrics::INDEX_NB_VISITS           => $row[Metrics::INDEX_NB_VISITS],
                Metrics::INDEX_NB_ACTIONS          => $row[Metrics::INDEX_NB_ACTIONS],
                Metrics::INDEX_NB_USERS            => $row[Metrics::INDEX_NB_USERS],
                Metrics::INDEX_MAX_ACTIONS         => $row[Metrics::INDEX_MAX_ACTIONS],
                Metrics::INDEX_SUM_VISIT_LENGTH    => $row[Metrics::INDEX_SUM_VISIT_LENGTH],
                Metrics::INDEX_BOUNCE_COUNT        => $row[Metrics::INDEX_BOUNCE_COUNT],
                Metrics::INDEX_NB_VISITS_CONVERTED => $row[Metrics::INDEX_NB_VISITS_CONVERTED],
            ];
            $record->sumRowWithLabel($row['label'], $columns);
        }

        return [self::COMPANIES_RECORD_NAME => $record];
    }
}
