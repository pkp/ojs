<?php

/**
 * @file classes/issue/IssueGalley.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalley
 *
 * @see IssueGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an issue.
 */

namespace APP\issue;

use PKP\db\DAORegistry;
use PKP\facades\Locale;

class IssueGalley extends IssueFile
{
    public ?IssueFile $_issueFile;


    /**
     * Check if galley is a PDF galley.
     */
    public function isPdfGalley(): bool
    {
        switch ($this->getFileType()) {
            case 'application/pdf':
            case 'application/x-pdf':
            case 'text/pdf':
            case 'text/x-pdf':
                return true;
            default: return false;
        }
    }

    //
    // Get/set methods
    //
    /**
     * Get the localized value of the galley label.
     */
    public function getGalleyLabel(): string
    {
        $label = $this->getLabel();
        if (($locale = $this->getLocale()) && $locale !== Locale::getLocale()) {
            $label .= ' (' . Locale::getSubmissionLocaleDisplayNames([$locale])[$locale] . ')';
        }
        return $label;
    }

    /**
     * Get label/title.
     */
    public function getLabel(): string
    {
        return $this->getData('label');
    }

    /**
     * Set label/title.
     */
    public function setLabel(string $label): void
    {
        $this->setData('label', $label);
    }

    /**
     * Get locale.
     */
    public function getLocale(): string
    {
        return $this->getData('locale');
    }

    /**
     * Set locale.
     */
    public function setLocale(string $locale): void
    {
        $this->setData('locale', $locale);
    }

    /**
     * Get sequence order.
     */
    public function getSequence(): ?float
    {
        return $this->getData('sequence');
    }

    /**
     * Set sequence order.
     */
    public function setSequence(float $sequence): void
    {
        $this->setData('sequence', $sequence);
    }

    /**
     * Get file ID.
     */
    public function getFileId(): int
    {
        return $this->getData('fileId');
    }

    /**
     * Set file ID.
     */
    public function setFileId(int $fileId): void
    {
        $this->setData('fileId', $fileId);
    }

    /**
     * Get stored public ID of the galley.
     *
     * @param $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     */
    public function getStoredPubId(string $pubIdType): null|int|string
    {
        return $this->getData('pub-id::' . $pubIdType);
    }

    /**
     * Set stored public galley id.
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     * @param string $pubId
     */
    public function setStoredPubId(string $pubIdType, null|int|string $pubId): void
    {
        $this->setData('pub-id::' . $pubIdType, $pubId);
    }

    /**
     * Return the "best" issue galley ID -- If a urlPath is set,
     * use it; otherwise use the internal article Id.
     */
    public function getBestGalleyId(): string|int
    {
        return strlen($urlPath = (string) $this->getData('urlPath')) ? $urlPath : $this->getId();
    }

    /**
     * Get the file corresponding to this galley.
     */
    public function getFile(): ?IssueFile
    {
        if (!isset($this->_issueFile)) {
            $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */
            $this->_issueFile = $issueFileDao->getById($this->getFileId());
        }
        return $this->_issueFile;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\issue\IssueGalley', '\IssueGalley');
}
