<?php

/**
 * Issue.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Issue.
 *
 * $Id$
 */
 
class Issue extends DataObject {
 
	/**
	 * Constructor.
	 */
	function Issue() {
		parent::DataObject();
	}
	
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

 }
 
?>
