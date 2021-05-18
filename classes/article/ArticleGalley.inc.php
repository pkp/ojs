<?php

/**
 * @file classes/article/ArticleGalley.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalley
 * @ingroup article
 *
 * @see ArticleGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an article.
 */

namespace APP\article;

use PKP\submission\Representation;

use APP\core\Application;
use APP\core\Services;
use APP\i18n\AppLocale;

class ArticleGalley extends Representation
{
    /** @var SubmissionFile */
    public $_submissionFile;


    //
    // Get/set methods
    //
    /**
     * Get views count.
     *
     * @return int
     */
    public function getViews()
    {
        $application = Application::get();
        $fileId = $this->getFileId();
        if ($fileId) {
            return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_SUBMISSION_FILE, $fileId);
        } else {
            return 0;
        }
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
     * @param $label string
     */
    public function setLabel($label)
    {
        $this->setData('label', $label);
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
     * @param $locale string
     */
    public function setLocale($locale)
    {
        $this->setData('locale', $locale);
    }

    /**
     * Return the "best" article ID -- If a public article ID is set,
     * use it; otherwise use the internal article Id.
     *
     * @return string
     */
    public function getBestGalleyId()
    {
        return $this->getData('urlPath')
            ? $this->getData('urlPath')
            : $this->getId();
    }

    /**
     * Set file ID.
     *
     * @deprecated 3.3
     *
     * @param $fileId int
     */
    public function setFileId($fileId)
    {
        $this->setData('submissionFileId', $fileId);
    }

    /**
     * Get file id
     *
     * @deprecated 3.3
     *
     * @return int
     */
    public function getFileId()
    {
        return $this->getData('submissionFileId');
    }

    /**
     * Get the submission file corresponding to this galley.
     *
     * @deprecated 3.3
     *
     * @return SubmissionFile
     */
    public function getFile()
    {
        if (!isset($this->_submissionFile)) {
            $this->_submissionFile = Services::get('submissionFile')->get($this->getData('submissionFileId'));
        }
        return $this->_submissionFile;
    }

    /**
     * Get the file type corresponding to this galley.
     *
     * @deprecated 3.3
     *
     * @return string MIME type
     */
    public function getFileType()
    {
        $galleyFile = $this->getFile();
        return $galleyFile ? $galleyFile->getData('mimetype') : null;
    }

    /**
     * Determine whether the galley is a PDF.
     *
     * @return boolean
     */
    public function isPdfGalley()
    {
        return $this->getFileType() == 'application/pdf';
    }

    /**
     * Get the localized galley label.
     *
     * @return string
     */
    public function getGalleyLabel()
    {
        $label = $this->getLabel();
        if ($this->getLocale() != AppLocale::getLocale()) {
            $locales = AppLocale::getAllLocales();
            $label .= ' (' . $locales[$this->getLocale()] . ')';
        }
        return $label;
    }

    /**
     * @see Representation::getName()
     *
     * This override exists to provide a functional getName() in order to make
     * native XML export work correctly.  It is only used in that single instance.
     *
     * @param $locale string unused, except to match the function prototype in Representation.
     *
     * @return array
     */
    public function getName($locale)
    {
        return [$this->getLocale() => $this->getLabel()];
    }

    /**
     * Override the parent class to fetch the non-localized label.
     *
     * @see Representation::getLocalizedName()
     *
     * @return string
     */
    public function getLocalizedName()
    {
        return $this->getLabel();
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\article\ArticleGalley', '\ArticleGalley');
}
