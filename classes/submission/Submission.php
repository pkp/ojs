<?php

/**
 * @defgroup submission Submission
 * Articles, OJS's extension of the generic Submission class in lib-pkp, are
 * implemented here.
 */

/**
 * @file classes/submission/Submission.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Submission
 *
 * @ingroup submission
 *
 * @see DAO
 *
 * @brief Article class.
 */

namespace APP\submission;

use APP\core\Services;
use APP\facades\Repo;
use APP\publication\Publication;
use PKP\facades\Locale;
use PKP\submission\PKPSubmission;

class Submission extends PKPSubmission
{
    // Author display in ToC
    public const AUTHOR_TOC_DEFAULT = 0;
    public const AUTHOR_TOC_HIDE = 1;
    public const AUTHOR_TOC_SHOW = 2;

    // Article access constants -- see Publication::getData('accessStatus')
    public const ARTICLE_ACCESS_ISSUE_DEFAULT = 0;
    public const ARTICLE_ACCESS_OPEN = 1;

    //
    // Get/set methods
    //

    /**
     * Get the value of a license field from the containing context.
     *
     * @param string $locale Locale code
     * @param int $field PERMISSIONS_FIELD_...
     * @param Publication $publication
     *
     * @return string|array|null
     */
    public function _getContextLicenseFieldValue($locale, $field, $publication = null)
    {
        $context = Services::get('context')->get($this->getData('contextId'));
        $fieldValue = null; // Scrutinizer
        switch ($field) {
            case self::PERMISSIONS_FIELD_LICENSE_URL:
                $fieldValue = $context->getData('licenseUrl');
                break;
            case self::PERMISSIONS_FIELD_COPYRIGHT_HOLDER:
                switch ($context->getData('copyrightHolderType')) {
                    case 'author':
                        // Override based on context settings
                        if (!$publication) {
                            $publication = $this->getCurrentPublication();
                        }

                        $authorUserGroups = Repo::userGroup()->getCollector()->filterByRoleIds([\PKP\security\Role::ROLE_ID_AUTHOR])->filterByContextIds([$context->getId()])->getMany();
                        $fieldValue = [$context->getPrimaryLocale() => $publication->getAuthorString($authorUserGroups)];
                        break;
                    case 'context':
                    case null:
                        $fieldValue = $context->getName(null);
                        break;
                    default:
                        $fieldValue = $context->getData('copyrightHolderOther');
                        break;
                }
                break;
            case self::PERMISSIONS_FIELD_COPYRIGHT_YEAR:
                // Default copyright year to current year
                $fieldValue = date('Y');

                // Override based on context settings
                if (!$publication) {
                    $publication = $this->getCurrentPublication();
                }

                if ($publication) {
                    switch ($context->getData('copyrightYearBasis')) {
                        case 'submission':
                            // override to the submission's year if published as you go
                            $fieldValue = date('Y', strtotime($publication->getData('datePublished')));
                            break;
                        case 'issue':
                            if ($publication->getData('issueId')) {
                                // override to the issue's year if published as issue-based
                                $issue = Repo::issue()->get($publication->getData('issueId'));
                                if ($issue && $issue->getDatePublished()) {
                                    $fieldValue = date('Y', strtotime($issue->getDatePublished()));
                                }
                            }
                            break;
                        default: assert(false);
                    }
                }
                break;
            default: assert(false);
        }

        // Return the fetched license field
        if ($locale === null) {
            return $fieldValue;
        }
        if (isset($fieldValue[$locale])) {
            return $fieldValue[$locale];
        }
        return null;
    }

    /**
     * @see PKPSubmission::getBestId()
     * @deprecated 3.2.0.0
     *
     * @return string
     */
    public function getBestArticleId()
    {
        return parent::getBestId();
    }

    /**
     * Get ID of journal.
     *
     * @deprecated 3.2.0.0
     *
     * @return int
     */
    public function getJournalId()
    {
        return $this->getData('contextId');
    }

    /**
     * Set ID of journal.
     *
     * @deprecated 3.2.0.0
     *
     * @param int $journalId
     */
    public function setJournalId($journalId)
    {
        return $this->setData('contextId', $journalId);
    }

    /**
     * Get ID of article's section.
     *
     * @return int
     */
    public function getSectionId()
    {
        $publication = $this->getCurrentPublication();
        if (!$publication) {
            return 0;
        }
        return $publication->getData('sectionId');
    }

    /**
     * Set ID of article's section.
     *
     * @param int $sectionId
     */
    public function setSectionId($sectionId)
    {
        $publication = $this->getCurrentPublication();
        if ($publication) {
            $publication->setData('sectionId', $sectionId);
        }
    }

    /**
     * Get the galleys for an article.
     *
     * @return array Galley
     *
     * @deprecated 3.2.0.0
     */
    public function getGalleys()
    {
        if (!is_null($this->getData('galleys'))) {
            return $this->getData('galleys');
        }

        $this->setData(
            'galleys',
            Repo::galley()->getCollector()
                ->filterByPublicationIds([$this->getData('currentPublicationId')])
                ->getMany()
                ->toArray()
        );

        return $this->getData('galleys');
    }

    /**
     * Get the localized galleys for an article.
     *
     * @return array Galley
     *
     * @deprecated 3.2.0.0
     */
    public function getLocalizedGalleys()
    {
        $allGalleys = $this->getGalleys();
        $galleys = [];
        foreach ([Locale::getLocale(), Locale::getPrimaryLocale()] as $tryLocale) {
            foreach (array_keys($allGalleys) as $key) {
                if ($allGalleys[$key]->getLocale() == $tryLocale) {
                    $galleys[] = $allGalleys[$key];
                }
            }
        }

        return $galleys;
    }

    /**
     * Return option selection indicating if author should be hidden in issue ToC.
     *
     * @return int AUTHOR_TOC_...
     *
     * @deprecated 3.2.0.0
     */
    public function getHideAuthor()
    {
        $publication = $this->getCurrentPublication();
        if (!$publication) {
            return 0;
        }
        return $publication->getData('hideAuthor');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\Submission', '\Submission');
    foreach ([
        'AUTHOR_TOC_DEFAULT',
        'AUTHOR_TOC_HIDE',
        'AUTHOR_TOC_SHOW',
        'ARTICLE_ACCESS_ISSUE_DEFAULT',
        'ARTICLE_ACCESS_OPEN',
    ] as $constantName) {
        define($constantName, constant('\Submission::' . $constantName));
    }
}
