<?php

/**
 * FrontMatter.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Front Matter.
 *
 * $Id$
 */
 
class FrontMatter extends DataObject {
 
	/**
	 * Constructor.
	 */
	function FrontMatter() {
		parent::DataObject();
	}

	/**
	 * get front matter id
	 * @return int
	 */
	function getFrontId() {
		return $this->getData('frontId');
	}
	 
	/**
	 * set front matter id
	 * @param $frontId int
	 */
	function setFrontId($frontId) {
		return $this->setData('frontId',$frontId);
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
	 * get front matter section id
	 * @return int
	 */
	function getFrontSectionId() {
		return $this->getData('frontSectionId');
	}
	 
	/**
	 * set front matter section id
	 * @param $frontSectionId int
	 */
	function setFrontSectionId($frontSectionId) {
		return $this->setData('frontSectionId',$frontSectionId);
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
	 * get date created
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}
	 
	/**
	 * set date created
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated',$dateCreated);
	}
 
 	/**
	 * get date modified
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}
	 
	/**
	 * set date modified
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified',$dateModified);
	}

	/**
	 * get cover
	 * @return int
	 */
	function getCover() {
		return $this->getData('cover');
	}
	 
	/**
	 * set cover
	 * @param $cover int
	 */
	function setCover($cover) {
		return $this->setData('cover',$cover);
	}

 }
 
?>
