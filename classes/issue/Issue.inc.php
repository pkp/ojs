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
 * @see IssueDAO
 *
 * @brief Class for Issue.
 */

define('ISSUE_ACCESS_OPEN', 1);
define('ISSUE_ACCESS_SUBSCRIPTION', 2);

class Issue extends DataObject {
	/**
	 * get journal id
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * set journal id
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the localized title
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * get title
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * set title
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * get volume
	 * @return int
	 */
	function getVolume() {
		return $this->getData('volume');
	}

	/**
	 * set volume
	 * @param $volume int
	 */
	function setVolume($volume) {
		return $this->setData('volume', $volume);
	}

	/**
	 * get number
	 * @return string
	 */
	function getNumber() {
		return $this->getData('number');
	}

	/**
	 * set number
	 * @param $number string
	 */
	function setNumber($number) {
		return $this->setData('number', $number);
	}

	/**
	 * get year
	 * @return int
	 */
	function getYear() {
		return $this->getData('year');
	}

	/**
	 * set year
	 * @param $year int
	 */
	function setYear($year) {
		return $this->setData('year', $year);
	}

	/**
	 * get published
	 * @return int
	 */
	function getPublished() {
		return $this->getData('published');
	}

	/**
	 * set published
	 * @param $published int
	 */
	function setPublished($published) {
		return $this->setData('published', $published);
	}

	/**
	 * get current
	 * @return int
	 */
	function getCurrent() {
		return $this->getData('current');
	}

	/**
	 * set current
	 * @param $current int
	 */
	function setCurrent($current) {
		return $this->setData('current', $current);
	}

 	/**
	 * get date published
	 * @return date
	 */
	function getDatePublished() {
		return $this->getData('datePublished');
	}

	/**
	 * set date published
	 * @param $datePublished date
	 */
	function setDatePublished($datePublished) {
		return $this->setData('datePublished', $datePublished);
	}

 	/**
	 * get date the users were last notified
	 * @return date
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}

	/**
	 * set date the users were last notified
	 * @param $dateNotified date
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}

 	/**
	 * get date the issue was last modified
	 * @return date
	 */
	function getLastModified() {
		return $this->getData('lastModified');
	}

	/**
	 * set date the issue was last modified
	 * @param $lastModified date
	 */
	function setLastModified($lastModified) {
		return $this->setData('lastModified', $lastModified);
	}

	/**
	 * Stamp the date of the last modification to the current time.
	 */
	function stampModified() {
		return $this->setLastModified(Core::getCurrentDate());
	}

	/**
	 * get access status (ISSUE_ACCESS_...)
	 * @return int
	 */
	function getAccessStatus() {
		return $this->getData('accessStatus');
	}

	/**
	 * set access status (ISSUE_ACCESS_...)
	 * @param $accessStatus int
	 */
	function setAccessStatus($accessStatus) {
		return $this->setData('accessStatus', $accessStatus);
	}

 	/**
	 * get open access date
	 * @return date
	 */
	function getOpenAccessDate() {
		return $this->getData('openAccessDate');
	}

	/**
	 * set open access date
	 * @param $openAccessDate date
	 */
	function setOpenAccessDate($openAccessDate) {
		return $this->setData('openAccessDate', $openAccessDate);
	}

	/**
	 * Get the localized description
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * get description
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * set description
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get stored public ID of the issue.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @return string
	 */
	function getStoredPubId($pubIdType) {
		return $this->getData('pub-id::'.$pubIdType);
	}

	/**
	 * Set stored public issue id.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function setStoredPubId($pubIdType, $pubId) {
		return $this->setData('pub-id::'.$pubIdType, $pubId);
	}

	/**
	 * get show issue volume
	 * @return int
	 */
	function getShowVolume() {
		return $this->getData('showVolume');
	}

	/**
	 * set show issue volume
	 * @param $showVolume int
	 */
	function setShowVolume($showVolume) {
		return $this->setData('showVolume', $showVolume);
	}

	/**
	 * get show issue number
	 * @return int
	 */
	function getShowNumber() {
		return $this->getData('showNumber');
	}

	/**
	 * set show issue number
	 * @param $showNumber int
	 */
	function setShowNumber($showNumber) {
		return $this->setData('showNumber', $showNumber);
	}

	/**
	 * get show issue year
	 * @return int
	 */
	function getShowYear() {
		return $this->getData('showYear');
	}

	/**
	 * set show issue year
	 * @param $showYear int
	 */
	function setShowYear($showYear) {
		return $this->setData('showYear', $showYear);
	}

	/**
	 * get show issue title
	 * @return int
	 */
	function getShowTitle() {
		return $this->getData('showTitle');
	}

	/**
	 * set show issue title
	 * @param $showTitle int
	 */
	function setShowTitle($showTitle) {
		return $this->setData('showTitle', $showTitle);
	}

	/**
	 * Get the localized issue cover image file name
	 * @return string
	 */
	function getLocalizedCoverImage() {
		return $this->getLocalizedData('coverImage');
	}

	/**
	 * Get issue cover image file name
	 * @param $locale string
	 * @return string|array
	 */
	function getCoverImage($locale) {
		return $this->getData('coverImage', $locale);
	}

	/**
	 * Set issue cover image file name
	 * @param $coverImage string|array
	 * @param $locale string
	 */
	function setCoverImage($coverImage, $locale) {
		return $this->setData('coverImage', $coverImage, $locale);
	}

	/**
	 * Get the localized issue cover image alternate text
	 * @return string
	 */
	function getLocalizedCoverImageAltText() {
		return $this->getLocalizedData('coverImageAltText');
	}

	/**
	 * Get issue cover image alternate text
	 * @param $locale string
	 * @return string
	 */
	function getCoverImageAltText($locale) {
		return $this->getData('coverImageAltText', $locale);
	}

	/**
	 * Get a full URL to the localized cover image
	 *
	 * @return string
	 */
	function getLocalizedCoverImageUrl() {
		$coverImage = $this->getLocalizedCoverImage();
		if (!$coverImage) {
			return '';
		}

		$request = Application::get()->getRequest();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		return $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($this->getJournalId()) . '/' . $coverImage;
	}

	/**
	 * Get the full URL to all localized cover images
	 *
	 * @return array
	 */
	function getCoverImageUrls() {
		$coverImages = $this->getCoverImage(null);
		if (empty($coverImages)) {
			return array();
		}

		$request = Application::get()->getRequest();
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		$urls = array();

		foreach ($coverImages as $locale => $coverImage) {
			$urls[$locale] = sprintf('%s/%s/%s', $request->getBaseUrl(), $publicFileManager->getContextFilesPath($this->getJournalId()), $coverImage);
		}

		return $urls;
	}

	/**
	 * Set issue cover image alternate text
	 * @param $coverImageAltText string
	 * @param $locale string
	 */
	function setCoverImageAltText($coverImageAltText, $locale) {
		return $this->setData('coverImageAltText', $coverImageAltText, $locale);
	}

	/**
	 * Return the string of the issue identification based label format
	 * @param $force array force show/hide of data components
	 * @param $locale string use spcific non-default locale
	 * @return string
	 */
	function getIssueIdentification($force = array(), $locale = null) {

		$displayOptions = array(
			'showVolume' => $this->getData('showVolume'),
			'showNumber' => $this->getData('showNumber'),
			'showYear' => $this->getData('showYear'),
			'showTitle' => $this->getData('showTitle'),
		);

		$displayOptions = array_merge($displayOptions, $force);
		if(is_null($locale)){
			$locale = AppLocale::getLocale();
		}

		AppLocale::requireComponents(array(LOCALE_COMPONENT_APP_COMMON), $locale);
		$volLabel = PKPLocale::translate('issue.vol', array(), $locale);
		$numLabel = PKPLocale::translate('issue.no', array(), $locale);

		$vol = $this->getData('volume');
		$num = $this->getData('number');
		$year = $this->getData('year');
		$title = $this->getTitle($locale);
		if(empty($title)){
			$title = $this->getLocalizedTitle();
		}

		$identification = array();
		foreach($displayOptions as $opt => $val) {

			if (empty($val)) {
				continue;
			}

			if ($opt == 'showVolume') {
				$identification[] = "$volLabel $vol";
			} elseif ($opt == 'showNumber') {
				$identification[] = "$numLabel $num";
			} elseif ($opt == 'showYear') {
				$identification[] = !empty($identification) ? "($year)" : $year;
			} elseif ($opt == 'showTitle' ) {
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
				array(
					'showVolume' => true,
					'showNumber' => true,
					'showYear' => true,
					'showTitle' => false,
				),
				$locale
			);
		}

		return join(' ', $identification);
	}

	/**
	 * Return the string of the issue series identification
	 * eg: Vol 1 No 1 (2000)
	 * @return string
	 */
	function getIssueSeries() {
		if ($this->getShowVolume() || $this->getShowNumber() || $this->getShowYear()) {
			return $this->getIssueIdentification(array('showTitle' => false));
		}
		return null;
	}

	/**
	 * Get number of articles in this issue.
	 * @return int
	 */
	function getNumArticles() {
		$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
		return $issueDao->getNumArticles($this->getId());
	}

	/**
	 * Return the "best" issue ID -- If a public issue ID is set,
	 * use it; otherwise use the internal issue Id.
	 * @return string
	 */
	function getBestIssueId() {
		return $this->getData('urlPath')
			? $this->getData('urlPath')
			: $this->getId();
	}

	/**
	 * Check whether a description exists for this issue
	 * @return bool
	 */
	function hasDescription() {
		$description = $this->getLocalizedDescription();
		return !empty($description);
	}

	/**
	 * @copydoc DataObject::getDAO()
	 */
	function getDAO() {
		return DAORegistry::getDAO('IssueDAO');
	}
}


