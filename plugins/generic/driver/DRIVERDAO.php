<?php

/**
 * @file plugins/generic/driver/DRIVERDAO.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DRIVERDAO
 *
 * @brief DAO operations for DRIVER.
 */

namespace APP\plugins\generic\driver;

use APP\oai\ojs\JournalOAI;
use APP\oai\ojs\OAIDAO;
use PKP\oai\OAIIdentifier;
use PKP\oai\OAIRecord;

class DRIVERDAO extends OAIDAO
{
    /**
     * Set parent OAI object.
     *
     * @param JournalOAI $oai
     */
    public function setOAI($oai): void
    {
        $this->oai = $oai;
    }

    //
    // Records
    //

    /**
     * Return set of OAI records or identifiers matching specified parameters.
     *
     * @param array $setIds Objects ids that specify an OAI set, in this case only journal ID.
     * @param string $funcName Row conversion method: 'returnRecordFromRow' or 'returnIdentifierFromRow'.
     *
     * @return array<OAIRecord|OAIIdentifier>
     */
    public function &getDRIVERRecordsOrIdentifiers(
        array $setIds,
        ?int $from,
        ?int $until,
        int $offset,
        int $limit,
        int &$total,
        string $funcName = 'returnRecordFromRow'
    ): array {
        $records = [];

        $query = $this->getRecordsRecordSetQuery($setIds, $from, $until, null);
        $total = $query->getCountForPagination();
        $results = $query->offset($offset)->limit($limit)->get();

        foreach ($results as $row) {
            $record = $this->$funcName((array) $row);
            if (in_array('driver', $record->sets)) {
                $records[] = $record;
            }
        }
        return $records;
    }
}
