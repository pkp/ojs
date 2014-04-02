<?php

/**
 * @file plugins/generic/dataverse/classes/DataverseStudy.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseStudy
 * @ingroup plugins_generic_dataverse
 *
 * @brief Basic class describing a Dataverse study
 */

class DataverseStudy extends DataObject {
  
  function DataverseStudy() {
    parent::DataObject();
  }
  
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the study.
	 * @return int
	 */
	function getId() {
		return $this->getData('studyId');
	}

	/**
	 * Set the ID of the study.
	 * @param $studyId int
	 */
	function setId($studyId) {
		return $this->setData('studyId', $studyId);
	}

  /**
	 * Get the study's submission ID 
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set the study's submission ID
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}
  
	/**
	 * Get study edit URI.
	 * @return string
	 */
	function getEditUri() {
		return $this->getData('editUri');
	}

	/**
	 * Set study edit URI
	 * @param $editUri string
	 */
	function setEditUri($editUri) {
		return $this->setData('editUri', $editUri);
	}  
  
	/**
	 * Get study's edit media URI.
	 * @return string
	 */
	function getEditMediaUri() {
		return $this->getData('editMediaUri');
	}

	/**
	 * Set study's edit media URI
	 * @param $editMediaUri string
	 */
	function setEditMediaUri($editMediaUri) {
		return $this->setData('editMediaUri', $editMediaUri);
	}  

  /**
	 * Get study's statement URI.
	 * @return string
	 */
	function getStatementUri() {
		return $this->getData('statementUri');
	}

	/**
	 * Set study's statement URI
	 * @param $statementUri string
	 */
	function setStatementUri($statementUri) {
		return $this->setData('statementUri', $statementUri);
	} 
  
  /**
   * Get study persistent URI
   * @return string
   */
  function getPersistentUri() {
    return $this->getData('persistentUri');
  }
  
  /**
   * Set study persistent Uri
   * @param string $persistentUri
   */
  function setPersistentUri($persistentUri) {
    $this->setData('persistentUri', $persistentUri);
  }
  
  /**
   * Get data citation
   * @param boolean $markup add HTML to link persistent URI
   */
  function getDataCitation($markup = true) {
    $dataCitation = $this->getData('dataCitation');
    if ($markup) {
      // Add markup to link persistent URI.
      $dataCitation = str_replace($this->getPersistentUri(), '<a href="'. $this->getPersistentUri() .'">'. $this->getPersistentUri() .'</a>', $dataCitation);
    }
    return $dataCitation;
  }
  
  /**
   * Set data citation
   * @param string $dataCitation
   */
  function setDataCitation($dataCitation) {
    $this->setData('dataCitation', $dataCitation);
  }

}

?>
