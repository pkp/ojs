<?php

/**
 * @defgroup issue
 */

/**
 * @file classes/issue/Issue.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
	 * get issue id
	 * @return int
	 */
	function getIssueId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * set issue id
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($issueId);
	}

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

	function getIssueTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
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

	function getIssueDescription() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedDescription();
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
	 * Get a public ID for this issue.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @var $preview boolean If true, generate a non-persisted preview only.
	 */
	function getPubId($pubIdType, $preview = false) {
		// FIXME: Move publisher-id to PID plug-in.
		if ($pubIdType === 'publisher-id') {
			$pubId = $this->getStoredPubId($pubIdType);
			return ($pubId ? $pubId : null);
		}

		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $this->getJournalId());
		foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($pubIdPlugin->getPubIdType() === $pubIdType) {
				// If we already have an assigned ID, use it.
				$storedId = $this->getStoredPubId($pubIdType);
				if (!empty($storedId)) return $storedId;

				return $pubIdPlugin->getPubId($this, $preview);
			}
		}
		return null;
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
	 * Get the localized issue cover filename
	 * @return string
	 */
	function getLocalizedFileName() {
		return $this->getLocalizedData('fileName');
	}

	function getIssueFileName() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedFileName();
	}

	/**
	 * Get issue cover image file name
	 * @param $locale string
	 * @return string
	 */
	function getFileName($locale) {
		return $this->getData('fileName', $locale);
	}

	/**
	 * set file name
	 * @param $fileName string
	 * @param $locale string
	 */
	function setFileName($fileName, $locale) {
		return $this->setData('fileName', $fileName, $locale);
	}

	/**
	 * Get the localized issue cover width
	 * @return string
	 */
	function getLocalizedWidth() {
		return $this->getLocalizedData('width');
	}

	function getIssueWidth() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedWidth();
	}

	/**
	 * get width of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getWidth($locale) {
		return $this->getData('width', $locale);
	}

	/**
	 * set width of cover page image
	 * @param $locale string
	 * @param $width int
	 */
	function setWidth($width, $locale) {
		return $this->setData('width', $width, $locale);
	}

	/**
	 * Get the localized issue cover height
	 * @return string
	 */
	function getLocalizedHeight() {
		return $this->getLocalizedData('height');
	}

	function getIssueHeight() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedHeight();
	}

	/**
	 * get height of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getHeight($locale) {
		return $this->getData('height', $locale);
	}

	/**
	 * set height of cover page image
	 * @param $locale string
	 * @param $height int
	 */
	function setHeight($height, $locale) {
		return $this->setData('height', $height, $locale);
	}

	/**
	 * Get the localized issue cover filename on the uploader's computer
	 * @return string
	 */
	function getLocalizedOriginalFileName() {
		return $this->getLocalizedData('originalFileName');
	}

	function getIssueOriginalFileName() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedOriginalFileName();
	}

	/**
	 * Get original issue cover image file name
	 * @param $locale string
	 * @return string
	 */
	function getOriginalFileName($locale) {
		return $this->getData('originalFileName', $locale);
	}

	/**
	 * set original file name
	 * @param $originalFileName string
	 * @param $locale string
	 */
	function setOriginalFileName($originalFileName, $locale) {
		return $this->setData('originalFileName', $originalFileName, $locale);
	}

	/**
	 * Get the localized issue cover alternate text
	 * @return string
	 */
	function getLocalizedCoverPageAltText() {
		return $this->getLocalizedData('coverPageAltText');
	}

	function getIssueCoverPageAltText() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverPageAltText();
	}

	/**
	 * Get issue cover image alternate text
	 * @param $locale string
	 * @return string
	 */
	function getCoverPageAltText($locale) {
		return $this->getData('coverPageAltText', $locale);
	}

	/**
	 * Set issue cover image alternate text
	 * @param $coverPageAltText string
	 * @param $locale string
	 */
	function setCoverPageAltText($coverPageAltText, $locale) {
		return $this->setData('coverPageAltText', $coverPageAltText, $locale);
	}

	/**
	 * Get the localized issue cover description
	 * @return string
	 */
	function getLocalizedCoverPageDescription() {
		return $this->getLocalizedData('coverPageDescription');
	}

	function getIssueCoverPageDescription() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverPageDescription();
	}

	/**
	 * get cover page description
	 * @param $locale string
	 * @return string
	 */
	function getCoverPageDescription($locale) {
		return $this->getData('coverPageDescription', $locale);
	}

	/**
	 * set cover page description
	 * @param $coverPageDescription string
	 * @param $locale string
	 */
	function setCoverPageDescription($coverPageDescription, $locale) {
		return $this->setData('coverPageDescription', $coverPageDescription, $locale);
	}

	/**
	 * Get the localized issue cover enable/disable flag
	 * @return string
	 */
	function getLocalizedShowCoverPage() {
		return $this->getLocalizedData('showCoverPage');
	}

	function getIssueShowCoverPage() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedShowCoverPage();
	}

	/**
	 * Get show issue cover image flag
	 * @param $locale string
	 * @return int
	 */
	function getShowCoverPage($locale) {
		return $this->getData('showCoverPage', $locale);
	}

	/**
	 * Set show issue cover image flag
	 * @param $showCoverPage int
	 * @param $locale string
	 */
	function setShowCoverPage($showCoverPage, $locale) {
		return $this->setData('showCoverPage', $showCoverPage, $locale);
	}

	/**
	 * get hide cover page in archives
	 * @param $locale string
	 * @return int
	 */
	function getHideCoverPageArchives($locale) {
		return $this->getData('hideCoverPageArchives', $locale);
	}

	/**
	 * set hide cover page in archives
	 * @param $hideCoverPageArchives int
	 * @param $locale string
	 */
	function setHideCoverPageArchives($hideCoverPageArchives, $locale) {
		return $this->setData('hideCoverPageArchives', $hideCoverPageArchives, $locale);
	}

	/**
	 * get hide cover page prior to ToC
	 * @param $locale string
	 * @return int
	 */
	function getHideCoverPageCover($locale) {
		return $this->getData('hideCoverPageCover', $locale);
	}

	/**
	 * set hide cover page prior to ToC
	 * @param $hideCoverPageCover int
	 * @param $locale string
	 */
	function setHideCoverPageCover($hideCoverPageCover, $locale) {
		return $this->setData('hideCoverPageCover', $hideCoverPageCover, $locale);
	}

	/**
	 * get style file name
	 * @return string
	 */
	function getStyleFileName() {
		return $this->getData('styleFileName');
	}

	/**
	 * set style file name
	 * @param $styleFileName string
	 */
	function setStyleFileName($styleFileName) {
		return $this->setData('styleFileName', $styleFileName);
	}

	/**
	 * get original style file name
	 * @return string
	 */
	function getOriginalStyleFileName() {
		return $this->getData('originalStyleFileName');
	}

	/**
	 * set original style file name
	 * @param $originalStyleFileName string
	 */
	function setOriginalStyleFileName($originalStyleFileName) {
		return $this->setData('originalStyleFileName', $originalStyleFileName);
	}

	/**
	 * Return string of author names, separated by the specified token
	 * @param $lastOnly boolean return list of lastnames only (default false)
	 * @param $separator string separator for names (default comma+space)
	 * @return string
	 */
	function getAuthorString($lastOnly = false, $separator = ', ') {
		$str = '';
		foreach ($this->getAuthors() as $a) {
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $lastOnly ? $a->getLastName() : $a->getFullName();
		}
		return $str;
	}

	/**
	 * Return the string of the issue identification based label format
	 * @param $default bool labelFormat type
	 * @param $breadcrumb bool return type of label
	 * @param $long bool long format of label
	 * @return string
	 */
	function getIssueIdentification($default = false, $breadcrumb = false, $long = false) {

		if ($default) {
			$showVolume = 1;
			$showNumber = 1;
			$showYear = 1;
			$showTitle = 0;
		} else {
			$showVolume = $this->getData('showVolume');
			$showNumber = $this->getData('showNumber');
			$showYear = $this->getData('showYear');
			$showTitle = $this->getData('showTitle');
		}

		if ($breadcrumb && ($showVolume || $showNumber || $showYear)) {
			$showTitle = 0;
		}

		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$volLabel = __('issue.vol');
		$numLabel = __('issue.no');

		$vol = $this->getData('volume');
		$num = $this->getData('number');
		$year = $this->getData('year');
		$title = $this->getLocalizedData('title');

		$identification = '';

		if ($showVolume) {
			$identification = "$volLabel $vol";
		}
		if ($showNumber) {
			if (!empty($identification)) {
				$identification .= ", ";
			}
			$identification .= "$numLabel $num";
		}
		if ($showYear) {
			if (!empty($identification)) {
				$identification .= " ($year)";
			} else {
				$identification = "$year";
			}
		}

		if ($showTitle || ($long && !empty($title))) {
			if (!empty($identification)) {
				$identification .= ': ';
			}
			$identification .= "$title";
		}

		if (empty($identification)) {
			$identification = "$volLabel $vol, $numLabel $num ($year)";
		}

		return $identification;
	}

	/**
	 * Get number of articles in this issue.
	 * @return int
	 */
	function getNumArticles() {
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		return $issueDao->getNumArticles($this->getId());
	}

	/**
	 * Return the "best" issue ID -- If a public issue ID is set,
	 * use it; otherwise use the internal issue Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal object The journal this issue is in
	 * @return string
	 */
	function getBestIssueId($journal = null) {
		// Retrieve the journal, if necessary.
		if (!isset($journal)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicIssueId')) {
			$publicIssueId = $this->getPubId('publisher-id');
			if (!empty($publicIssueId)) return $publicIssueId;
		}
		return $this->getId();
	}
}

?>
