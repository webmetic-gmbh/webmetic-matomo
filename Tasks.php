<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Plugins\Webmetic\Dao\LookupCache;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        $this->daily('pruneLookupCache');
    }

    public function pruneLookupCache()
    {
        (new LookupCache())->pruneExpired();
    }
}
