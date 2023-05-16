<?php
/**
 * @defgroup issue_galley Issue Galleys
 * Issue galleys allow for the representation of an entire journal issue with
 * a single file, typically a PDF.
 */

/**
 * @file classes/issue/IssueGalley.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalley
 *
 * @ingroup issue_galley
 *
 * @see IssueGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an issue.
 */

namespace APP\issue;

use APP\core\Application;
use APP\core\Services;
use APP\statistics\StatisticsHelper;
use PKP\db\DAORegistry;
use PKP\facades\Locale;

class IssueGalley extends IssueFile
{
    /** @var IssueFile */
    public $_issueFile;


    /**
     * Check if galley is a PDF galley.
     *
     * @return bool
     */
    public function isPdfGalley()
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
     * Get views count.
     *
     * @deprecated 3.4
     *
     * @return int
     */
    public function getViews()
    {
        $filters = [
            'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
            'dateEnd' => date('Y-m-d', strtotime('yesterday')),
            'contextIds' => [Application::get()->getRequest()->getContext()->getId()],
            'issueGalleyIds' => [$this->getId()],
        ];
        $metrics = Services::get('issueStats')
            ->getQueryBuilder($filters)
            ->getSum([])
            ->value('metric');
        return $metrics ? $metrics : 0;
    }

    /**
     * Get the localized value of the galley label.
     *
     * @return $string
     */
    public function getGalleyLabel()
    {
        $label = $this->getLabel();
        if ($this->getLocale() != Locale::getLocale()) {
            $label .= ' (' . Locale::getMetadata($this->getLocale())->getDisplayName() . ')';
        }
        return $label;
    }

    /**
     * Get label/title.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->getData('label');
    }

    /**
     * Set label/title.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        return $this->setData('label', $label);
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->getData('locale');
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        return $this->setData('locale', $locale);
    }

    /**
     * Get sequence order.
     *
     * @return float
     */
    public function getSequence()
    {
        return $this->getData('sequence');
    }

    /**
     * Set sequence order.
     *
     * @param float $sequence
     */
    public function setSequence($sequence)
    {
        return $this->setData('sequence', $sequence);
    }

    /**
     * Get file ID.
     *
     * @return int
     */
    public function getFileId()
    {
        return $this->getData('fileId');
    }

    /**
     * Set file ID.
     *
     * @param int $fileId
     */
    public function setFileId($fileId)
    {
        return $this->setData('fileId', $fileId);
    }

    /**
     * Get stored public ID of the galley.
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     *
     * @return string
     */
    public function getStoredPubId($pubIdType)
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
    public function setStoredPubId($pubIdType, $pubId)
    {
        return $this->setData('pub-id::' . $pubIdType, $pubId);
    }

    /**
     * Return the "best" issue galley ID -- If a urlPath is set,
     * use it; otherwise use the internal article Id.
     *
     * @return string
     */
    public function getBestGalleyId()
    {
        return strlen($urlPath = (string) $this->getData('urlPath')) ? $urlPath : $this->getId();
    }

    /**
     * Get the file corresponding to this galley.
     *
     * @return IssueFile
     */
    public function getFile()
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
