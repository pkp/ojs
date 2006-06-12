<?php

/**
 * LogEntry.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Log entry data object
 *
 * $Id$
 */

define('LOG_ENTRY_TYPE_HTML_ARTICLE', 'html');
define('LOG_ENTRY_TYPE_PDF_ARTICLE', 'pdf');
define('LOG_ENTRY_TYPE_OTHER_ARTICLE', 'article');
define('LOG_ENTRY_TYPE_SEARCH', 'search');
class LogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function LogEntry() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get date & time stamp
	 * @return string
	 */
	 function getStamp() {
	 	return $this->getData('stamp');
	}
	
	/**
	* Set date/time stamp
	* @param $stamp string
	*/
	function setStamp($stamp) {
		return $this->setData('stamp',$stamp);
	}
	
	/**
	 * Get site
	 * @return string
	 */
	 function getSite() {
	 	return $this->getData('site');
	}
	
	/**
	* Set site
	* @param $site string
	*/
	function setSite($site) {
		return $this->setData('site',$site);
	}
	
	/**
	 * Get journal
	 * @return string
	 */
	 function getJournal() {
	 	return $this->getData('journal');
	}
	
	/**
	* Set journal
	* @param $journal string
	*/
	function setJournal($journal) {
		return $this->setData('journal',$journal);
	}
	
	/**
	 * Get publisher
	 * @return string
	 */
	 function getPublisher() {
	 	return $this->getData('publisher');
	}
	
	/**
	* Set publisher
	* @param $publisher string
	*/
	function setPublisher($publisher) {
		return $this->setData('publisher',$publisher);
	}

	/**
	 * Get print ISSN
	 * @return string
	 */
	 function getPrintIssn() {
	 	return $this->getData('printIssn');
	}
	
	/**
	* Set print ISSN
	* @param $printIssn string
	*/
	function setPrintIssn($printIssn) {
		return $this->setData('printIssn',$printIssn);
	}

	/**
	 * Get online ISSN
	 * @return string
	 */
	 function getOnlineIssn() {
	 	return $this->getData('onlineIssn');
	}
	
	/**
	* Set online ISSN
	* @param $onlineIssn string
	*/
	function setOnlineIssn($onlineIssn) {
		return $this->setData('onlineIssn',$onlineIssn);
	}

	/**
	 * Get type
	 * @return string
	 */
	 function getType() {
	 	return $this->getData('type');
	}
	
	/**
	* Set type
	* @param $type string
	*/
	function setType($type) {
		return $this->setData('type',$type);
	}

	/**
	 * Get value
	 * @return string
	 */
	 function getValue() {
	 	return $this->getData('value');
	}
	
	/**
	* Set value
	* @param $value string
	*/
	function setValue($value) {
		return $this->setData('value',$value);
	}

	/**
	 * Get user
	 * @return string
	 */
	 function getUser() {
	 	return $this->getData('user');
	}
	
	/**
	* Set user
	* @param $user string
	*/
	function setUser($user) {
		return $this->setData('user',$user);
	}

	/**
	 * Get category
	 * @return string
	 */
	 function getCategory() {
	 	return $this->getData('category');
	}
	
	/**
	* Set category
	* @param $category string
	*/
	function setCategory($category) {
		return $this->setData('category',$category);
	}

	/**
	 * Get description
	 * @return string
	 */
	 function getDescription() {
	 	return $this->getData('description');
	}
	
	/**
	* Set description
	* @param $description string
	*/
	function setDescription($description) {
		return $this->setData('description',$description);
	}

	/**
	 * Get journal URL
	 * @return string
	 */
	 function getJournalUrl() {
	 	return $this->getData('journalUrl');
	}
	
	/**
	* Set journal URL
	* @param $url string
	*/
	function setJournalUrl($url) {
		return $this->setData('journalUrl',$url);
	}


}

?>
