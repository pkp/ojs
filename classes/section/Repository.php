<?php
/**
 * @file classes/section/Repository.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class section
 *
 * @brief A repository to find and manage sections.
 */

namespace APP\section;

use Illuminate\Support\LazyCollection;

class Repository extends \PKP\section\Repository
{
    public string $schemaMap = maps\Schema::class;

    /**
     * Retrieve all sections in which articles are currently published in
     * the given issue.
     */
    public function getByIssueId(int $issueId): LazyCollection
    {
        return $this->dao->getByIssueId($issueId);
    }

    /**
     * Delete the custom ordering of an issue's sections.
     */
    public function deleteCustomSectionOrdering(int $issueId): void
    {
        $this->dao->deleteCustomSectionOrdering($issueId);
    }

    /**
     * Get the custom section order of a section.
     */
    public function getCustomSectionOrder(int $issueId, int $sectionId): ?int
    {
        return $this->dao->getCustomSectionOrder($issueId, $sectionId);
    }

    /**
     * Delete a section from the custom section order table.
     */
    public function deleteCustomSectionOrder(int $issueId, int $sectionId): void
    {
        $this->dao->deleteCustomSectionOrder($issueId, $sectionId);
    }

    /**
     * Insert or update a custom section ordering
     */
    public function upsertCustomSectionOrder(int $issueId, int $sectionId, int $seq): void
    {
        $this->dao->upsertCustomSectionOrder($issueId, $sectionId, $seq);
    }
}
