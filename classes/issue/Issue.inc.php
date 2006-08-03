<?php

/**
 * Issue.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Issue.
 *
 * $Id$
 */
 
define('ISSUE_DEFAULT', 0);
define('OPEN_ACCESS', 1);
define('SUBSCRIPTION', 2);

define('ISSUE_LABEL_NUM_VOL_YEAR', 1);
define('ISSUE_LABEL_VOL_YEAR', 2);
define('ISSUE_LABEL_YEAR', 3);
define('ISSUE_LABEL_TITLE', 4);

class Issue extends DataObject {
	/**
	 * get issue id
	 * @return int
	 */
	function getIssueId() {
		return $this->getData('issueId');
	}
	 
	/**
	 * set issue id
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		return $this->setData('issueId',$issueId);
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
		return $this->setData('journalId',$journalId);
	}

	/**
	 * get title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	 
	/**
	 * set title
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title',$title);
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
		return $this->setData('volume',$volume);
	}

	/**
	 * get number
	 * @return int
	 */
	function getNumber() {
		return $this->getData('number');
	}
	 
	/**
	 * set number
	 * @param $number int
	 */
	function setNumber($number) {
		return $this->setData('number',$number);
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
		return $this->setData('year',$year);
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
		return $this->setData('published',$published);
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
		return $this->setData('current',$current);
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
		return $this->setData('datePublished',$datePublished);
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
		return $this->setData('dateNotified',$dateNotified);
	}

	/**
	 * get access status
	 * @return int
	 */
	function getAccessStatus() {
		return $this->getData('accessStatus');
	}
	 
	/**
	 * set access status
	 * @param $accessStatus int
	 */
	function setAccessStatus($accessStatus) {
		return $this->setData('accessStatus',$accessStatus);
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
		return $this->setData('openAccessDate',$openAccessDate);
	}

	/**
	 * get description
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	 
	/**
	 * set description
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description',$description);
	}

	/**
	 * get public issue id
	 * @return string
	 */
	function getPublicIssueId() {
		return $this->getData('publicIssueId');
	}
	 
	/**
	 * set public issue id
	 * @param $publicIssueId string
	 */
	function setPublicIssueId($publicIssueId) {
		return $this->setData('publicIssueId',$publicIssueId);
	}

	/**
	 * get label format
	 * @return int
	 */
	function getLabelFormat() {
		return $this->getData('labelFormat');
	}
	 
	/**
	 * set label format
	 * @param $labelFormat int
	 */
	function setLabelFormat($labelFormat) {
		return $this->setData('labelFormat',$labelFormat);
	}

	/**
	 * get file name
	 * @return string
	 */
	function getFileName() {
		return $this->getData('fileName');
	}
	 
	/**
	 * set file name
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName',$fileName);
	}

	/**
	 * get width of cover page image
	 * @return string
	 */
	function getWidth() {
		return $this->getData('width');
	}
	 
	/**
	 * set width of cover page image
	 * @param $width int
	 */
	function setWidth($width) {
		return $this->setData('width',$width);
	}

	/**
	 * get height of cover page image
	 * @return string
	 */
	function getHeight() {
		return $this->getData('height');
	}
	 
	/**
	 * set height of cover page image
	 * @param $height int
	 */
	function setHeight($height) {
		return $this->setData('height',$height);
	}

	/**
	 * get original file name
	 * @return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}
	 
	/**
	 * set original file name
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		return $this->setData('originalFileName',$originalFileName);
	}

	/**
	 * get cover page description
	 * @return string
	 */
	function getCoverPageDescription() {
		return $this->getData('coverPageDescription');
	}
	 
	/**
	 * set cover page description
	 * @param $coverPageDescription string
	 */
	function setCoverPageDescription($coverPageDescription) {
		return $this->setData('coverPageDescription',$coverPageDescription);
	}

	/**
	 * get show cover page
	 * @return int
	 */
	function getShowCoverPage() {
		return $this->getData('showCoverPage');
	}
	 
	/**
	 * set show cover page
	 * @param $showCoverPage int
	 */
	function setShowCoverPage($showCoverPage) {
		return $this->setData('showCoverPage',$showCoverPage);
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

		$labelFormat = $default ? 1 : $this->getData('labelFormat');
		
		$volLabel = Locale::translate('issue.vol');
		$numLabel = Locale::translate('issue.no');
		$vol = $this->getData('volume');
		$num = $this->getData('number');
		$year = $this->getData('year');
		$title = $this->getData('title');

		switch($labelFormat) {
			case ISSUE_LABEL_NUM_VOL_YEAR:
				$identification = "$volLabel $vol, $numLabel $num ($year)";
				//$breadcrumbId = "$vol.$num ($year)";
				break;
			case ISSUE_LABEL_VOL_YEAR:
				$identification = "$volLabel $vol ($year)";
				//$breadcrumbId = "$vol ($year)";
				break;
			case ISSUE_LABEL_YEAR:
				$identification = "$year";
				//$breadcrumbId = "$year";
				break;
			case ISSUE_LABEL_TITLE:
				$identification = "$title";
				//$breadcrumbId = "$vol.$num ($year)";
				break;
		}
		
		$breadcrumbId = $identification;
		
		if ($long && $labelFormat != ISSUE_LABEL_TITLE && !empty($title)) {
			$identification .= ' ' . $title;
		}

		return $breadcrumb ? $breadcrumbId : $identification;
	}

	/**
	 * Get number of articles in this issue.
	 * @return int
	 */
	function getNumArticles() {
		return $this->getData('numArticles');
	}

	/**
	 * Set number of articles in this issue.
	 * @param $numArticles int
	 */
	function setNumArticles($numArticles) {
		return $this->setData('numArticles', $numArticles);
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
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getJournal($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicIssueId')) {
			$publicIssueId = $this->getPublicIssueId();
			if (!empty($publicIssueId)) return $publicIssueId;
		}
		return $this->getIssueId();
	}
 }
 
?>
