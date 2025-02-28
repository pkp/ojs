<?php

namespace APP\galley;

use PKP\db\DAOResultFactory;
use PKP\galley\DAO as PKPGalleyDAO;

/**
 * OJS-specific GalleyDAO override, including issueId filtering.
 */
class DAO extends PKPGalleyDAO
{
    /**
     * OJS version of getExportable with $issueId filter
     */
    public function getExportable(int $contextId, $pubIdType = null, $title = null, $author = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null, ?int $issueId = null)
    {
        $q = $this->buildGetExportableQuery(
            $contextId,
            $pubIdType,
            $title,
            $author,
            $pubIdSettingName,
            $pubIdSettingValue
        );

        // adding OJS specific issueId filter
        if (!is_null($issueId)) {
            $q->where('p.issue_id', '=', $issueId);
        }

        $result = $this->deprecatedDao->retrieveRange($q, [], $rangeInfo);
        return new DAOResultFactory($result, $this, 'fromRow', [], $q, [], $rangeInfo);
    }
}
