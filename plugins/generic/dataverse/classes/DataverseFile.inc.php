<?php

/**
 * @file plugins/generic/dataverse/classes/DataverseFile.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseFile
 * @ingroup plugins_generic_dataverse
 *
 * @brief DataverseFile object associates suppfile with a Dataverse study
 */

import('classes.article.SuppFile');

class DataverseFile extends DataObject {
  
  function DataverseFile() {
    parent::DataObject();
  }
  
  /**
   * Get suppfile ID
   * @return int
   */
  function getSuppFileId() {
    return $this->getData('suppFileId');
  }
  
  /**
   * Set suppfile ID
   * @param $suppFileId int
   */
  function setSuppFileId($suppFileId) {
    $this->setData('suppFileId', $suppFileId);
  }
  
  /**
   * Get Dataverse study ID
   * @return int
   */
  function getStudyId() {
    return $this->getData('studyId');
  }
  
  /**
   * Set Dataverse study ID
   * @param $studyId int
   */
  function setStudyId($studyId) {
    $this->setData('studyId', $studyId);
  }
  
  /**
   * Get submission ID
   * @return int
   */
  function getSubmissionId() {
    return $this->getData('submissionId');
  }
  
  /**
   * Set submission ID
   * @param $submissionId int
   */
  function setSubmissionId($submissionId) {
    $this->setData('submissionId', $submissionId);
  }
  
  /**
   * Get content source URI for file in Dataverse
   * @return string
   */
  function getContentSourceUri() {
    return $this->getData('contentSourceUri');
  }
  
  /**
   * Set content source URI for file in Dataverse
   * @param $contentSourceUri string
   */
  function setContentSourceUri($contentSourceUri) {
    $this->setData('contentSourceUri', $contentSourceUri);
  }
  
  
}

?>
