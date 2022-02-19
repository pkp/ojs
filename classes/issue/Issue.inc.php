<?php

/**
 * @defgroup issue Issue
 * Implement journal issues.
 */

/**
 * @file classes/issue/Issue.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Issue
 * @ingroup issue
 *
 * @see \APP\issue\DAO
 *
 * @brief Class for Issue.
 */

namespace APP\issue;

use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use PKP\facades\Locale;
use PKP\core\Core;
use PKP\submission\PKPSubmission;

class Issue extends \PKP\core\DataObject
{
    public const ISSUE_ACCESS_OPEN = 1;
    public const ISSUE_ACCESS_SUBSCRIPTION = 2;

    /**
     * get journal id
     *
     * @return int
     */
    public function getJournalId()
    {
        return $this->getData('journalId');
    }

    /**
     * set journal id
     *
     * @param int $journalId
     */
    public function setJournalId($journalId)
    {
        return $this->setData('journalId', $journalId);
    }

    /**
     * Get the localized title
     *
     * @return string
     */
    public function getLocalizedTitle()
    {
        return $this->getLocalizedData('title');
    }

    /**
     * get title
     *
     * @param string $locale
     *
     * @return string
     */
    public function getTitle($locale)
    {
        return $this->getData('title', $locale);
    }

    /**
     * set title
     *
     * @param string $title
     * @param string $locale
     */
    public function setTitle($title, $locale)
    {
        return $this->setData('title', $title, $locale);
    }

    /**
     * get volume
     *
     * @return int
     */
    public function getVolume()
    {
        return $this->getData('volume');
    }

    /**
     * set volume
     *
     * @param int $volume
     */
    public function setVolume($volume)
    {
        return $this->setData('volume', $volume);
    }

    /**
     * get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->getData('number');
    }

    /**
     * set number
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        return $this->setData('number', $number);
    }

    /**
     * get year
     *
     * @return int
     */
    public function getYear()
    {
        return $this->getData('year');
    }

    /**
     * set year
     *
     * @param int $year
     */
    public function setYear($year)
    {
        return $this->setData('year', $year);
    }

    /**
     * get published
     *
     * @return int
     */
    public function getPublished()
    {
        return $this->getData('published');
    }

    /**
     * set published
     *
     * @param int $published
     */
    public function setPublished($published)
    {
        return $this->setData('published', $published);
    }

    /**
     * get date published
     *
     * @return date
     */
    public function getDatePublished()
    {
        return $this->getData('datePublished');
    }

    /**
     * set date published
     *
     * @param date $datePublished
     */
    public function setDatePublished($datePublished)
    {
        return $this->setData('datePublished', $datePublished);
    }

    /**
     * get date the users were last notified
     *
     * @return date
     */
    public function getDateNotified()
    {
        return $this->getData('dateNotified');
    }

    /**
     * set date the users were last notified
     *
     * @param date $dateNotified
     */
    public function setDateNotified($dateNotified)
    {
        return $this->setData('dateNotified', $dateNotified);
    }

    /**
     * get date the issue was last modified
     *
     * @return date
     */
    public function getLastModified()
    {
        return $this->getData('lastModified');
    }

    /**
     * set date the issue was last modified
     *
     * @param date $lastModified
     */
    public function setLastModified($lastModified)
    {
        return $this->setData('lastModified', $lastModified);
    }

    /**
     * Stamp the date of the last modification to the current time.
     */
    public function stampModified()
    {
        return $this->setLastModified(Core::getCurrentDate());
    }

    /**
     * get access status (ISSUE_ACCESS_...)
     *
     * @return int
     */
    public function getAccessStatus()
    {
        return $this->getData('accessStatus');
    }

    /**
     * set access status (ISSUE_ACCESS_...)
     *
     * @param int $accessStatus
     */
    public function setAccessStatus($accessStatus)
    {
        return $this->setData('accessStatus', $accessStatus);
    }

    /**
     * get open access date
     *
     * @return date
     */
    public function getOpenAccessDate()
    {
        return $this->getData('openAccessDate');
    }

    /**
     * set open access date
     *
     * @param date $openAccessDate
     */
    public function setOpenAccessDate($openAccessDate)
    {
        return $this->setData('openAccessDate', $openAccessDate);
    }

    /**
     * Get the localized description
     *
     * @return string
     */
    public function getLocalizedDescription()
    {
        return $this->getLocalizedData('description');
    }

    /**
     * get description
     *
     * @param string $locale
     *
     * @return string
     */
    public function getDescription($locale)
    {
        return $this->getData('description', $locale);
    }

    /**
     * set description
     *
     * @param string $description
     * @param string $locale
     */
    public function setDescription($description, $locale)
    {
        return $this->setData('description', $description, $locale);
    }

    /**
     * Returns current DOI
     *
     */
    public function getDoi(): ?string
    {
        $doiObject = $this->getData('doiObject');

        if (empty($doiObject)) {
            return null;
        } else {
            return $doiObject->getData('doi');
        }
    }

    /**
     * Get stored public ID of the issue.
     *
     * This helper function is required by PKPPubIdPlugins.
     * NB: To maintain backwards compatability, getDoi() is called from here
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     *
     * @return string
     */
    public function getStoredPubId($pubIdType)
    {
        if ($pubIdType == 'doi') {
            return $this->getDoi();
        } else {
            return $this->getData('pub-id::' . $pubIdType);
        }
    }

    /**
     * Set stored public issue id.
     *
     * @param string $pubIdType One of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     * @param string $pubId
     */
    public function setStoredPubId($pubIdType, $pubId)
    {
        if ($pubIdType == 'doi') {
            if ($doiObject = $this->getData('doiObject')) {
                Repo::doi()->edit($doiObject, ['doi' => $pubId]);
            } else {
                $newDoiObject = Repo::doi()->newDataObject(
                    [
                        'doi' => $pubId,
                        'contextId' => $this->getJournalId()
                    ]
                );
                $doiId = Repo::doi()->add($newDoiObject);
                $this->setData('doiId', $doiId);
            }
        } else {
            $this->setData('pub-id::' . $pubIdType, $pubId);
        }
    }

    /**
     * get show issue volume
     *
     * @return int
     */
    public function getShowVolume()
    {
        return $this->getData('showVolume');
    }

    /**
     * set show issue volume
     *
     * @param int $showVolume
     */
    public function setShowVolume($showVolume)
    {
        return $this->setData('showVolume', $showVolume);
    }

    /**
     * get show issue number
     *
     * @return int
     */
    public function getShowNumber()
    {
        return $this->getData('showNumber');
    }

    /**
     * set show issue number
     *
     * @param int $showNumber
     */
    public function setShowNumber($showNumber)
    {
        return $this->setData('showNumber', $showNumber);
    }

    /**
     * get show issue year
     *
     * @return int
     */
    public function getShowYear()
    {
        return $this->getData('showYear');
    }

    /**
     * set show issue year
     *
     * @param int $showYear
     */
    public function setShowYear($showYear)
    {
        return $this->setData('showYear', $showYear);
    }

    /**
     * get show issue title
     *
     * @return int
     */
    public function getShowTitle()
    {
        return $this->getData('showTitle');
    }

    /**
     * set show issue title
     *
     * @param int $showTitle
     */
    public function setShowTitle($showTitle)
    {
        return $this->setData('showTitle', $showTitle);
    }

    /**
     * Get the localized issue cover image file name
     *
     * @return string
     */
    public function getLocalizedCoverImage()
    {
        return $this->getLocalizedData('coverImage');
    }

    /**
     * Get issue cover image file name
     *
     * @param string $locale
     *
     * @return string|array
     */
    public function getCoverImage($locale)
    {
        return $this->getData('coverImage', $locale);
    }

    /**
     * Set issue cover image file name
     *
     * @param string|array $coverImage
     * @param string $locale
     */
    public function setCoverImage($coverImage, $locale)
    {
        return $this->setData('coverImage', $coverImage, $locale);
    }

    /**
     * Get the localized issue cover image alternate text
     *
     * @return string
     */
    public function getLocalizedCoverImageAltText()
    {
        return $this->getLocalizedData('coverImageAltText');
    }

    /**
     * Get issue cover image alternate text
     *
     * @param string $locale
     *
     * @return string
     */
    public function getCoverImageAltText($locale)
    {
        return $this->getData('coverImageAltText', $locale);
    }

    /**
     * Get a full URL to the localized cover image
     *
     * @return string
     */
    public function getLocalizedCoverImageUrl()
    {
        $coverImage = $this->getLocalizedCoverImage();
        if (!$coverImage) {
            return '';
        }

        $request = Application::get()->getRequest();

        $publicFileManager = new PublicFileManager();

        return $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($this->getJournalId()) . '/' . $coverImage;
    }

    /**
     * Get the full URL to all localized cover images
     *
     * @return array
     */
    public function getCoverImageUrls()
    {
        $coverImages = $this->getCoverImage(null);
        if (empty($coverImages)) {
            return [];
        }

        $request = Application::get()->getRequest();
        $publicFileManager = new PublicFileManager();

        $urls = [];

        foreach ($coverImages as $locale => $coverImage) {
            $urls[$locale] = sprintf('%s/%s/%s', $request->getBaseUrl(), $publicFileManager->getContextFilesPath($this->getJournalId()), $coverImage);
        }

        return $urls;
    }

    /**
     * Set issue cover image alternate text
     *
     * @param string $coverImageAltText
     * @param string $locale
     */
    public function setCoverImageAltText($coverImageAltText, $locale)
    {
        return $this->setData('coverImageAltText', $coverImageAltText, $locale);
    }

    /**
     * Return the string of the issue identification based label format
     *
     * @param array $force force show/hide of data components
     * @param string $locale use specific non-default locale
     *
     * @return string
     */
    public function getIssueIdentification($force = [], $locale = null)
    {
        $displayOptions = [
            'showVolume' => $this->getData('showVolume'),
            'showNumber' => $this->getData('showNumber'),
            'showYear' => $this->getData('showYear'),
            'showTitle' => $this->getData('showTitle'),
        ];

        $displayOptions = array_merge($displayOptions, $force);
        if (is_null($locale)) {
            $locale = Locale::getLocale();
        }

        $volLabel = __('issue.vol', [], $locale);
        $numLabel = __('issue.no', [], $locale);

        $vol = $this->getData('volume');
        $num = $this->getData('number');
        $year = $this->getData('year');
        $title = $this->getTitle($locale);
        if (empty($title)) {
            $title = $this->getLocalizedTitle();
        }

        $identification = [];
        foreach ($displayOptions as $opt => $val) {
            if (empty($val)) {
                continue;
            }

            if ($opt == 'showVolume') {
                $identification[] = "${volLabel} ${vol}";
            } elseif ($opt == 'showNumber') {
                $identification[] = "${numLabel} ${num}";
            } elseif ($opt == 'showYear') {
                $identification[] = !empty($identification) ? "(${year})" : $year;
            } elseif ($opt == 'showTitle') {
                if (!empty($title)) {
                    // Append a separator to the last key
                    if (!empty($identification)) {
                        end($identification);
                        $identification[key($identification)] .= ':';
                    }
                    $identification[] = $title;
                }
            }
        }

        // If we've got an empty title, re-run the function and force a result
        if (empty($identification)) {
            return $this->getIssueIdentification(
                [
                    'showVolume' => true,
                    'showNumber' => true,
                    'showYear' => true,
                    'showTitle' => false,
                ],
                $locale
            );
        }

        return join(' ', $identification);
    }

    /**
     * Return the string of the issue series identification
     * eg: Vol 1 No 1 (2000)
     *
     * @return string
     */
    public function getIssueSeries()
    {
        if ($this->getShowVolume() || $this->getShowNumber() || $this->getShowYear()) {
            return $this->getIssueIdentification(['showTitle' => false]);
        }
        return null;
    }

    /**
     * Get number of articles in this issue.
     *
     * @return int
     */
    public function getNumArticles()
    {
        $collector = Repo::submission()->getCollector()
            ->filterByContextIds([$this->getData('journalId')])
            ->filterByIssueIds([$this->getId()])
            ->filterByStatus([PKPSubmission::STATUS_SCHEDULED, PKPSubmission::STATUS_PUBLISHED]);
        return Repo::submission()->getCount($collector);
    }

    /**
     * Return the "best" issue ID -- If a public issue ID is set,
     * use it; otherwise use the internal issue Id.
     *
     * @return string
     */
    public function getBestIssueId()
    {
        return $this->getData('urlPath')
            ? $this->getData('urlPath')
            : $this->getId();
    }

    /**
     * Check whether a description exists for this issue
     *
     * @return bool
     */
    public function hasDescription()
    {
        $description = $this->getLocalizedDescription();
        return !empty($description);
    }

    /**
     * Checks whether issue had DOI assigned to it
     *
     */
    public function hasDoi(): bool
    {
        return (bool) $this->getData('doiObject');
    }

    /**
     * @copydoc \PKP\core\DataObject::getDAO()
     */
    public function getDAO(): DAO
    {
        return Repo::issue()->dao;
    }

    /**
     * Display the object in Import/Export results
     *
     * @return string A string that Identifies the object
     */
    public function getUIDisplayString()
    {
        return __('plugins.importexport.issue.cli.display', ['issueId' => $this->getId(), 'issueIdentification' => $this->getIssueIdentification()]);
    }
}
if (!PKP_STRICT_MODE) {
    class_alias('\APP\issue\Issue', '\Issue');
    foreach ([
        'ISSUE_ACCESS_OPEN',
        'ISSUE_ACCESS_SUBSCRIPTION',
    ] as $constantName) {
        define($constantName, constant('\Issue::' . $constantName));
    }
}
