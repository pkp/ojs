<?php

/**
 * @file classes/issue/IssueFile.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueFile
 *
 * @ingroup issue
 *
 * @brief Issue file class.
 */

namespace APP\issue;

use PKP\file\PKPFile;

class IssueFile extends PKPFile
{
    /** @var int File content type IDs */
    public const ISSUE_FILE_PUBLIC = 1;

    //
    // Get/set methods
    //

    /**
     * Get ID of issue.
     *
     * @return int
     */
    public function getIssueId()
    {
        return $this->getData('issueId');
    }

    /**
     * set ID of issue.
     *
     * @param int $issueId
     */
    public function setIssueId($issueId)
    {
        return $this->setData('issueId', $issueId);
    }

    /**
     * Get content type of the file.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getData('contentType');
    }

    /**
     * set type of the file.
     */
    public function setContentType($contentType)
    {
        return $this->setData('contentType', $contentType);
    }

    /**
     * Get modified date of file.
     *
     * @return string
     */
    public function getDateModified()
    {
        return $this->getData('dateModified');
    }

    /**
     * set modified date of file.
     *
     * @param string $dateModified
     */
    public function setDateModified($dateModified)
    {
        return $this->setData('dateModified', $dateModified);
    }
}
