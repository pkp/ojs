<?php

/**
 * FrontMatterSection.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Front Matter Section.
 *
 * $Id$
 */
 
class FrontMatterSection extends DataObject {
 
	/**
	 * Constructor.
	 */
	function FrontMatterSection() {
		parent::DataObject();
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
	 * get abbrev
	 * @return string
	 */
	function getAbbrev() {
		return $this->getData('abbrev');
	}
	 
	/**
	 * set abbrev
	 * @param $abbrev string
	 */
	function setAbbrev($abbrev) {
		return $this->setData('abbrev',$abbrev);
	}

	/**
	 * get seq
	 * @return int
	 */
	function getSeq() {
		return $this->getData('seq');
	}
	 
	/**
	 * set seq
	 * @param $seq int
	 */
	function setSeq($seq) {
		return $this->setData('seq',$seq);
	}

 }
 
?>
