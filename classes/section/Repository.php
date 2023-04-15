<?php
/**
 * @file classes/section/Repository.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage sections.
 */

namespace APP\section;

use Illuminate\Support\LazyCollection;

class Repository extends \PKP\section\Repository
{
    /** @copydoc DAO::getByIssueId() */
    public function getByIssueId(int $issueId): LazyCollection
    {
        return $this->dao->getByIssueId($issueId);
    }

    /** @copydoc DAO::customSectionOrderingExists() */
    public function customSectionOrderingExists(int $issueId): bool
    {
        return $this->dao->customSectionOrderingExists($issueId);
    }

    /** @copydoc DAO::deleteCustomSectionOrdering() */
    public function deleteCustomSectionOrdering(int $issueId): void
    {
        $this->dao->deleteCustomSectionOrdering($issueId);
    }

    /** @copydoc DAO::getCustomSectionOrder() */
    public function getCustomSectionOrder(int $issueId, int $sectionId): ?int
    {
        return $this->dao->getCustomSectionOrder($issueId, $sectionId);
    }

    /** @copydoc DAO::deleteCustomSectionOrder() */
    public function deleteCustomSectionOrder(int $issueId, int $sectionId): void
    {
        $this->dao->deleteCustomSectionOrder($issueId, $sectionId);
    }

    /** @copydoc DAO::upsertCustomSectionOrder() */
    public function upsertCustomSectionOrder(int $issueId, int $sectionId, int $seq): void
    {
        $this->dao->upsertCustomSectionOrder($issueId, $sectionId, $seq);
    }
}
