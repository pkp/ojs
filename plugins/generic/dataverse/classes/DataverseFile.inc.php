<?php

/**
 * @file plugins/generic/dataverse/classes/DataverseFile.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseFile
 * @ingroup plugins_generic_dataverse
 *
 * @brief Basic class describing a data file stored in Dataverse
 */

import('classes.article.SuppFile');

class DataverseFile extends DataObject {
  
  function DataverseFile() {
    parent::DataObject();
  }
  
	//
	// Get/set methods
	//

  /**
   * Supplementary file id
   */
  function getSuppFileId() {
    return $this->getData('suppFileId');
  }
  function setSuppFileId($suppFileId) {
    $this->setData('suppFileId', $suppFileId);
  }
  
  /**
   * Dataverse study id
   */
  function getStudyId() {
    return $this->getData('studyId');
  }
  function setStudyId($studyId) {
    $this->setData('studyId', $studyId);
  }
  
  /**
   * Submission id
   */
  function getSubmissionId() {
    return $this->getData('submissionId');
  }
  function setSubmissionId($submissionId) {
    $this->setData('submissionId', $submissionId);
  }
  
  
  /**
   * Content source URI
   */
  function getContentSourceUri() {
    return $this->getData('contentSourceUri');
  }
  function setContentSourceUri($contentSourceUri) {
    $this->setData('contentSourceUri', $contentSourceUri);
  }
  
  
}

?>
