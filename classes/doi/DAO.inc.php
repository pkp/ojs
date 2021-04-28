<?php

/**
 * @file classes/doi/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiDAO
 * @ingroup doi
 *
 * @see Doi
 *
 * @brief Operations for retrieving and modifying Doi objects.
 */

namespace APP\doi;

use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use PKP\context\Context;
use PKP\doi\Doi;

class DAO extends \PKP\doi\DAO
{
    public function getAllDepositableIssueIds(Context $context): Enumerable
    {
        $q = DB::table($this->table, 'd')
            ->leftJoin('issues as i', 'i.doi_id', '=', 'd.doi_id')
            ->where('i.journal_id', '=', $context->getId())
            ->whereNotNull('i.doi_id')
            ->where('i.published', '=', 1)
            ->whereIn('d.status', [Doi::STATUS_UNREGISTERED, Doi::STATUS_ERROR, Doi::STATUS_STALE]);

        return $q->get(['i.issue_id', 'i.doi_id']);
    }
}
